<?php

namespace App\Http\Controllers\Api\Office;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Departure;
use App\Models\Office;
use App\Models\User;
use Dentro\Accounting\Entities\Journal\Entry;
use Dentro\Yalr\Attributes as RA;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[RA\Name('api.office.finance', dotSuffix: true), RA\Prefix('/office/{office_slug}/finance')]
class FinanceController extends Controller
{
    #[RA\Get('', name: 'index')]
    public function index(Request $request, Office $office): LengthAwarePaginator
    {
        $journalQuery = Journal::query()
            ->with('entries.account', 'entries.author')
            ->where('group_code', $office->id)
            ->orderByDesc('created_at');

        $this->applyFilter($journalQuery, $request);

        $journalQuery->when(
            $request->input('type_code', false),
            fn (Builder $journalQuery, $typeCode) => match ($typeCode) {
                'income' => $journalQuery->whereHas('entries', fn (Builder $entryQuery) => $entryQuery->whereHas(
                    'account',
                    fn (Builder $accountQuery) => $accountQuery->where('type_code', Account::TYPE_REVENUE)
                )),
                'expense' => $journalQuery->whereHas('entries', fn (Builder $entryQuery) => $entryQuery->whereHas(
                    'account',
                    fn (Builder $accountQuery) => $accountQuery->where(
                        fn (Builder $builder) => $builder
                        // expense
                        ->where('type_code', Account::TYPE_EXPENSE)
                        // code "kas jalan"
                        // ->orWhere('code', 10402)
                    )
                )),
                default => $journalQuery
            }
        );

        return $journalQuery->paginate($request->input('per_page'));
    }

    #[RA\Get('matrix', name: 'matrix')]
    public function matrix(Request $request, Office $office): JsonResponse
    {
        $queries = collect([
            strtolower(Entry::TYPE_CREDIT) => Entry::query()->where('type', Entry::TYPE_CREDIT),
            strtolower(Entry::TYPE_DEBIT) => Entry::query()->where('type', Entry::TYPE_DEBIT),
            'unearned' => Entry::query()
                ->whereHas(
                    'account',
                    fn (Builder $accountQuery) => $accountQuery->where('code', config('tiara.accounting.unearned_revenue'))
                ),
            'income' => Entry::query()
                ->whereHas(
                    'account',
                    fn (Builder $accountQuery) => $accountQuery->where('type_code', Account::TYPE_REVENUE)
                ),
            'expense' => Entry::query()
                ->whereHas(
                    'account',
                    fn (Builder $accountQuery) => $accountQuery->where('type_code', Account::TYPE_EXPENSE)
                ),
        ]);

        $queries->map(fn (Builder $builder) => $builder
            ->whereHas('journal', function (Builder $builder) use ($request, $office) {
                $builder->where('group_code', $office->id);
                $this->applyFilter($builder, $request);
            }));

        $groupQuery = static fn (Builder $builder) => $builder
            ->whereHas('account')
            ->select('account_id')
            ->selectRaw('SUM(amount) as total_amount')
            ->with('account')
            ->groupBy('account_id');

        $data = $queries
            ->map(fn (Builder $builder) => $groupQuery($builder))
            ->map(fn (Builder $builder) => $builder->get()->each(function (Entry $entry) {
                $entry->makeHidden('account_id');
                $entry->mergeCasts(['total_amount' => 'double']);
                $entry->account->makeHidden(
                    'type_description',
                    'group_code',
                    'group_description',
                    'is_cash',
                    'deleted_at',
                    'created_at',
                    'updated_at',
                    'hash',
                    'balance'
                );
            }));

        $data->put(
            'total',
            $data->map(fn (Collection $collection) => $collection->sum('total_amount'))
        );

        return $this->success($data);
    }

    private function applyFilter(Builder $query, Request $request): void
    {
        $inputs = $request->validate([
            'staff_hash' => 'nullable',
            'departure_hash' => 'nullable',
            'start_date' => 'required_if:end_date,string|date_format:Y-m-d',
            'end_date' => 'required_if:start_date,string|date_format:Y-m-d',
        ]);

        $query->when(
            ($inputs['start_date'] ?? false) && ($inputs['end_date'] ?? false),
            fn (Builder $builder) => $builder
                ->whereDate('created_at', '>=', $inputs['start_date'])
                ->whereDate('created_at', '<=', $inputs['end_date'])
        );

        $query->when(
            $inputs['staff_hash'] ?? null,
            fn (Builder $journalQuery, $staffHash) => $journalQuery
                ->whereHas('entries', fn (Builder $entryQuery) => $entryQuery->where(
                    fn (Builder $subEntryQuery) => $subEntryQuery
                    ->orWhereHasMorph(
                        'author',
                        Office\Staff::class,
                        fn (Builder $authorQuery) => $authorQuery->where('id', Office\Staff::hashToId($staffHash))
                    )->orWhereHasMorph(
                        'author',
                        User::class,
                        fn (Builder $authorQuery) => $authorQuery->where('id', User::hashToId($staffHash))
                    )
                ))
        );

        $query->when(
            $inputs['departure_hash'] ?? null,
            fn (Builder $journalQuery, $departureHash) => $journalQuery->where(
                fn (Builder $journalQuery) => $journalQuery
                    ->orWhereHasMorph(
                        'recordable',
                        Departure::class,
                        fn (Builder $departureQuery) => $departureQuery->where('id', Departure::hashToId($departureHash))
                    )->orWhereHasMorph(
                        'recordable',
                        Departure\Allowance::class,
                        fn (Builder $allowanceQuery) => $allowanceQuery->where('departure_id', Departure::hashToId($departureHash))
                    )
            )
        );
    }
}
