<?php

namespace App\Actions\Transaction\Passenger;

use App\Events\Transaction\TransactionReversalCreated;
use App\Models\Customer\Transaction;
use Lorisleiva\Actions\Concerns\AsAction;

class DegenerateTicketCode
{
    use AsAction;

    public function handle(Transaction $transaction): void
    {
        $transaction->passengers()->cursor()
            ->each(fn (Transaction\Passenger $passenger) => $passenger->setAttribute('ticket_code', null) && $passenger->save());
    }

    public function asListener(TransactionReversalCreated $event)
    {
        $this->handle($event->transaction);
    }
}
