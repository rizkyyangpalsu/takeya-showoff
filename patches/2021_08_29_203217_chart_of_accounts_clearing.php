<?php

use App\Models\Accounting\Account;
use Jalameta\Patcher\Patch;

class ChartOfAccountsClearing extends Patch
{
    /**
     * Run patch script.
     *
     * @return void
     */
    public function patch()
    {
        Account::query()
            ->whereNotNull('group_code')
            ->whereIn('code', Account::query()->whereNull('group_code')->pluck('code'))
            ->get()
            ->each(fn (Account $account) => $account->delete());
    }
}
