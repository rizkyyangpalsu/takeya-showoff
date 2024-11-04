<?php

namespace App\Actions\Logistic\Manifest;

use App\Actions\Logistic\Code\Code;
use App\Models\Fleet;
use App\Models\Logistic\Item;
use App\Models\Logistic\Manifest;
use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class CreateOrUpdateManifest
{
    public function createOrUpdate(array $attributes = []): Manifest
    {
        $inputs = Validator::make($attributes, [
            'hash'                  =>  ['nullable', new ExistsByHash(Manifest::class)],
            'origin_office_id'      =>  ['required', new ExistsByHash(Office::class)],
            'destination_office_id' =>  ['required', new ExistsByHash(Office::class)],
            'fleet_id'              =>  ['nullable', new ExistsByHash(Fleet::class)],
            'driver_id'             =>  ['required', new ExistsByHash(User::class)],
            'pickup_time'           =>  'required|date_format:Y-m-d H:i:s',
            'status'                =>  'required|in:created,loading,expedition',
            'items'                 =>  'nullable',
        ])->validate();

        $items = empty($inputs['items']) ? null : $inputs['items'];
        unset($inputs['items']);

        $inputs['origin_office_id'] = Office::hashToId($inputs['origin_office_id']);
        $inputs['destination_office_id'] = Office::hashToId($inputs['destination_office_id']);
        if (! empty($inputs['fleet_id'])) {
            $inputs['fleet_id'] = Fleet::hashToId($inputs['fleet_id']);
        }
        $inputs['driver_id'] = User::hashToId($inputs['driver_id']);

        if (empty($items)) {
            $inputs['status'] = 'created';
        }

        $manifest = new Manifest();
        if (! empty($inputs['hash'])) {
            $manifestId = Manifest::hashToId($inputs['hash']);
            $manifest = Manifest::find($manifestId);
        } else {
            if (! empty($attributes['date'])) {
                $manifest->created_at = $attributes['date'];
            }
            if (! empty($attributes['date'])) {
                $manifest->updated_at = $attributes['date'];
            }

            $code = new Code();
            $inputs['code'] = $code->manifest($inputs['origin_office_id'], $inputs['destination_office_id'], (! empty($attributes['date'])) ? $attributes['date'] : null);
        }

        $inputs['fleet_id'] ??= null;
        $manifest->fill($inputs);
        $manifest->save();

        if (! empty($items)) {
            $status = ($inputs['status'] === 'expedition') ? $inputs['status'] : 'loading';
            $itemsId = [];
            foreach ($items as  $value) {
                $itemsId[] = Item::hashToId($value);
            }
            Item::whereIn('id', $itemsId)->update([
                'status'        =>  ($status === 'expedition') ? $status : 'in_manifest',
                'last_manifest' =>  $manifest->id,
            ]);
            $manifest->items()->sync($itemsId);
            $manifest->status = $status;
            $manifest->save();
        }

        return $manifest;
    }
}
