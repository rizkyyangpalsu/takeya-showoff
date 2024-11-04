<?php

namespace App\Actions\Departure\Allowance;

use App\Models\Departure;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;

class GetAllowancesFromDeparture
{
    use AsAction;

    public function asController(Request $request, Departure $departure): LengthAwarePaginatorContract
    {
        return $departure->allowances()->paginate($request->input('per_page', 15));
    }
}
