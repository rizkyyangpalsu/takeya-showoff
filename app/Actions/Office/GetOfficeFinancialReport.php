<?php

namespace App\Actions\Office;

use App\Models\Office;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Models\Departure\Allowance;
use JetBrains\PhpStorm\ArrayShape;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

class GetOfficeFinancialReport
{
    use AsAction;

    #[ArrayShape(['start_date' => 'string', 'end_date' => 'string'])]
    public function rules(): array
    {
        return [
            'start_date' => 'nullable|string|date_format:Y-m-d',
            'end_date' => 'nullable|string|date_format:Y-m-d',
        ];
    }

    public function asController(ActionRequest $request): LengthAwarePaginator
    {
        $request->validate();

        $filterDate = static function (Builder $query) use ($request) {
            $query->when($request->input('start_date'), fn (Builder $builder, $startDate) => $builder->whereDate('created_at', '>=', $startDate));

            $query->when($request->input('end_date'), fn (Builder $builder, $endDate) => $builder->whereDate('created_at', '<=', $endDate));

            return $query;
        };

        $query = Office::query()
            ->addSelect([
                'income' => $filterDate(Journal::query()
                    ->whereColumn(DB::raw('cast(group_code as bigint)'), '=', 'offices.id')
                    ->whereHas(
                        'entries',
                        fn (Builder $builder) => $builder->whereHas(
                            'account',
                            fn (Builder $builder) => $builder->where('type_code', Account::TYPE_REVENUE)
                        )
                    ))
                    ->selectRaw('sum(amount)'),
            ])
            ->addSelect([
                'expense' => $filterDate(Journal::query()
                    ->whereColumn(DB::raw('cast(group_code as bigint)'), '=', 'offices.id')
                    ->whereHas(
                        'entries',
                        fn (Builder $builder) => $builder->whereHas(
                            'account',
                            fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE)
                        )
                    ))
                    ->selectRaw('sum(amount)'),
            ])
            ->addSelect([
                'allowances' => $filterDate(Allowance::query()->whereColumn('office_id', '=', 'offices.id'))
                    ->selectRaw('sum(amount)'),
            ]);

        return $query->paginate($request->input('per_page', 15));
    }
}
