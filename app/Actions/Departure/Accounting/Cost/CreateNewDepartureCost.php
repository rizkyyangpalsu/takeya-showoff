<?php

namespace App\Actions\Departure\Accounting\Cost;

use App\Models\Accounting\Journal;
use App\Models\Departure;
use App\Models\Office\Staff;
use App\Concerns\BasicResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Jobs\Accounting\Journal\CreateNewJournal;

class CreateNewDepartureCost extends HandleDepartureCostForm
{
    use AsAction, BasicResponse;

    /**
     * @param array $input
     * @param Departure $departure
     * @param Staff $staff
     * @return Journal
     * @throws ValidationException
     */
    public function handle(array $input, Departure $departure, Staff $staff): Journal
    {
        $data = $this->mapIntoJournalEntries($input);

        $job = new CreateNewJournal($data, $staff, $departure);

        dispatch_sync($job);

        $job->journal->note = trans('tiara.note.departure.cost', [
            'name' => $departure->name,
        ]);

        $job->journal->save();

        return $job->journal;
    }

    /**
     * @param ActionRequest $request
     * @param Departure $departure
     * @return JsonResponse
     * @throws ValidationException
     */
    public function asController(ActionRequest $request, Departure $departure): JsonResponse
    {
        $inputs = $request->validated();

        $staff = $request->user() instanceof Staff ? $request->user() : Staff::byHash(Staff::idToHash($request->user()->id));

        $journal = $this->handle($inputs, $departure, $staff);

        return $this->success($journal->toArray());
    }
}
