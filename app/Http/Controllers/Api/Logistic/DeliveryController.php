<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Actions\Logistic\Delivery\ActionAfterArrived;
use App\Http\Controllers\Controller;
use App\Models\Geo\Regency;
use App\Models\Logistic\Price;
use Illuminate\Http\Request;
use App\Models\Logistic\Service;
use App\Actions\Logistic\Delivery\CreateNewDelivery;
use App\Actions\Logistic\Delivery\DeleteExistingDelivery;
use App\Actions\Logistic\Delivery\CreateQrcode;
use App\Actions\Logistic\Delivery\ShowDelivery;
use App\Models\Logistic\Delivery;
use App\Models\Office;
use Veelasky\LaravelHashId\Rules\ExistsByHash;
use Illuminate\Database\Eloquent\Builder;

class DeliveryController extends Controller
{
    // ------------------------- START Public API
    public function getOriginOffice(Request $request)
    {
        $offices = Office::has('regency.originCities');

        return $this->success($offices->paginate($request->input('per_page')));
    }

    public function getDestinationOffice(Request $request, $office)
    {
        $offices = Office::whereHas('regency.destinationCities', function ($query) use ($office) {
            $query->where('origin_city_id', $office->load('regency')->regency->id);
        });

        $offices->when($request->input('keyword'), function (Builder $builder, $keyword) {
            $likeClause = $this->getMatchLikeClause($builder);

            $builder->where(fn (Builder $builder) => $builder->where('name', $likeClause, "%$keyword%"));
        });

        return $this->success($offices->paginate($request->input('per_page')));
    }

    public function getPriceByOffice(Request $request)
    {
        $request->validate([
            'origin_office_hash'      =>  ['required', new ExistsByHash(Office::class)],
            'destination_office_hash' =>  ['required', new ExistsByHash(Office::class)],
        ]);

        $originOffice = Office::byHash($request->origin_office_hash)->load('regency');
        $destinationOffice = Office::byHash($request->destination_office_hash)->load('regency');

        $query = Price::query()
            ->with('service')
            ->where('origin_city_id', $originOffice->regency->id)->where('destination_city_id', $destinationOffice->regency->id)->distinct('title_type')
            ->when($request->input('keyword'), function (Builder $builder, $keyword) {
                $likeClause = $this->getMatchLikeClause($builder);

                $builder->where(fn (Builder $builder) => $builder->where('title_type', $likeClause, "%$keyword%"));
            });

        return $query->paginate($request->input('per_page'));
    }

    public function checkPrice(Request $request)
    {
        $request->validate([
            'origin_office_hash'        =>  ['required', new ExistsByHash(Office::class)],
            'destination_office_hash'   =>  ['required', new ExistsByHash(Office::class)],
            'price_hash'                =>  ['required', new ExistsByHash(Price::class)]
        ]);

        $price = Price::byHash($request->price_hash);

        if ($price->type !== null) {
            $request->validate([
                'value' =>  'required|integer', // required if price_calc_type is precentage
            ]);
        } else {
            $request->validate([
                'volume' =>  'required|integer', // required if price type is null
                'weight' =>  'required|integer', // required if price type is null
            ]);
        }

        $originOffice = Office::byHash($request->origin_office_hash)->load('regency');
        $destinationOffice = Office::byHash($request->destination_office_hash)->load('regency');

        $servicesId = Price::where('origin_city_id', $originOffice->regency->id)
                    ->select('logistic_service_id')
                    ->where('destination_city_id', $destinationOffice->regency->id);
        if ($price->type !== null) {
            $servicesId->where('type', $price->type);
        } else {
            $servicesId->whereNull('type');
        }
        $servicesId->distinct('logistic_service_id')
            ->pluck('logistic_service_id');

        $services = Service::whereIn('id', $servicesId)->get();

        $data = [];

        foreach ($services as $key => $service) {
            $shippingCost = 0;
            if (empty($price->type)) {
                $priceWeight = (empty($price->price_weight)) ? $service->price_weight : $price->price_weight;
                $minWeight = (empty($price->min_weight)) ? $service->min_weight : $price->min_weight;
                $priceVolume = (empty($price->price_volume)) ? $service->price_volume : $price->price_volume;
                $minVolume = (empty($price->min_volume)) ? $service->min_volume : $price->min_volume;
                $totalWeight = ($priceWeight * (($request->weight < $minWeight) ? $minWeight : $request->weight));
                $totalVolume = ($priceVolume * (($request->volume < $minVolume) ? $minVolume : $request->volume));

                $shippingCost = ($totalWeight >= $totalVolume) ? $totalWeight : $totalVolume;
            } else {
                if ($price->price_calc_type === 'percentage_by_value') {
                    $shippingCost = $request->value / 100 * $price->price_calc_type_value;
                } else {
                    $shippingCost = $price->price_calc_type_value;
                }
            }

            $data[] = [
                'name'  =>  $service->name,
                'etd'  =>  $service->etd,
                'price'  =>  $shippingCost,
            ];
        }

        return $this->success($data);
    }
    // ------------------------- END Public API

