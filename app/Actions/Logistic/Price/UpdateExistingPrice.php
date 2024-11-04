<?php

namespace App\Actions\Logistic\Price;

use App\Models\Geo\Regency;
use App\Models\Logistic\Service;
use Illuminate\Support\Facades\Validator;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class UpdateExistingPrice
{
    public function update(array $attributes = [], $price)
    {
        $inputs = Validator::make($attributes, [
            'origin_city_id'        =>  ['required', new ExistsByHash(Regency::class)],
            'destination_city_id'   =>  ['required', new ExistsByHash(Regency::class)],
            'logistic_service_id'   =>  ['required', new ExistsByHash(Service::class)],
            'type'                  =>  'nullable',
            'type_weight'           =>  'nullable|integer',
            'type_volume'           =>  'nullable|integer',
            'price_calc_type'       =>  'nullable|in:money,percentage_by_value',
            'price_calc_type_value' =>  'nullable|integer',

            'price_weight'          =>  'nullable|integer',
            'min_weight'            =>  'nullable|integer',
            'price_volume'          =>  'nullable|integer',
            'min_volume'            =>  'nullable|integer',
        ])->validate();

        $inputs['origin_city_id'] = Regency::hashToId($inputs['origin_city_id']);
        $inputs['destination_city_id'] = Regency::hashToId($inputs['destination_city_id']);
        $inputs['logistic_service_id'] = Service::hashToId($inputs['logistic_service_id']);

        $inputs['title_type'] = (! empty($inputs['type'])) ? $inputs['type'] : 'Paket';

        $inputs['type'] ??= null;
        $inputs['type_weight'] ??= null;
        $inputs['type_volume'] ??= null;
        $inputs['price_calc_type'] ??= null;
        $inputs['price_calc_type_value'] ??= null;

        $inputs['price_weight'] ??= null;
        $inputs['min_weight'] ??= null;
        $inputs['price_volume'] ??= null;
        $inputs['min_volume'] ??= null;
        $price->fill($inputs);

        $price->save();

        $price->load(['originCity', 'destinationCity']);

        return $price;
    }
}
