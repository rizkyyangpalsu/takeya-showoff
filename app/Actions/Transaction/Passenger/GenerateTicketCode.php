<?php

namespace App\Actions\Transaction\Passenger;

use App\Models\Customer\Transaction;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Events\Transaction\TransactionPaid;

class GenerateTicketCode
{
    use AsAction;

    private int $counter = 0;

    public function handle(Transaction $transaction)
    {
        $transaction->passengers()->cursor()
            ->each(fn (Transaction\Passenger $passenger) => $passenger->setAttribute('ticket_code', $this->generateTicketCode($passenger)) && $passenger->save());
    }

    public function asListener(TransactionPaid $event)
    {
        $this->handle($event->transaction);
    }

    private function generateTicketCode(Transaction\Passenger $passenger): string
    {
        return 'TRM/'.now()->format('dmy').'/'.$passenger->hash;
    }
}
