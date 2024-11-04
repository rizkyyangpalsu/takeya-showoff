<?php

namespace App\Http\Controllers\Api\Departure;

use App\Models\Departure;
use App\Models\Office\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Jobs\Departures\Crew\UpdateExistingCrew;
use App\Jobs\Departures\Crew\AssignCrewForDeparture;
use App\Jobs\Departures\Crew\RemoveCrewFromDeparture;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CrewController extends Controller
{
    public function index(Departure $departure): LengthAwarePaginator
    {
        return $departure->crews()->paginate();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Departure $departure
     * @param \App\Models\Office\Staff $staff
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, Departure $departure, Staff $staff): JsonResponse
    {
        $job = new AssignCrewForDeparture($departure, $staff, $request->all());

        $this->dispatchSync($job);

        return $this->success($job->crew->fresh(['staff']))->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Departure $departure
     * @param \App\Models\Departure\Crew $crew
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Departure $departure, Departure\Crew $crew): JsonResponse
    {
        $job = new UpdateExistingCrew($departure, $crew, $request->all());

        $this->dispatchSync($job);

        return $this->success($job->crew->fresh(['staff']));
    }

    /**
     * @param \App\Models\Departure $departure
     * @param \App\Models\Departure\Crew $crew
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Departure $departure, Departure\Crew $crew): JsonResponse
    {
        $job = new RemoveCrewFromDeparture($departure, $crew);

        $this->dispatchSync($job);

        return $this->success($job->crew->toArray());
    }
}
