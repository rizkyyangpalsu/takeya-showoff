<?php

namespace App\Http\Controllers\Api\Schedule;

use App\Jobs\RouteTrack\DuplicateExistingRouteTrack;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\RouteTrack\CreateNewRouteTrack;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Validation\ValidationException;
use App\Jobs\RouteTrack\DeleteExistingRouteTrack;
use App\Jobs\RouteTrack\UpdateExistingRouteTrack;

class TrackController extends Controller
{
    public function index(Request $request): Paginator
    {
        $query = Route::query()->withCount('tracks');

        $query->when(
            $request->input('keyword'),
            fn (Builder $builder, string $keyword) => $builder->where('name', 'like', "%{$keyword}%")
        );

        return $query->paginate($request->input('per_page', 15));
    }

    public function show(Route $route): JsonResponse
    {
        $route->load(['tracks.origin', 'tracks.destination', 'prices']);
        $route->loadCount('tracks');
        $route->append(['prices_table']);

        return $this->success($route->toArray());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $job = new CreateNewRouteTrack($request->all());
        $this->dispatch($job);

        return $this->success(
            $job->route
                ->fresh(['tracks.origin', 'tracks.destination', 'prices'])
                ->loadCount('tracks')
                ->append(['prices_table'])
                ->toArray()
        )->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param Request $request
     * @param Route $route
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, Route $route): JsonResponse
    {
        $job = new UpdateExistingRouteTrack($route, $request->toArray());
        $this->dispatch($job);

        return $this->success(
            $job->route
                ->fresh(['tracks.origin', 'tracks.destination', 'prices'])
                ->loadCount('tracks')
                ->append(['prices_table'])
                ->toArray()
        );
    }

    /**
     * @param Route $route
     * @return JsonResponse
     */
    public function duplicate(Route $route): JsonResponse
    {
        $job = new DuplicateExistingRouteTrack($route);
        $this->dispatch($job);

        return $this->success($job->route->toArray());
    }

    public function destroy(Route $route): JsonResponse
    {
        $job = new DeleteExistingRouteTrack($route);
        $this->dispatch($job);

        return $this->success($job->route->toArray());
    }
}
