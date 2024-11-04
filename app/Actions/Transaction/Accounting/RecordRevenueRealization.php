<?php

namespace App\Actions\Transaction\Accounting;

use App\Models\User;
use App\Models\Customer\Transaction;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Support\Accounting\AccountResolver;
use Dentro\Accounting\Ledger\Recorder;

class RecordRevenueRealization
{
    use AsAction, AccountResolver;

    /**
     * @param Transaction $transaction
     * @param User $user
     * @throws \Dentro\Accounting\Exceptions\NotBalanceJournalEntryException
     */
    public function handle(Transaction $transaction, User $user)
    {
        /** @var Recorder $ledger */
        $ledger = app(Recorder::class);

        $unearnedRevenue = $this->accountByCode(config('tiara.accounting.unearned_revenue'));
        $revenue = $this->accountByCode(config('tiara.accounting.revenue'));
        $commission = $this->accountByCode(config('tiara.accounting.commission'));

        $total_price = $transaction->total_price;
        if ($user->additional_data && $user->additional_data['fee_percentage']) {
            $fee_agent = $total_price * ($user->additional_data['fee_percentage']/100);
        } else {
            $fee_agent = $total_price * 0.1;
        }

        $ledger->credit($revenue, $transaction->total_price - $fee_agent, $user)
            ->debit($unearnedRevenue, $transaction->total_price, $user)
            ->record($transaction, null, null, $transaction->office->id);
    }

    /**
     * @param Transaction $transaction
     * @param User $user
     * @throws \Dentro\Accounting\Exceptions\NotBalanceJournalEntryException
     */
    public function asJob(Transaction $transaction, User $user)
    {
        $this->handle($transaction, $user);
    }
}
