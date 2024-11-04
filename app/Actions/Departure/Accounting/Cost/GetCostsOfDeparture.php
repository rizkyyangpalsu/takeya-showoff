<?php

namespace App\Actions\Departure\Accounting\Cost;

use App\Models\Departure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Models\Accounting\Account;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

class GetCostsOfDeparture
{
    use AsAction;

    public function asController(Request $request, Departure $departure): LengthAwarePaginator
    {
        return $departure
            ->journals()
            ->with(['entries.author', 'entries.account', 'office'])
            ->whereHas(
                'entries',
                fn (Builder $builder) => $builder->whereHas(
                    'account',
                    fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE)
                )
            )
            ->latest()
            ->paginate($request->input('per_page', 15));
    }
}
