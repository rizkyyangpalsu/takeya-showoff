<?php

namespace App\Actions\Reservation\Trip;

use App\Models\Customer\Transaction;
use App\Models\Schedule\Reservation;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateSeatConfigurationStatus
{
    use AsAction;

    public function handle(Transaction $transaction): void
    {
        $trips = $transaction->trips()
            ->get()
            // set reservation relation for prevent new query while get layout from inside Trip model
            ->map(fn (Reservation\Trip $trip) => $trip->setRelation('reservation', $transaction->reservation));

        $seatIds = $transaction->passengers()->pluck('seat_id')->toArray();

        if ($transaction->type === Transaction::TYPE_REFUND) {
            $trips->each(fn (Reservation\Trip $trip) => $trip->seat_configuration->makeAvailable($seatIds) && $trip->save());

            return;
        }

        switch ($transaction->status) {
            case Transaction::STATUS_PENDING:
                $trips->each(fn (Reservation\Trip $trip) => $trip->seat_configuration->bookSeat(...$seatIds) && $trip->save());
                break;
            case Transaction::STATUS_PAID:
                $trips->each(fn (Reservation\Trip $trip) => $trip->seat_configuration->occupySeat(...$seatIds) && $trip->save());
                break;
            case Transaction::STATUS_DISCARD:
            case Transaction::STATUS_REVERSAL:
            case Transaction::STATUS_EXPIRED:
                $trips->each(fn (Reservation\Trip $trip) => $trip->seat_configuration->makeAvailable($seatIds) && $trip->save());
                break;
        }
    }

    public function asListener($event): void
    {
        if (property_exists($event, 'transaction') && $event->transaction instanceof Transaction) {
            $this->handle($event->transaction);
        }
    }
}
