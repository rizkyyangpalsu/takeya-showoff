<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Models\Fleet;
use App\Models\Office;
use Illuminate\Http\Request;
use App\Jobs\Fleet\CreateNewFleet;
use App\Http\Controllers\Controller;
use App\Jobs\Fleet\DeleteExistingFleet;
use App\Jobs\Fleet\UpdateExistingFleet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class FleetController extends Controller
{
    /**
     * Fleet list
     * Route Path       : /v1/fleet
     * Route Name       : api.fleet
     * Route Path       : GET.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index(Request $request): LengthAwarePaginator
    {
        $query = Fleet::query()->with(['base', 'layout']);

        $this->applyFilter($query, $request);

        return $query->paginate($request->input('per_page', 15));
    }

    public function applyFilter($query, Request $request): void
    {
        $inputs = $request->validate([
            'keyword' => 'nullable|string',
            'is_operable' => 'nullable|bool',
            'base_hash' => ['nullable', new ExistsByHash(Office::class)],
            'layout_hash' => ['nullable', new ExistsByHash(Fleet\Layout::class)],
        ]);

        $query->when(
            $inputs['keyword'] ?? false,
            fn (Builder $builder, string $keyword) => $builder->where(
                fn (Builder $builder) => $builder
                    ->orWhere('manufacturer', 'like', '%'.$keyword.'%')
                    ->orWhere('license_plate', 'like', '%'.$keyword.'%')
                    ->orWhere('model', 'like', '%'.$keyword.'%')
                    ->orWhere('hull_number', 'like', '%'.$keyword.'%')
                    ->orWhere('engine_number', 'like', '%'.$keyword.'%')
            )
        );

        $query->when(
            $inputs['base_hash'] ?? false,
            fn (Builder $builder, string $base_hash) => $builder->where('base_id', Office::hashToId($base_hash))
        );

        $query->when(
            $inputs['layout_hash'] ?? false,
            fn (Builder $builder, string $layout_hash) => $builder->where('layout_id', Fleet\Layout::hashToId($layout_hash))
        );

        $query->when(
            $request->has('is_operable'),
            fn (Builder $builder) => $builder->where('is_operable', $inputs['is_operable'])
        );
    }

    /**
     * Create new fleet
     * Route Path       : /v1/fleet
     * Route Name       : api.fleet.store
     * Route Method     : POST.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $job = new CreateNewFleet($request->toArray());
        $this->dispatch($job);

        return $this->success($job->fleet->toArray());
    }

    /**
     * Get fleet details.
     * Route Path       : /v1/fleet/{fleet_hash}
     * Route  Name      : api.fleet.show
     * Route  Path      : GET.
     *
     * @param \App\Models\Fleet $fleet
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Fleet $fleet)
    {
        $fleet->load(['base', 'layout']);

        return $this->success($fleet->toArray());
    }

    /**
     * Update existing fleet
     * Route Path       : /v1/fleet/{fleet_hash}
     * Route Name       : api.fleet.update
     * Route Method     : PUT.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Fleet $fleet
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Fleet $fleet)
    {
        $job = new UpdateExistingFleet($fleet, $request->toArray());
        $this->dispatch($job);

        return $this->success($fleet->fresh());
    }

    /**
     * Delete existing user.
     * Route Path       : /v1/fleet/{fleet_hash}
     * Route Name       : api.fleet.hash
     * Route Method     : DELETE.
     *
     * @param \App\Models\Fleet $fleet
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Fleet $fleet)
    {
        $job = new DeleteExistingFleet($fleet);
        $this->dispatch($job);

        return $this->success($fleet->toArray());
    }
}