    public function getOriginCity(Request $request)
    {
        $regencies = Regency::has('originCities');

        if ($request->filled('regency_hash')) {
            $id = Regency::hashToId($request->regency_hash);
            $regency = $regencies->where('id', $id)->first();

            return response()->json(['data'  => ($regency) ? $regency : null]);
        }

        $regencies->when($request->input('keyword'), function (Builder $builder, $keyword) {
            $likeClause = $this->getMatchLikeClause($builder);

            $builder->where(fn (Builder $builder) => $builder->where('name', $likeClause, "%$keyword%"));
        });

        return $regencies->paginate($request->input('per_page'));
    }

    public function getDestinationCity(Request $request, $hash)
    {
        $id = Regency::hashToId($hash);
        $regencies = Regency::whereHas('destinationCities', function ($query) use ($id) {
            $query->where('origin_city_id', $id);
        });

        $regencies->when($request->input('keyword'), function (Builder $builder, $keyword) {
            $likeClause = $this->getMatchLikeClause($builder);

            $builder->where(fn (Builder $builder) => $builder->where('name', $likeClause, "%$keyword%"));
        });

        return $regencies->paginate($request->input('per_page'));
    }

    public function getAvailablePrice(Request $request)
    {
        $request->validate([
            'origin_city_hash'      =>  ['required', new ExistsByHash(Regency::class)],
            'destination_city_hash' =>  ['required', new ExistsByHash(Regency::class)],
        ]);

        $originId = Regency::hashToId($request->origin_city_hash);
        $destinationId = Regency::hashToId($request->destination_city_hash);
        $query = Price::query()->with('service');

        $query->where('origin_city_id', $originId)->where('destination_city_id', $destinationId)->distinct('title_type');

        $query->when($request->input('keyword'), function (Builder $builder, $keyword) {
            $likeClause = $this->getMatchLikeClause($builder);

            $builder->where(fn (Builder $builder) => $builder->where('title_type', $likeClause, "%$keyword%"));
        });

        return $query->paginate($request->input('per_page'));
    }

    public function getAvailableService(Request $request)
    {
        $request->validate([
            'origin_city_hash'      =>  ['required', new ExistsByHash(Regency::class)],
            'destination_city_hash' =>  ['required', new ExistsByHash(Regency::class)],
            'type'                  =>  'required',
        ]);

        $originId = Regency::hashToId($request->origin_city_hash);
        $destinationId = Regency::hashToId($request->destination_city_hash);

        $prices = Price::where('origin_city_id', $originId)
                    ->select('logistic_service_id')
                    ->where('destination_city_id', $destinationId)
                    ->where(function ($r) use ($request) {
                        foreach ($request->type as $key => $value) {
                            ($key > 0) ? $r->orWhere('title_type', $value) : $r->where('title_type', $value);
                        }
                    })
                    ->distinct('logistic_service_id')
                    ->pluck('logistic_service_id');

        $services = Service::whereIn('id', $prices)->get();

        return $this->success($services);
    }

    public function getTypePriceByService(Request $request)
    {
        $request->validate([
            'origin_city_hash'          =>  ['required', new ExistsByHash(Regency::class)],
            'destination_city_hash'     =>  ['required', new ExistsByHash(Regency::class)],
            'logistic_service_hash'     =>  ['required', new ExistsByHash(Service::class)],
            'title_type'                =>  'required',
        ]);

        $originId = Regency::hashToId($request->origin_city_hash);
        $destinationId = Regency::hashToId($request->destination_city_hash);
        $serviceId = Service::hashToId($request->logistic_service_hash);

        $price = Price::where([
            ['origin_city_id', '=', $originId],
            ['destination_city_id', '=', $destinationId],
            ['logistic_service_id', '=', $serviceId],
            ['title_type', '=', $request->title_type]
        ])->first();

        return $this->success($price);
    }

    public function getDataReceipt(Request $request)
    {
        return $this->success($request->logistic_delivery_hash->load(['items', 'originCity', 'destinationCity', 'originOffice', 'destinationOffice']));
    }

    public function createQrcode(Request $request, CreateQrcode $action)
    {
        return $this->success($action->generate($request->logistic_delivery_hash));
    }

    public function index(Request $request, ShowDelivery $action)
    {
        return response()->json($action->show($request->all()));
    }

    public function show(Delivery $delivery)
    {
        return $this->success($delivery->load('originCity', 'destinationCity', 'originOffice', 'destinationOffice', 'service', 'items'));
    }

    public function store(Request $request, CreateNewDelivery $action)
    {
        return $this->success($action->store($request->all()));
    }

    // public function update(Request $request, Price $price, UpdateExistingPrice $action)
    // {
    //     return $this->success($action->create($request->all(), $price));
    // }

    public function destroy(Delivery $delivery, DeleteExistingDelivery $action)
    {
        return $this->success($action->delete($delivery));
    }

    public function paid(Delivery $delivery, ActionAfterArrived $action)
    {
        return $this->success($action->paid($delivery));
    }

    public function taken(Delivery $delivery, ActionAfterArrived $action)
    {
        return $this->success($action->taken($delivery));
    }
}
