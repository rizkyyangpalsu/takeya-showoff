<?php

namespace App\Actions\Template;

use App\Models\Office;
use App\Models\Template;
use Illuminate\Support\Facades\Validator;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class CreateNewTemplate
{
    public function create(array $attributes = []): Template
    {
        $inputs = Validator::make($attributes, [
            'body'          =>  'required',
            'type'          =>  'required',
            'office_hash'   =>  ['required', new ExistsByHash(Office::class)],
        ])->validate();

        $template = new Template();

        $inputs['office_id'] = Office::hashToId($inputs['office_hash']);

        $template->fill($inputs);

        $template->save();

        return $template;
    }
}
