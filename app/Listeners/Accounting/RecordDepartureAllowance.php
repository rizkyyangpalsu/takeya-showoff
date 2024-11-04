<?php

namespace App\Listeners\Accounting;

use App\Events\Departure\AllowanceAdded;
use App\Actions\Departure\Allowance\Accounting\RecordPrepaidExpenses;

class RecordDepartureAllowance
{
    public function handle(AllowanceAdded $event): void
    {
        RecordPrepaidExpenses::dispatch($event->departure, $event->allowance, $event->staff, [
            'note' => trans('tiara.note.departure.allowance', [
                'name' => $event->departure->name,
                'executor' => $event->allowance->executor->name,
                'receiver' => $event->allowance->receiver->name,
            ]),
            'ref' => $event->departure->name,
        ]);
    }
}
