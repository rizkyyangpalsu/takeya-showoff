<?php

namespace App\Http\Controllers\Api\Schedule;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Route\Track\Point;
use Illuminate\Http\JsonResponse;
use App\Jobs\Points\CreateNewPoint;
use App\Http\Controllers\Controller;
use App\Jobs\Points\DeleteExistingPoint;
use App\Jobs\Points\UpdateExistingPoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PointController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        $query = Point::query()->with('regency', 'province')->orderBy('code');

        $likeClause = $this->getMatchLikeClause($query);

        $query->when(
            $request->input('keyword'),
            fn (Builder $builder, string $keyword) => $builder->where(fn (Builder $builder) => $query
                ->orWhere('name', $likeClause, "%$keyword%")
                ->orWhere('code', $likeClause, "%$keyword%")
                ->orWhere('terminal', $likeClause, "%$keyword%"))
        );

        return $query->paginate($request->input('per_page', 30));
    }

    /**
     * @param Request $request
     * @param Point $point
     * @return LengthAwarePaginator
     */
    public function getDestination(Request $request, Point $point): LengthAwarePaginator
    {
        $query = Point::query()->with('regency.destinationCities', 'province')->whereHas('regency.destinationCities', function ($query) use ($point) {
            $query->where('origin_city_id', $point->load('regency')->regency->id);
        })->orderBy('code');

        $likeClause = $this->getMatchLikeClause($query);

        $query->when(
            $request->input('keyword'),
            fn (Builder $builder, string $keyword) => $builder->where(fn (Builder $builder) => $query
                ->orWhere('name', $likeClause, "%$keyword%")
                ->orWhere('code', $likeClause, "%$keyword%")
                ->orWhere('terminal', $likeClause, "%$keyword%"))
        );

        return $query->paginate($request->input('per_page', 30));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $job = new CreateNewPoint($request->all());

        $this->dispatch($job);

        return $this->success($job->point->load('regency')->toArray())->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param Request $request
     * @param Point $point
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Point $point): JsonResponse
    {
        $job = new UpdateExistingPoint($point, $request->toArray());
        $this->dispatch($job);

        return $this->success($job->point->load('regency')->toArray());
    }

    public function show(Point $point): JsonResponse
    {
        return $this->success($point->load('regency', 'province')->toArray());
    }

    public function destroy(Point $point): JsonResponse
    {
        $job = new DeleteExistingPoint($point);
        $this->dispatch($job);

        return $this->success($job->point->toArray());
    }
}
