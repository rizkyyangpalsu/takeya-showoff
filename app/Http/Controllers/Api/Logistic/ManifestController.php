<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Actions\Logistic\Manifest\CreateOrUpdateManifest;
use App\Actions\Logistic\Manifest\DeleteExistingManifest;
use App\Actions\Logistic\Manifest\RemoveItem;
use App\Actions\Logistic\Manifest\Unloading;
use App\Http\Controllers\Controller;
use App\Models\Logistic\Item;
use App\Models\Logistic\Manifest;
use App\Models\Office;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class ManifestController extends Controller
{
    public function getItem(Request $request)
    {
        $item = Item::where('receipt', $request->receipt)->with('price')->first();

        return $this->success($item);
    }

    public function index(Request $request)
    {
        $request->validate([
            'date_from'         => 'required|date_format:Y-m-d',
            'date_to'           => 'required|date_format:Y-m-d',
            'code'              => 'nullable',
            'receipt'           => 'nullable',
            'status'            => 'nullable|in:created,loading,expedition,arrived',
            'office_hash'       => ['nullable', new ExistsByHash(Office::class)]
        ]);


        $from = Carbon::createFromFormat('Y-m-d', $request['date_from'])->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $request['date_to'])->endOfDay();

        $manifests = Manifest::whereBetween('created_at', [$from, $to]);


        if (! empty($request->code)) {
            $manifests->where('code', $request->code);
        }
        if (! empty($request->receipt)) {
            $manifests->whereHas('items', function ($query) use ($request) {
                $query->where('receipt', $request->receipt);
            });
        }

        if (! empty($request->status)) {
            $manifests->where('status', $request->status);
        }

        if (! empty($request['office_hash'])) {
            $office = Office::byHash($request['office_hash']);
            $manifests->where(fn ($query) => $query->where('origin_office_id', $office->id)->orWhere('destination_office_id', $office->id));
        }

        $manifests->with('originOffice', 'destinationOffice', 'fleet', 'driver', 'items.price')->orderBy('created_at', 'desc');

        return $manifests->paginate($request->input('per_page'));
    }

    public function show(Manifest $manifest)
    {
        return $this->success($manifest->load('originOffice', 'destinationOffice', 'fleet', 'driver', 'items.price', 'items.delivery'));
    }

    public function createOrUpdate(Request $request, CreateOrUpdateManifest $action)
    {
        return $this->success($action->createOrUpdate($request->all()));
    }

    public function removeItem(Request $request, RemoveItem $action)
    {
        return $this->success($action->remove($request->all()));
    }

    public function changeToExpedition(Manifest $manifest)
    {
        $manifest->status = 'expedition';
        $manifest->save();

        $itemsId = $manifest->items()->pluck('id');

        Item::whereIn('id', $itemsId)->update([
            'status'        =>  'expedition',
            'last_manifest' =>  $manifest->id
        ]);

        return $manifest;
    }

    public function destroy(Manifest $manifest, DeleteExistingManifest $action)
    {
        return $this->success($action->delete($manifest));
    }

    public function scanInBound(Request $request)
    {
        $item = Item::where('receipt', $request->receipt)->with(['price', 'manifests' => fn ($query) => $query->where('status', 'expedition')])->first();

        $activeManifest = $item->manifests->first();

        abort_if(! $activeManifest, 404);
        $item->active_manifest = $activeManifest;

        return $this->success([
            'manifest'  => $activeManifest,
            'item'      => $item
        ]);
    }

    public function unloading(Request $request, Unloading $action)
    {
        return $this->success($action->unload($request->all()));
    }
}
