<?php

namespace App\Actions\Logistic\Manifest;

use App\Models\Logistic\Item;
use App\Models\Logistic\Manifest;
use Illuminate\Support\Facades\Validator;

class Unloading
{
    public function unload($attributes)
    {
        $inputs = Validator::make($attributes, [
            'items'     =>  'required|array',
        ])->validate();

        $itemsReturn = [];
        foreach ($inputs['items'] as $key => $value) {
            $item = Item::where('receipt', $value['receipt'])->update(['status' => 'arrived']);
            $manifest = Manifest::where('code', $value['active_manifest']['code'])->first();
            $items = $manifest->items()->get();
            $arrived = [];
            foreach ($items as $key => $value) {
                if ($value->status == 'arrived') {
                    $arrived[] = $value;
                }
            }

            if (count($items) == count($arrived)) {
                $manifest->status = 'arrived';
                $manifest->save();
            }
            $itemsReturn[] = $item;
        }

        return $itemsReturn;
    }
}
