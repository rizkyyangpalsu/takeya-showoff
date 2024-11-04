<?php

namespace App\Actions\Departure\Allowance\Accounting;

use App\Models\User;
use App\Models\Departure;
use App\Models\Accounting\Account;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Support\Accounting\AccountResolver;
use App\Jobs\Accounting\Journal\CreateNewJournal;
use Dentro\Accounting\Entities\Journal\Entry;

class BalanceCashWithPrepaidExpense
{
    use AsAction, AccountResolver;

    /**
     * @param Departure $departure
     * @param User $user
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(Departure $departure, User $user)
    {
        $diff = $this->diff($departure);

        $departure->load('journals.entries');

        if ($diff == 0) {
            return;
        }

        $cash = $this->accountByCode(config('tiara.accounting.cash'));
        $prepaidExpense = $this->accountByCode(config('tiara.accounting.prepaid_expenses'));

        $data = [
            'entries' => [
                [
                    'amount' => abs($diff),
                    'type' => $diff > 0 ? Entry::TYPE_CREDIT : Entry::TYPE_DEBIT,
                    'account_hash' => $cash->getAttribute('hash'),
                ],
                [
                    'amount' => abs($diff),
                    'type' => $diff > 0 ? Entry::TYPE_DEBIT : Entry::TYPE_CREDIT,
                    'account_hash' => $prepaidExpense->getAttribute('hash'),
                ],
            ],
        ];

        $job = new CreateNewJournal($data, $user, $departure);

        dispatch($job);
    }

    private function diff(Departure $departure): float
    {
        return $this->getCosts($departure) - $this->getAllowances($departure);
    }

    private function getCosts(Departure $departure): float
    {
        return (float) $departure->journals()->whereHas(
            'entries',
            fn (Builder $builder) => $builder->whereHas(
                'account',
                fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE)
            )
        )->sum('amount');
    }

    private function getAllowances(Departure $departure): float
    {
        return (float) $departure->loadSum('allowances', 'amount')->allowances_sum_amount;
    }
}
