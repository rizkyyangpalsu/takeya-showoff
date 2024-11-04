<?php

namespace App\Actions\Logistic\Delivery;

use App\Models\Geo\Regency;
use App\Models\Logistic\Delivery;
use App\Models\Logistic\Item;
use App\Models\Logistic\Price;
use App\Models\Logistic\Service;
use App\Models\Office;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Veelasky\LaravelHashId\Rules\ExistsByHash;
use App\Actions\Logistic\Code\Code;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CreateNewDelivery
{
    public function store(array $attributes = [])
    {
        $inputs = Validator::make($attributes, [
            'delivery.origin_city_id'           =>  ['required', new ExistsByHash(Regency::class)],
            'delivery.destination_city_id'      =>  ['required', new ExistsByHash(Regency::class)],
            'delivery.origin_office_id'         =>  [new ExistsByHash(Office::class)],
            'delivery.destination_office_id'    =>  [new ExistsByHash(Office::class)],
            'delivery.logistic_service_id'      =>  ['required', new ExistsByHash(Service::class)],
            'delivery.sender_name'              =>  'required',
            'delivery.sender_phone'             =>  'nullable|numeric',
            'delivery.sender_postal_code'       =>  'nullable|numeric',
            'delivery.recipient_address'        =>  'nullable',
            'delivery.recipient_name'           =>  'required',
            'delivery.recipient_phone'          =>  'nullable|numeric',
            'delivery.recipient_postal_code'    =>  'nullable|numeric',
            'delivery.recipient_address'        =>  'nullable',
            'delivery.payment_method'           =>  'required|in:cod,credit',
            'delivery.payment_by'               =>  'required|in:sender,recipient',
            'delivery.payment_deadline'         =>  'nullable|numeric',
            'delivery.other_cost'               =>  'required|numeric',
            'delivery.discount'                 =>  'required|numeric',
            'items.*'                           =>  'required',
            'items.*.description'               =>  'required',
            'items.*.qty'                       =>  'required|numeric',
            'items.*.price'                     =>  'required',
            'items.*.price.hash'                =>  ['required', new ExistsByHash(Price::class)],
            'items.*.volume'                    =>  'required|numeric',
            'items.*.weight'                    =>  'required|numeric',
            'items.*.value'                     =>  'nullable|numeric',
            'date'                              =>  'nullable'
        ])->validate();

        $originCity = Regency::byHash($inputs['delivery']['origin_city_id']);
        $destinationCity = Regency::byHash($inputs['delivery']['destination_city_id']);
        $originOffice = Office::byHash($inputs['delivery']['origin_office_id']);
        $service = Service::byHash($inputs['delivery']['logistic_service_id']);

        $inputs['delivery']['origin_city_id'] = $originCity->id;
        $inputs['delivery']['destination_city_id'] = $destinationCity->id;
        $inputs['delivery']['origin_office_id'] =  (! empty($inputs['delivery']['origin_office_id'])) ? Office::hashToId($inputs['delivery']['origin_office_id']) : null;
        $inputs['delivery']['destination_office_id'] = (! empty($inputs['delivery']['destination_office_id'])) ? Office::hashToId($inputs['delivery']['destination_office_id']) : null;
        $inputs['delivery']['logistic_service_id'] = $service->id;

        // will be response later
        $code = new Code();
        $delivery = new Delivery();
        $items = [];

        DB::transaction(static function () use ($inputs, $originCity, $destinationCity, $originOffice, $service, &$code, &$delivery, &$items) {
            $inputs['delivery']['code'] = $code->delivery($originOffice->id, (Auth::check()) ? Auth::user()->id : User::where('user_type', 'SUPER_ADMIN')->first()->id, (! empty($inputs['date'])) ? $inputs['date'] : null);
            $inputs['delivery']['origin_city_name'] = $originCity->name;
            $inputs['delivery']['destination_city_name'] = $destinationCity->name;
            $inputs['delivery']['origin_office_name'] = $originOffice->name;
            $inputs['delivery']['destination_office_name'] = (! empty($inputs['delivery']['destination_office_id'])) ? Office::byHash($inputs['delivery']['destination_office_id']) : null;
            $inputs['delivery']['service_name'] = $service->name;
            $inputs['delivery']['service_etd'] = $service->etd;
            $inputs['delivery']['service_price_weight'] = $service->price_weight;
            $inputs['delivery']['service_min_weight'] = $service->min_weight;
            $inputs['delivery']['service_price_volume'] = $service->price_volume;
            $inputs['delivery']['service_min_volume'] = $service->min_volume;
            $inputs['delivery']['service_credit'] = $service->credit;
            if (! empty($inputs['delivery']['payment_deadline'])) {
                $inputs['delivery']['payment_deadline_date'] = Carbon::now()->addDays($inputs['delivery']['payment_deadline'])->format('Y-m-d H:i:s');
            }

            if ($inputs['delivery']['payment_method'] === 'cod' && $inputs['delivery']['payment_by'] === 'sender') {
                $inputs['delivery']['paid'] = Carbon::now();
            }
            $delivery->fill($inputs['delivery']);
            if (! empty($inputs['date'])) {
                $delivery->created_at = $inputs['date'];
            }
            if (! empty($inputs['date'])) {
                $delivery->updated_at = $inputs['date'];
            }
            $delivery->save();

            $totalShippingCost = 0;
            foreach ($inputs['items'] as $key => $value) {
                $priceId = Price::hashToId($value['price']['hash']);
                for ($i=0; $i < $value['qty']; $i++) {
                    $item = new Item();
                    $item->logistic_delivery_id = $delivery->id;
                    $item->price_id = $priceId;
                    $item->description = $value['description'];
                    $item->weight = $value['weight'];
                    $item->volume = $value['volume'];
                    $item->value = (! empty($value['value'])) ? $value['value'] : null;
                    $item->price_title_type = $value['price']['title_type'];
                    $item->price_calc_type = (! empty($value['price']['price_calc_type'])) ? $value['price']['price_calc_type'] : null;
                    $item->price_calc_type_value = (! empty($value['price']['price_calc_type_value'])) ? $value['price']['price_calc_type_value'] : null;

                    if (empty($value['price']['type'])) {
                        $priceWeight = (empty($value['price']['price_weight'])) ? $service->price_weight : $value['price']['price_weight'];
                        $minWeight = (empty($value['price']['min_weight'])) ? $service->min_weight : $value['price']['min_weight'];
                        $priceVolume = (empty($value['price']['price_volume'])) ? $service->price_volume : $value['price']['price_volume'];
                        $minVolume = (empty($value['price']['min_volume'])) ? $service->min_volume : $value['price']['min_volume'];
                        $totalWeight = ($priceWeight * (($value['weight'] < $minWeight) ? $minWeight : $value['weight']));
                        $totalVolume = ($priceVolume * (($value['volume'] < $minVolume) ? $minVolume : $value['volume']));

                        $shippingCost = ($totalWeight >= $totalVolume) ? $totalWeight : $totalVolume;
                    } else {
                        if ($value['price']['price_calc_type'] === 'percentage_by_value') {
                            $shippingCost = ($value['value'] / 100 * $value['price']['price_calc_type_value']) * $item->qty;
                        } else {
                            $shippingCost = $value['price']['price_calc_type_value'];
                        }
                    }

                    $totalShippingCost += $shippingCost;

                    $item->shipping_cost = $shippingCost;
                    if (! empty($inputs['date'])) {
                        $item->created_at = $inputs['date'];
                    }
                    if (! empty($inputs['date'])) {
                        $item->updated_at = $inputs['date'];
                    }
                    $item->save();
                    $items[] = $item;
                }
            }

            $delivery->shipping_cost = $totalShippingCost;
            $delivery->total_cost = $totalShippingCost + $inputs['delivery']['other_cost'] - $inputs['delivery']['discount'];
            $delivery->save();
        });

        return [
            'delivery'  =>  $delivery,
            'items'     =>  $items,
        ];
    }
}
