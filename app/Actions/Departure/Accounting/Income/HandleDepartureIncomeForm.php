<?php

namespace App\Actions\Departure\Accounting\Income;

use App\Models\Office;
use Illuminate\Support\Arr;
use App\Support\Accounting\AccountResolver;
use Veelasky\LaravelHashId\Rules\ExistsByHash;
use Dentro\Accounting\Entities\Journal\Entry;

class HandleDepartureIncomeForm
{
    use AccountResolver;

    public function rules(): array
    {
        return [
            'office_hash' => ['required', new ExistsByHash(Office::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable'],
        ];
    }

    public function mapIntoJournalEntries(array $input): array
    {
        $office = Office::byHash($input['office_hash']);
        $cash = $this->accountByCode(config('tiara.accounting.cash'));
        $revenue = $this->accountByCode(config('tiara.accounting.revenue'));

        return [
            'group_code' => $office->id,
            'entries' => [
                [
                    'amount' => $input['amount'],
                    'type' => Entry::TYPE_CREDIT,
                    'account_hash' => $revenue->getAttribute('hash'),
                    'memo' => Arr::get($input, 'description'),
                ],
                [
                    'amount' => $input['amount'],
                    'type' => Entry::TYPE_DEBIT,
                    'account_hash' => $cash->getAttribute('hash'),
                    'memo' => Arr::get($input, 'description'),
                ],
            ],
        ];
    }
}
