<?php

namespace App\Listeners\Accounting;

use App\Events\Departure\DepartureStatusChanged;
use App\Actions\Departure\Allowance\Accounting\BalanceCashWithPrepaidExpense;

class BalanceDepartureCostWithAllowance
{
    /**
     * @param DepartureStatusChanged $event
     */
    public function handle(DepartureStatusChanged $event)
    {
        BalanceCashWithPrepaidExpense::dispatch($event->departure, $event->user);
    }
}
