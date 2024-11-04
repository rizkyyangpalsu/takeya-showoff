<?php

namespace App\Listeners\Accounting;

use App\Events\Transaction\TransactionCanceled;
use App\Models\Accounting\Journal;

class RemoveCanceledTransactionFromRecord
{
    public function handle(TransactionCanceled $transactionCanceled): void
    {
        $transactionCanceled->transaction->journals()->cursor()->each(fn (Journal $journal) => $journal->delete());
    }
}
