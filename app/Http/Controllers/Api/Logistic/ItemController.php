<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\Item;
use App\Models\Office;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'office_hash'   => 'nullable',
            'order'         => 'required|in:asc,desc',
            'desc'          => 'nullable',
        ]);


        $officeId = (empty($request->office_hash)) ? null : Office::hashToId($request->office_hash);

        $items = Item::orderBy('description', $request->order);

        if ($officeId !== null) {
            $items->whereHas('delivery', function ($query) use ($officeId) {
                $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId);
            });
        }

        if (! empty($request->desc)) {
            $items->where('description', $this->getMatchLikeClause(Item::query()), '%'.$request->desc.'%');
        }

        if (! empty($request->receipt)) {
            $items->where('receipt', $request->receipt);
        }

        $items->with('delivery.originOffice', 'manifests.originOffice', 'manifests.destinationOffice', 'manifests.driver');

        return $items->paginate($request->input('per_page'));
    }

    // ------------------------- START Public API
    public function checkReceipt(Request $request)
    {
        $request->validate([
            'receipt'   => 'required',
        ]);

        $item = Item::where('receipt', $request->receipt)->with('manifests', 'lastManifest')->first();

        return ($item) ? $this->success($item) : $this->noContent();
    }
    // ------------------------- END Public API
}
