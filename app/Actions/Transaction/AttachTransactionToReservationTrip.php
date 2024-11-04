<?php

namespace App\Actions\Transaction;

use App\Support\Schedule\Item;
use App\Models\Customer\Transaction;
use Illuminate\Database\Query\Builder;
use App\Models\Schedule\Reservation\Trip;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Events\Transaction\TransactionOccurred;

class AttachTransactionToReservationTrip
{
    use AsAction;

    public function handle(Transaction $transaction, Item $item): void
    {
        $transaction->reservation->trips()
            ->where('schedule_reservation_trips.index', '>=', fn (Builder $builder) => $builder->select('index')
                ->where('origin_id', $item->departureId)
                ->where('schedule_reservation_trips.reservation_id', $transaction->reservation->id)
                ->from('schedule_reservation_trips'))
            ->where('schedule_reservation_trips.index', '<=', fn (Builder $builder) => $builder->select('index')
                ->where('destination_id', $item->destinationId)
                ->where('schedule_reservation_trips.reservation_id', $transaction->reservation->id)
                ->from('schedule_reservation_trips'))
            ->cursor()
            ->each(fn (Trip $trip) => $trip->transactions()->attach($transaction->id));
    }

    public function asListener(TransactionOccurred $event): void
    {
        $this->handle($event->transaction, $event->item);
    }
}
