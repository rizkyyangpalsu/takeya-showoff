<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Controller;
use App\Models\Logistic\Delivery;
use App\Models\Logistic\Item;
use App\Models\Logistic\Manifest;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class ReportController extends Controller
{
    public function getTodayData(Request $request)
    {
        $officeId = (! empty($request->office_hash)) ? Office::hashToId($request->office_hash) : null;

        $manifests = Manifest::whereDate('created_at', Carbon::today());
        if ($officeId !== null) {
            $manifests->where(fn ($query) => $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId));
        }

        $manifests = $manifests->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->each->setAppends([]);


        $items = Item::whereDate('created_at', Carbon::today());
        if ($officeId !== null) {
            $items->whereHas('lastManifest', fn ($query) => $query->where('destination_office_id', $officeId));
        }
        $items = $items->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->each->setAppends([]);


        $codSender = Delivery::whereDate('created_at', Carbon::today());
        if ($officeId !== null) {
            $codSender->where(fn ($query) => $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId));
        }
        $codSender = $codSender->where('payment_method', 'cod')->where('payment_by', 'sender')->count();

        $codRecipient = Delivery::whereDate('created_at', Carbon::today());
        if ($officeId !== null) {
            $codRecipient->where(fn ($query) => $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId));
        }
        $codRecipient = $codRecipient->where('payment_method', 'cod')->where('payment_by', 'recipient')->count();

        $creditSender = Delivery::whereDate('created_at', Carbon::today());
        if ($officeId !== null) {
            $creditSender->where(fn ($query) => $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId));
        }
        $creditSender = $creditSender->where('payment_method', 'credit')->where('payment_by', 'sender')->count();

        $creditRecipient = Delivery::whereDate('created_at', Carbon::today());
        if ($officeId !== null) {
            $creditRecipient->where(fn ($query) => $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId));
        }
        $creditRecipient = $creditRecipient->where('payment_method', 'credit')->where('payment_by', 'recipient')->count();

        return $this->success([
            'manifests' => $manifests,
            'deliveries' => [
                [
                    'status'    => 'Pembayaran di tempat',
                    'total'     =>  $codSender,
                ],
                [
                    'status'    => 'Pembayaran di tujuan',
                    'total'     =>  $codRecipient,
                ],
                [
                    'status'    => 'Kredit oleh Pengirim',
                    'total'     =>  $creditSender,
                ],
                [
                    'status'    => 'Kredit oleh Penerima',
                    'total'     =>  $creditRecipient,
                ],
            ],
            'items' => $items
        ]);
    }

    public function getRangeData(Request $request)
    {
        $request->validate([
            'date_from'     => 'required|date_format:Y-m-d',
            'date_to'       => 'required|date_format:Y-m-d',
            'group'         => 'required|in:1,7,30',
            'office_hash'   => ['nullable', new ExistsByHash(Office::class)]
        ]);

        $officeId = (! empty($request->office_hash)) ? Office::hashToId($request->office_hash) : null;

        $from = Carbon::createFromFormat('Y-m-d', $request['date_from'])->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $request['date_to'])->endOfDay();

        $codSender = Delivery::whereBetween('created_at', [$from, $to]);
        if ($officeId !== null) {
            $codSender->where(fn ($query) => $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId));
        }
        $codSender = $codSender->where('payment_method', 'cod')->where('payment_by', 'sender')->count();

        $codRecipient = Delivery::whereBetween('created_at', [$from, $to]);
        if ($officeId !== null) {
            $codRecipient->where(fn ($query) => $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId));
        }
        $codRecipient = $codRecipient->where('payment_method', 'cod')->where('payment_by', 'recipient')->count();

        $creditSender = Delivery::whereBetween('created_at', [$from, $to]);
        if ($officeId !== null) {
            $creditSender->where(fn ($query) => $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId));
        }
        $creditSender = $creditSender->where('payment_method', 'credit')->where('payment_by', 'sender')->count();

        $creditRecipient = Delivery::whereBetween('created_at', [$from, $to]);
        if ($officeId !== null) {
            $creditRecipient->where(fn ($query) => $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId));
        }
        $creditRecipient = $creditRecipient->where('payment_method', 'credit')->where('payment_by', 'recipient')->count();

        $items = Item::whereBetween('created_at', [$from, $to]);
        if ($officeId !== null) {
            $items->whereHas('lastManifest', fn ($query) => $query->where('destination_office_id', $officeId));
        }
        $items = $items->select('price_title_type', DB::raw('count(*) as total'))
            ->groupBy('price_title_type')
            ->orderBy('total', 'desc')
            ->get()
            ->each->setAppends([]);

        $deliveries = Delivery::whereBetween('created_at', [$from, $to]);
        if ($officeId !== null) {
            $deliveries->where(fn ($query) => $query->where('origin_office_id', $officeId)->orWhere('destination_office_id', $officeId));
        }
        $deliveries = $deliveries->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(total_cost) as total_cost')
        )
            ->groupBy('date')
            ->get()
            ->each->setAppends([]);

        $date = $from;
        $dataDeliveries = [];
        $tempData = [];
        $itteration = 1;
        while ($date->format('Y-m-d') !== Carbon::createFromFormat('Y-m-d', $request['date_to'])->addDays(1)->format('Y-m-d')) {
            $key = array_search($date->format('Y-m-d'), array_column($deliveries->toArray(), 'date'));
            if (empty($tempData)) {
                $tempData = [
                    'total' =>  ($key === false) ? 0 : $deliveries[$key]['total'],
                    'total_cost' =>  ($key === false) ? 0 : $deliveries[$key]['total_cost'],
                ];
            } else {
                $tempData['total'] = $tempData['total'] + (($key === false) ? 0 : $deliveries[$key]['total']);
                $tempData['total_cost'] = $tempData['total_cost'] + (($key === false) ? 0 : $deliveries[$key]['total_cost']);
            }
            if ($itteration === 1) {
                $tempData['from'] = $date->format('Y-m-d');
            }
            if ($itteration % $request['group'] === 0 || $date->format('Y-m-d') === $to->format('Y-m-d')) {
                $tempData['to'] = $date->format('Y-m-d');
                $dataDeliveries[] = $tempData;
                $tempData = [];
                $itteration = 1;
            } else {
                $itteration++;
            }
            $date = $date->addDays(1);
        }

        return $this->success([
            'deliveries_payment' => [
                [
                    'status'    => 'Pembayaran di tempat',
                    'total'     =>  $codSender,
                ],
                [
                    'status'    => 'Pembayaran di tujuan',
                    'total'     =>  $codRecipient,
                ],
                [
                    'status'    => 'Kredit oleh Pengirim',
                    'total'     =>  $creditSender,
                ],
                [
                    'status'    => 'Kredit oleh Penerima',
                    'total'     =>  $creditRecipient,
                ],
            ],
            'items' =>  $items,
            'deliveries_statistic' =>  $dataDeliveries,
        ]);
    }
}
