<?php

namespace App\Actions\Transaction\Accounting;

use App\Models\Accounting\Journal;
use App\Models\User;
use App\Models\Office;
use App\Models\Customer\Transaction;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Support\Accounting\AccountResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Dentro\Accounting\Ledger\Recorder;

class RecordUnearnedRevenue implements ShouldQueue
{
    use AsAction, AccountResolver;

    /**
     * @param Transaction $transaction
     * @param User $user
     * @param Office|null $office
     * @param array $additionalData
     * @throws \Dentro\Accounting\Exceptions\NotBalanceJournalEntryException
     */
    public function handle(Transaction $transaction, User $user, ?Office $office = null, array $additionalData = []): void
    {
        /** @var Recorder $ledger */
        $ledger = app(Recorder::class);

        $unearnedRevenue = $this->accountByCode(config('tiara.accounting.unearned_revenue'));
        $cash = $this->accountByCode(config('tiara.accounting.cash'));
        $commission = $this->accountByCode(config('tiara.accounting.commission'));

        $total_price = $transaction->total_price;
        if ($user->additional_data && $user->additional_data['fee_percentage']) {
            $fee_agent = $total_price * ($user->additional_data['fee_percentage']/100);
        } else {
            $fee_agent = $total_price * 0.1;
        }

        /** @var Journal $journal */
        $journal = $ledger->credit($commission, $fee_agent, $user)
            ->credit($unearnedRevenue, $transaction->total_price - $fee_agent, $user)
            ->debit($cash, $transaction->total_price, $user)
            ->record($transaction, $additionalData['memo'] ?? null, $additionalData['ref'] ?? null, optional($office)->id);

        if (count($additionalData = Arr::except($additionalData, ['ref', 'memo'])) > 0) {
            collect($additionalData)->each(fn ($value, $key) => $journal->{$key} = $value);
            $journal->save();
        }
    }

    /**
     * @param Transaction $transaction
     * @param User $user
     * @param Office|null $office
     * @param array $additionalData
     * @throws \Dentro\Accounting\Exceptions\NotBalanceJournalEntryException
     */
    public function asJob(Transaction $transaction, User $user, Office $office = null, array $additionalData = []): void
    {
        $this->handle($transaction, $user, $office, $additionalData);
    }
}
