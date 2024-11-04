<?php

namespace App\Actions\Departure\Transaction;

use App\Models\Departure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;

class GetTransactionFromDeparture
{
    use AsAction;

    public function asController(ActionRequest $request, Departure $departure): LengthAwarePaginator
    {
        $query = $departure->reservationTransaction()->with('user');

        return $query->paginate($request->input('per_page'));
    }
}
