<?php

namespace App\Actions\Departure\Accounting\Cost;

use App\Models\Office;
use Illuminate\Support\Arr;
use App\Models\Accounting\Account;
use App\Support\Accounting\AccountResolver;
use Veelasky\LaravelHashId\Rules\ExistsByHash;
use Dentro\Accounting\Entities\Journal\Entry;

class HandleDepartureCostForm
{
    use AccountResolver;

    public function rules(): array
    {
        return [
            'office_hash' => ['required', new ExistsByHash(Office::class)],
            'expenses' => ['required', 'array'],
            'expenses.*.account_hash' => ['required', new ExistsByHash(Account::class)],
            'expenses.*.description' => ['nullable', 'string'],
            'expenses.*.amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function mapIntoJournalEntries(array $input): array
    {
        $office = Office::byHash($input['office_hash']);
        $prepaidExpenses = $this->accountByCode(config('tiara.accounting.prepaid_expenses'));

        $data = [
            'entries' => [],
            'group_code' => $office->id,
        ];

        foreach ($input['expenses'] as $expense) {
            $credit = [
                'amount' => Arr::get($expense, 'amount'),
                'type' => Entry::TYPE_CREDIT,
                'account_hash' => $prepaidExpenses->getAttribute('hash'),
                'memo' => Arr::get($expense, 'description'),
            ];
            $debit = [
                'amount' => Arr::get($expense, 'amount'),
                'type' => Entry::TYPE_DEBIT,
                'account_hash' => $expense['account_hash'],
                'memo' => Arr::get($expense, 'description'),
            ];

            array_push($data['entries'], $credit, $debit);
        }

        return $data;
    }
}
