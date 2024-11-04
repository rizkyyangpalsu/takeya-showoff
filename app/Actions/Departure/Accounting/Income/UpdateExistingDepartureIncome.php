<?php

namespace App\Actions\Departure\Accounting\Income;

use App\Models\Departure;
use App\Models\Office\Staff;
use App\Concerns\BasicResponse;
use App\Models\Accounting\Journal;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Jobs\Accounting\Journal\UpdateExistingJournal;

class UpdateExistingDepartureIncome extends HandleDepartureIncomeForm
{
    use AsAction, BasicResponse;

    /**
     * @param array $input
     * @param Departure $departure
     * @param Journal $journal
     * @param Staff $staff
     * @return Journal
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(array $input, Departure $departure, Journal $journal, Staff $staff): Journal
    {
        $data = $this->mapIntoJournalEntries($input);

        $job = new UpdateExistingJournal($data, $journal, $staff, $departure);

        dispatch_sync($job);

        return $job->journal;
    }

    /**
     * @param ActionRequest $request
     * @param Departure $departure
     * @param Journal $journal
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    public function asController(ActionRequest $request, Departure $departure, Journal $journal): JsonResponse
    {
        abort_if(! $departure->journals()->where('id', $journal->id)->exists(), 404);

        $inputs = $request->validated();

        $staff = $request->user() instanceof Staff ? $request->user() : Staff::byHash(Staff::idToHash($request->user()->id));

        $journal = $this->handle($inputs, $departure, $journal, $staff);

        return $this->success($journal->toArray());
    }
}
