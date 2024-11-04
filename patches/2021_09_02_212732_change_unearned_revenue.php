<?php

use App\Models\Accounting\Account;
use Illuminate\Support\Facades\Artisan;
use Jalameta\Patcher\Patch;
use function PHPUnit\Framework\assertEquals;

class ChangeUnearnedRevenue extends Patch
{
    /**
     * Run patch script.
     *
     * @return void
     */
    public function patch()
    {
        $previousCode = 20105;
        $newCode = 40001;

        $this->command->info('change from '.$previousCode.' to '.$newCode);

        /** @var Account $entry */
        $entry = Account::query()->where('code', $previousCode)->first();

        if (! $entry) {
            $this->command->warn('Skipping...');
            return;
        }

        $entry->update(['code' => $newCode]);

        assertEquals($newCode, $entry->fresh()->code);

        $this->command->info('rebuilding all chart of account');
        Artisan::call('db:seed', [
            '--class' => 'ChartOfAccountsSeeder'
        ]);
    }
}
