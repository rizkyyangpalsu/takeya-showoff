<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Actions\Logistic\Price\CreateNewPrice;
use App\Actions\Logistic\Price\UpdateExistingPrice;
use App\Actions\Logistic\Price\DeleteExistingPrice;
use App\Http\Controllers\Controller;
use App\Models\Geo\Regency;
use App\Models\Logistic\Price;
use App\Models\Logistic\Service;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class PriceController extends Controller
{
    public function index(Request $request)
    {
        $query = Price::query();

        $this->applyFilter($query, $request);

        $data = $query->with('originCity', 'destinationCity', 'service')->orderBy('created_at', 'desc')->paginate($request->input('per_page'));

        return response()->json($data);
    }

    public function searchType(Request $request)
    {
        $prices = Price::where('type', $this->getMatchLikeClause(Price::query()), '%'.$request->type.'%')->get();
        return response()->json($prices);
    }

    public function show(Price $price)
    {
        return $this->success($price->load('originCity', 'destinationCity', 'service'));
    }

    public function store(Request $request, CreateNewPrice $action)
    {
        return $this->success($action->store($request->all()));
    }

    public function update(Request $request, Price $price, UpdateExistingPrice $action)
    {
        return $this->success($action->update($request->all(), $price));
    }

    public function destroy(Price $price, DeleteExistingPrice $action)
    {
        return $this->success($action->delete($price));
    }

    private function applyFilter(Builder $query, Request $request): void
    {
        $inputs = $request->validate([
            'type'                  =>  ['nullable', 'string'],
            'origin_city_id'        =>  ['nullable', new ExistsByHash(Regency::class)],
            'destination_city_id'   =>  ['nullable', new ExistsByHash(Regency::class)],
            'logistic_service_id'   =>  ['nullable', new ExistsByHash(Service::class)],
        ]);

        $originId = (array_key_exists('origin_city_id', $inputs)) ? Regency::hashToId($inputs['origin_city_id']) : null;
        $destinationId = (array_key_exists('destination_city_id', $inputs)) ? Regency::hashToId($inputs['destination_city_id']) : null;
        $serviceId = (array_key_exists('logistic_service_id', $inputs)) ? Service::hashToId($inputs['logistic_service_id']) : null;

        $query->when($inputs['type'] ?? false, fn (Builder $query, $type) => $query->where('title_type', 'like', '%'.$type.'%'));
        $query->when($originId ?? false, fn (Builder $query, $val) => $query->where('origin_city_id', $val));
        $query->when($destinationId ?? false, fn (Builder $query, $val) => $query->where('destination_city_id', $val));
        $query->when($serviceId ?? false, fn (Builder $query, $val) => $query->where('logistic_service_id', $val));
    }
}
