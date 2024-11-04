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

class CreateNewDepartureCombinedIncome extends HandleDepartureIncomeForm
{
    use AsAction, BasicResponse;

    /**
     * @param array $input
     * @param Departure\Combined $combined
     * @param Staff $staff
     * @return Journal
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(array $input, Departure\Combined $combined, Staff $staff): Journal
    {
        $data = $this->mapIntoJournalEntries($input);
        $job = new CreateNewJournal($data, $staff, $combined);

        dispatch_sync($job);

        $job->journal->note = trans('tiara.note.departure.income', [
            'name' => $combined->name,
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
    public function asController(ActionRequest $request, Departure\Combined $combined): JsonResponse
    {
        $inputs = $request->validated();

        $staff = $request->user() instanceof Staff ? $request->user() : Staff::byHash(Staff::idToHash($request->user()->id));

        $journal = $this->handle($inputs, $combined, $staff);

        $combined->total_incomes += $inputs['amount'];
        $combined->save();

        return $this->success($journal->toArray());
    }
}
