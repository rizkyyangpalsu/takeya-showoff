<?php

namespace App\Actions\Transaction;

use App\Events\Transaction\TransactionExpired;
use App\Models\Customer\Transaction;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class SetTransactionToExpired
{
    use AsAction;

    public string $commandSignature = 'transaction:scan-expired';

    /**
     * @param Transaction $transaction
     * @return Transaction
     * @throws \Throwable
     */
    public function handle(Transaction $transaction): Transaction
    {
        throw_if($transaction->status !== Transaction::STATUS_PENDING, ValidationException::withMessages([
            'transaction' => __('transaction is not in the pending state!'),
        ]));

        $transaction->status = Transaction::STATUS_EXPIRED;
        $transaction->save();

        event(new TransactionExpired($transaction));

        return $transaction;
    }

    /**
     * @param \Illuminate\Console\Command $command
     * @throws \Throwable
     */
    public function asCommand(Command $command): void
    {
        Transaction::query()
            ->whereDoesntHave('attachments')
            ->where('status', Transaction::STATUS_PENDING)
            ->where('expired_at', '<', now())
            ->cursor()
            ->each(fn (Transaction $transaction) => $this->handle($transaction))
            ->each(fn (Transaction $transaction) => $command->info('transaction : '.$transaction->id.' from '.$transaction->user->name.' has been set to expired!'));
    }
}
