<?php

namespace App\Actions\Logistic\Service;

use App\Models\Logistic\Service;
use Illuminate\Support\Facades\Validator;

class CreateNewService
{
    public function create(array $attributes = []): Service
    {
        $inputs = Validator::make($attributes, [
            'name'          =>  'required',
            'etd'           =>  'required',
            'price_weight'  =>  'required|integer',
            'min_weight'    =>  'required|integer',
            'price_volume'  =>  'required|integer',
            'min_volume'    =>  'required|integer',
            'credit'        =>  'nullable|boolean',
        ])->validate();

        $service = new Service();

        $service->fill($inputs);

        $service->save();

        return $service;
    }
}
