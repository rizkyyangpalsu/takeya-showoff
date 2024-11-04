<?php

namespace App\Listeners\Accounting;

use App\Events\Departure\AllowanceUpdated;
use App\Actions\Departure\Allowance\Accounting\AdjustPrepaidExpense;

class AdjustDepartureAllowance
{
    public function handle(AllowanceUpdated $event): void
    {
        AdjustPrepaidExpense::dispatch($event->departure, $event->allowance, $event->staff);
    }
}
