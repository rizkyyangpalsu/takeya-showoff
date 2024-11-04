<?php

namespace App\Actions\Departure\Allowance\Accounting;

use App\Models\Departure;
use App\Models\Office\Staff;
use App\Models\Accounting\Journal;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Support\Accounting\AccountResolver;
use Dentro\Accounting\Ledger\Recorder;

class AdjustPrepaidExpense
{
    use AsAction, AccountResolver;

    /**
     * @param Departure $departure
     * @param Departure\Allowance $allowance
     * @param Staff $staff
     * @param array $additionalData
     * @throws \Dentro\Accounting\Exceptions\NotBalanceJournalEntryException
     */
    public function handle(Departure $departure, Departure\Allowance $allowance, Staff $staff, array $additionalData = []): void
    {
        /** @var Recorder $ledger */
        $ledger = app(Recorder::class);

        $cash = $this->accountByCode(config('tiara.accounting.cash'));
        $prepaidExpense = $this->accountByCode(config('tiara.accounting.prepaid_expenses'));

        /** @var Journal $journal */
        $journal = $allowance->journals()->first();

        $ledger->credit($cash, $allowance->amount, $staff)
            ->debit($prepaidExpense, $allowance->amount, $staff)
            ->updateRecord($journal, $allowance, $additionalData['memo'] ?? null, $additionalData['ref'] ?? null, $allowance->office->id);

        if (count($additionalData = Arr::except($additionalData, ['ref', 'memo'])) > 0) {
            collect($additionalData)->each(fn ($value, $key) => $journal->{$key} = $value);
            $journal->save();
        }
    }

    /**
     * @param Departure $departure
     * @param Departure\Allowance $allowance
     * @param Staff $staff
     * @param array $additionalData
     * @throws \Dentro\Accounting\Exceptions\NotBalanceJournalEntryException
     */
    public function asJob(Departure $departure, Departure\Allowance $allowance, Staff $staff, array $additionalData = []): void
    {
        $this->handle($departure, $allowance, $staff, $additionalData);
    }
}
