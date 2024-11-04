<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Models\Fleet\Layout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\LayoutSeat\CreateNewLayoutSeat;
use App\Jobs\LayoutSeat\DeleteExistingLayoutSeat;
use App\Jobs\LayoutSeat\UpdateExistingLayoutSeat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LayoutController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        $query = Layout::query();

        $query->when($request->filled('keyword'), function (Builder $builder) {
            $builder->search(\request('keyword'));
        });

        return $query->paginate($request->input('per_page', 15));
    }

    public function show(Layout $layout): JsonResponse
    {
        $layout->load(['seats']);

        return $this->success($layout->toArray());
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|object
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $job = new CreateNewLayoutSeat($request->all());

        $this->dispatch($job);

        return $this->success($job->layout->fresh(['seats']))->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Fleet\Layout $layout
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Layout $layout)
    {
        $job = new UpdateExistingLayoutSeat($layout, $request->all());

        $this->dispatchSync($job);

        return $this->success($job->layout->fresh(['seats']));
    }

    /**
     * @param \App\Models\Fleet\Layout $layout
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Layout $layout)
    {
        $job = new DeleteExistingLayoutSeat($layout);

        $this->dispatchSync($job);

        return $this->success($job->layout->toArray());
    }
}
