<?php

namespace App\Listeners\Accounting;

use Illuminate\Support\Facades\Bus;
use App\Models\Customer\Transaction;
use App\Events\Departure\DepartureStatusChanged;
use App\Actions\Transaction\Accounting\RecordRevenueRealization;

class RecordTransactionRevenueRealization
{
    public function handle(DepartureStatusChanged $event): void
    {
        $chains = $event->departure
            ->reservation
            ->transactions()
            ->cursor()
            ->map(fn (Transaction $transaction) => RecordRevenueRealization::makeJob($transaction, $event->user))
            ->toArray();

        if (count($chains)) {
            Bus::chain($chains)->dispatch();
        }
    }
}
