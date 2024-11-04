<?php

namespace App\Listeners\Accounting;

use App\Events\Departure\AllowanceWillBeDeleted;
use App\Actions\Departure\Allowance\Accounting\DeletePrepaidExpense;

class DeleteDepartureAllowance
{
    public function handle(AllowanceWillBeDeleted $event)
    {
        DeletePrepaidExpense::dispatchNow($event->departure, $event->allowance, $event->staff);
    }
}
