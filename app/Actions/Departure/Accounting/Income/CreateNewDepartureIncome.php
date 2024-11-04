<?php

namespace App\Actions\Departure\Accounting\Income;

use App\Models\Departure;
use App\Models\Office\Staff;
use App\Concerns\BasicResponse;
use App\Models\Accounting\Journal;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Jobs\Accounting\Journal\CreateNewJournal;

class CreateNewDepartureIncome extends HandleDepartureIncomeForm
{
    use AsAction, BasicResponse;

    /**
     * @param array $input
     * @param Departure $departure
     * @param Staff $staff
     * @return Journal
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(array $input, Departure $departure, Staff $staff): Journal
    {
        $data = $this->mapIntoJournalEntries($input);
        $job = new CreateNewJournal($data, $staff, $departure);

        dispatch_sync($job);

        $job->journal->note = trans('tiara.note.departure.income', [
            'name' => $departure->name,
        ]);

        $job->journal->save();

        return $job->journal;
    }

    /**
     * @param ActionRequest $request
     * @param Departure $departure
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function asController(ActionRequest $request, Departure $departure): JsonResponse
    {
        $inputs = $request->validated();

        $staff = $request->user() instanceof Staff ? $request->user() : Staff::byHash(Staff::idToHash($request->user()->id));

        $journal = $this->handle($inputs, $departure, $staff);

        return $this->success($journal->toArray());
    }
}
