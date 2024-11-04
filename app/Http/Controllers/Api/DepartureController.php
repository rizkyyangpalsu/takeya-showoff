<?php

namespace App\Http\Controllers\Api;

use App\Models\Fleet;
use App\Models\Departure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Route\Track\Point;
use Illuminate\Http\JsonResponse;
use App\Models\Accounting\Account;
use App\Http\Controllers\Controller;
use App\Models\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\Departures\CreateNewDeparture;
use App\Jobs\Departures\DeleteExistingDeparture;
use App\Jobs\Departures\UpdateExistingDeparture;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartureController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        $query = Departure::query();

        $this->applyFilter($query, $request);
        $this->applySort($query, $request);

        $data = $query->paginate($request->input('per_page', 15));

        /** @noinspection PhpUndefinedMethodInspection */
        collect($data->items())->each->load('origin', 'destination', 'fleet', 'reservation', 'crews.staff');

        return $data;
    }

    public function show(Departure $departure): JsonResponse
    {
        $aggregates = [
            'allowances_sum' => (float) $departure->allowances()->sum('amount'),
            'passengers_count' => $departure->reservation?->passengers?->count() ?? 0,
        ];

        if ($departure->origin_id !== null && $departure->destination_id !== null) {
            $aggregates['passengers_count'] = $departure->reservationPassengers()?->count() ?? 0;
        }

        return $this->success(
            array_merge(
                $departure
                    ->load('origin', 'destination', 'fleet', 'reservation', 'crews.staff')
                    ->toArray(),
                $aggregates
            )
        );
    }

    public function summary(Departure $departure): JsonResponse
    {
        if (! $departure->reservation) {
            return $this->success([]);
        }

        $transactionQuery = $departure->reservationTransaction();

        $aggregates = [
            'allowances_sum' => (float) $departure->loadSum('allowances', 'amount')->allowances_amount_sum,
            'costs_sum' => (float) $departure->journals()->whereHas(
                'entries',
                fn (Builder $builder) => $builder->whereHas(
                    'account',
                    fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE)
                )
            )->sum('amount'),
            'income_sum' => (float) $departure->journals()->whereHas(
                'entries',
                fn (Builder $builder) => $builder->whereHas(
                    'account',
                    fn (Builder $builder) => $builder->where('code', config('tiara.accounting.revenue'))
                )
            )->sum('amount'),
            'agent_commissions_sum' => $this->getAgentCommission($transactionQuery, true),
            'transactions_sum' => (float) $transactionQuery?->sum('total_price'),
        ];

        return $this->success($aggregates);
    }

    public function summaryDetail(Departure $departure): JsonResponse
    {
        if (! $departure->reservation) {
            return $this->success([]);
        }

        $transactionQuery = $departure->reservationTransaction();

        $aggregates = [
            'allowances' => $departure->load('allowances')->allowances,
            'costs' => $departure->journals()->with('office')->whereHas(
                'entries',
                fn (Builder $builder) => $builder->whereHas(
                    'account',
                    fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE)
                )
            )->latest()->get(),
            'incomes' => $departure->journals()->with('office')->whereHas(
                'entries',
                fn (Builder $builder) => $builder->whereHas(
                    'account',
                    fn (Builder $builder) => $builder->where('code', config('tiara.accounting.revenue'))
                )
            )->latest()->get(),
            'agent_commissions' => $this->getAgentCommission($transactionQuery),
            'transactions_sum' => (float) $transactionQuery?->sum('total_price'),
        ];

        return $this->success($aggregates);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException|\Throwable
     */
    public function store(Request $request): JsonResponse
    {
        $job = new CreateNewDeparture(
            $request->has('reservation_hash') ? Reservation::byHashOrFail($request->input('reservation_hash')) : null,
            $request->has('fleet_hash') && $request->input('fleet_hash') ? Fleet::byHashOrFail($request->input('fleet_hash')) : null,
            $request->has('origin_hash') ? Point::byHashOrFail($request->input('origin_hash')) : null,
            $request->has('destination_hash') ? Point::byHashOrFail($request->input('destination_hash')) : null,
            $request->all(),
        );

        $this->dispatch($job);

        return $this->success($job->departure->load('origin', 'destination', 'fleet', 'reservation', 'crews.staff')->toArray());
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Departure $departure
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Departure $departure): JsonResponse
    {
        $job = new UpdateExistingDeparture(
            $departure,
            $request->has('reservation_hash') ? Reservation::byHashOrFail($request->input('reservation_hash')) : null,
            $request->has('fleet_hash') ? Fleet::byHashOrFail($request->input('fleet_hash')) : null,
            $request->has('origin_hash') ? Point::byHashOrFail($request->input('origin_hash')) : null,
            $request->has('destination_hash') ? Point::byHashOrFail($request->input('destination_hash')) : null,
            $request->all()
        );

        $this->dispatch($job);

        return $this->success($job->departure->load(['origin', 'destination', 'fleet', 'reservation', 'crews.staff'])->toArray());
    }

    public function destroy(Departure $departure): JsonResponse
    {
        $job = new DeleteExistingDeparture($departure);

        $this->dispatchSync($job);

        return $this->success($job->departure->toArray());
    }

    private function getAgentCommission($query, $getTotal = false)
    {
        $sumCommission = 0;
        $entries = [];
        $transactions = $query->with('journals.entries', function ($q) {
            $q->whereHas('account', function ($builder) {
                $builder->where('code', config('tiara.accounting.commission'));
            });
        })->with('journals.office')->get();

        // Mapping data
        foreach ($transactions as $transaction) {
            if (count($transaction->journals) >= 1) {
                foreach ($transaction->journals as $journal) {
                    if (count($journal->entries) >= 1) {
                        foreach ($journal->entries as $entry) {
                            $entry->office = $journal->office;
                            $entries[] = $entry;
                            $sumCommission = $sumCommission + $entry->amount;
                        }
                    }
                }
            }
        }

        return $getTotal ? $sumCommission : $entries;
    }

    private function applyFilter(Builder $query, Request $request): void
    {
        $inputs = $request->validate([
            'keyword' => ['nullable', 'string'],
            'departure_date' => ['nullable'],
            'arrival_date' => ['nullable'],
            'status' => ['nullable', Rule::in(Departure::getDepartureStatus())],
            'type' => ['nullable', Rule::in(Departure::getDepartureTypes())],
        ]);

        $query->when($inputs['status'] ?? false, fn (Builder $query, $status) => $query->where('status', $status));
        $query->when($inputs['type'] ?? false, fn (Builder $query, $type) => $query->where('type', $type));
        $query->when($inputs['departure_date'] ?? false, fn (Builder $query, $departure_date) => $query->whereDate('departure_time', Carbon::parse($departure_date)));
        $query->when($inputs['arrival_date'] ?? false, fn (Builder $query, $arrival_date) => $query->whereDate('arrival_time', Carbon::parse($arrival_date)));

        $keyword = $inputs['keyword'] ?? null;
        $query->when(
            array_key_exists('keyword', $inputs),
            fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->orWhere('name', 'like', '%'.$keyword.'%')
                    ->orWhereHas('origin', fn (Builder $builder) => $builder->where('name', 'like', '%'.$keyword.'%'))
                    ->orWhereHas('destination', fn (Builder $builder) => $builder->where('name', 'like', '%'.$keyword.'%'))
                    ->orWhereHas('reservation', fn (Builder $builder) => $builder
                        ->Where('code', 'like', '%'.$keyword.'%'))
                    ->orWhereHas('fleet', fn (Builder $builder) => $builder->where(
                        fn (Builder $builder) => $builder
                            ->orWhere('manufacturer', 'like', '%'.$keyword.'%')
                            ->orWhere('license_plate', 'like', '%'.$keyword.'%')
                            ->orWhere('model', 'like', '%'.$keyword.'%')
                    ))
                    ->orWhereHas('crews.staff', fn (Builder $builder) => $builder
                        ->Where('name', 'like', '%'.$keyword.'%'))
            )
        );
    }
}
