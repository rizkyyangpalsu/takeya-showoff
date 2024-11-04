<?php

namespace App\Actions\Logistic\Manifest;

use App\Models\Logistic\Item;
use App\Models\Logistic\Manifest;
use Illuminate\Support\Facades\Validator;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class RemoveItem
{
    public function remove(array $attributes = []): Item
    {
        $inputs = Validator::make($attributes, [
            'item_hash'     =>  ['required', new ExistsByHash(Item::class)],
            'manifest_hash' =>  ['required', new ExistsByHash(Manifest::class)],
        ])->validate();

        $item = Item::byHash($inputs['item_hash']);
        $manifest = Manifest::byHash($inputs['manifest_hash']);

        $manifest->items()->detach($item);
        $item->status = 'in_warehouse';
        $item->save();

        return $item;
    }
}
