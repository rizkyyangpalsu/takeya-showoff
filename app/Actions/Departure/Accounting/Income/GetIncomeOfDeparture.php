<?php

namespace App\Actions\Departure\Accounting\Income;

use App\Models\Departure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

class GetIncomeOfDeparture
{
    use AsAction;

    public function asController(ActionRequest $request, Departure $departure): LengthAwarePaginator
    {
        return $departure
            ->journals()
            ->with(['entries.author', 'entries.account', 'office'])
            ->whereHas(
                'entries',
                fn (Builder $builder) => $builder->whereHas(
                    'account',
                    fn (Builder $builder) => $builder->whereIn('code', [
                        config('tiara.accounting.unearned_revenue'),
                        config('tiara.accounting.revenue'),
                    ])
                )
            )
            ->latest()
            ->paginate($request->input('per_page', 15));
    }
}
