<?php

namespace App\Actions\Departure\Accounting\Income;

use App\Models\Departure;
use App\Concerns\BasicResponse;
use App\Models\Accounting\Journal;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Jobs\Accounting\Journal\DeleteExistingJournal;

class DeleteExistingDepartureIncome
{
    use AsAction, BasicResponse;


    public function asController(ActionRequest $request, Departure $departure, Journal $journal): \Illuminate\Http\JsonResponse
    {
        abort_if(! $departure->journals()->where('id', $journal->id)->exists(), 404);

        $job = new DeleteExistingJournal($journal);
        dispatch_sync($job);

        return $this->success($journal->toArray());
    }
}
