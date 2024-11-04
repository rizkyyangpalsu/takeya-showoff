<?php

namespace App\Actions\Departure\Passenger;

use App\Models\Customer\Transaction\Passenger;
use App\Models\Departure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;

class GetPassengersFromDeparture
{
    use AsAction;

    /**
     * @param Request $request
     * @param Departure $departure
     * @return LengthAwarePaginator
     */
    public function asController(Request $request, Departure $departure): LengthAwarePaginator
    {
        $query = $departure->reservationPassengers();

        $paginator = $query->paginate($request->input('per_page', 15));

        $passengers = collect($paginator->items());

        $transactions = $departure->reservation->transactions()->with('trips')
            ->whereIn('id', $passengers->pluck('transaction_id')->unique()->values())->get()->keyBy('id');

        $passengers->each(fn (Passenger $passenger) => $passenger->setRelation('transaction', $transactions->get($passenger->transaction_id)));

        return $paginator;
    }
}
