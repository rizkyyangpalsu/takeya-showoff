<?php

namespace App\Actions\Departure\Allowance\Accounting;

use App\Models\Departure;
use App\Models\Office\Staff;
use App\Models\Accounting\Journal;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Jobs\Accounting\Journal\DeleteExistingJournal;

class DeletePrepaidExpense
{
    use AsAction;

    public function handle(Departure $departure, Departure\Allowance $allowance, Staff $staff)
    {
        /** @var Journal $journal */
        $journal = $allowance->journals()->first();

        $job = new DeleteExistingJournal($journal);
        dispatch_sync($job);
    }

    /**
     * @param Departure $departure
     * @param Departure\Allowance $allowance
     * @param Staff $staff
     */
    public function asJob(Departure $departure, Departure\Allowance $allowance, Staff $staff)
    {
        $this->handle($departure, $allowance, $staff);
    }
}
