<?php

namespace App\Support\Transaction\Reversal;

use App\Models\Customer\Transaction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransactionPassengersRepository
{
    public function __construct(
        public Transaction $bookingTransaction,
        public EloquentCollection $deletingPassengers,
    ) {
    }

    public function getPassengerFees(): Collection
    {
        $items = $this->bookingTransaction->items;

        return $this->deletingPassengers->map(static function (Transaction\Passenger $passenger) use ($items) {
            /** @see \App\Actions\Transaction\CreateNewTransaction L 112-128 */
            $ticketFee = $items
                ->filter(fn (Transaction\Item $item) => Str::contains($item->name, $passenger->seat_code))
                ->first()?->amount;

            if (empty($ticketFee)) {
                Log::debug($items);
                dd();
                /** @var \App\Models\Customer\Transaction\Item $unionItemFee */
                $unionItemFee = $items->where('name', 'ticket')->firstOrFail();

                $ticketFee = $unionItemFee->amount;
            }

            return new PassengerFee($passenger, $ticketFee);
        });
    }
}
