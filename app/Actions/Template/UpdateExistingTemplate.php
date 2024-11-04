<?php

namespace App\Actions\Template;

use App\Models\Office;
use Illuminate\Support\Facades\Validator;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class UpdateExistingTemplate
{
    public function create(array $attributes = [], $template)
    {
        $inputs = Validator::make($attributes, [
            'body'          =>  'required',
            'type'          =>  'required',
            'office_hash'   =>  ['required', new ExistsByHash(Office::class)],
        ])->validate();

        $inputs['office_id'] = Office::hashToId($inputs['office_hash']);

        $template->fill($inputs);

        $template->save();

        return $template;
    }
}
