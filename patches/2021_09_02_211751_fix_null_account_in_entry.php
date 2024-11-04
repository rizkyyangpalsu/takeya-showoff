<?php

use App\Models\Accounting\Account;
use Dentro\Accounting\Entities\Journal\Entry;
use Jalameta\Patcher\Patch;

class FixNullAccountInEntry extends Patch
{
    /**
     * Run patch script.
     *
     * @return void
     */
    public function patch()
    {
        Account::onlyTrashed()->get()
            ->each(fn (Account $account) => Entry::query()->where('account_id', $account->id)->cursor()
                ->each(fn (Entry $entry) => $entry->update([
                    'account_id' => $account->id % 130
                ])));
    }
}
