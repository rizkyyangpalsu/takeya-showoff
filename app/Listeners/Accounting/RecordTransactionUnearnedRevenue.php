<?php

namespace App\Listeners\Accounting;

use App\Events\Transaction\TransactionPaid;
use App\Actions\Transaction\Accounting\RecordUnearnedRevenue;

class RecordTransactionUnearnedRevenue
{
    public function handle(TransactionPaid $event): void
    {
        RecordUnearnedRevenue::dispatch($event->transaction, $event->user, $event->office, [
            'note' => trans('tiara.note.transaction', [
                'name' => $event->transaction?->user?->name,
                'user_type' => $event->transaction?->user?->user_type,
            ]),
            'ref' => $event->transaction->code,
        ]);
    }
}
