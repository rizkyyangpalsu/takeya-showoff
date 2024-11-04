<?php

namespace App\Actions\Logistic\Delivery;

use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Logistic\Delivery;
use App\Models\Office;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class ShowDelivery
{
    public function show($attributes)
    {
        $request = Validator::make($attributes, [
            'date_from'         => 'required|date_format:Y-m-d',
            'date_to'           => 'required|date_format:Y-m-d',
            'code'              => 'nullable',
            'receipt'           => 'nullable',
            'status'            => 'nullable|in:in_warehouse,in_manifest,expedition,arrived,lost',
            'paid'              => 'nullable',
            'payment_method'    => 'nullable',
            'taken'             => 'nullable',
            'sorting'           => 'required|in:latest,closest-due',
            'per_page'          => 'nullable',
            'office_hash'       => ['nullable', new ExistsByHash(Office::class)]
        ])->validate();

        $deliveries = $this->initQuery($request);

        $countPaid = $this->initQuery($request);
        $countPaid = $countPaid->whereNotNull('paid')->count();

        $countUnpaid = $this->initQuery($request);
        $countUnpaid = $countUnpaid->whereNull('paid')->count();

        if (! empty($request['paid'])) {
            ($request['paid'] === 'true') ? $deliveries->whereNotNull('paid') : $deliveries->whereNull('paid');
        }

        $deliveries->with('originCity', 'destinationCity', 'originOffice', 'destinationOffice', 'service', 'items.price');

        if ($request['sorting'] === 'latest') {
            $deliveries->orderBy('created_at', 'desc');
        } elseif ($request['sorting'] === 'closest-due') {
            $deliveries->orderBy('payment_deadline_date', 'asc');
        }

        $deliveries = $deliveries->paginate((empty($request['per_page'])) ? 15 : $request['per_page']);

        return [
            'metadata'  => [
                'paid'      => $countPaid,
                'unpaid'    => $countUnpaid,
            ],
            'data'      => $deliveries
        ];
    }

    private function initQuery($request)
    {
        $from = Carbon::createFromFormat('Y-m-d', $request['date_from'])->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $request['date_to'])->endOfDay();

        $model = Delivery::whereBetween('created_at', [$from, $to]);

        if (! empty($request['office_hash'])) {
            $office = Office::byHash($request['office_hash']);
            $model->where(fn ($query) => $query->where('origin_office_id', $office->id)->orWhere('destination_office_id', $office->id));
        }

        if (! empty($request['code'])) {
            $model->where('code', $request['code']);
        }
        if (! empty($request['receipt'])) {
            $model->whereHas('items', function ($query) use ($request) {
                $query->where('receipt', $request['receipt']);
            });
        }

        if (! empty($request['status'])) {
            $model->whereHas('items', function ($query) use ($request) {
                $query->where('status', $request['status']);
            });
        }

        if (! empty($request['payment_method'])) {
            $model->where('payment_method', $request['payment_method']);
        }

        if (! empty($request['taken'])) {
            ($request['taken'] === 'true') ? $model->whereNotNull('taken') : $model->whereNull('taken');
        }

        return $model;
    }
}
