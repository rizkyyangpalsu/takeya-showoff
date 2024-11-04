<?php

namespace App\Http\Controllers\Api\Office;

use App\Models\Fleet;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use App\Http\Controllers\Api\Fleet\FleetController as BaseFleetController;

class FleetController extends Controller
{
    /**
     * Base Fleet controller.
     *
     * @var \App\Http\Controllers\Api\Fleet\FleetController
     */
    protected BaseFleetController $fleetController;

    /**
     * FleetController constructor.
     */
    public function __construct()
    {
        $this->fleetController = new BaseFleetController();
    }

    /**
     * All fleet within the office
     * Route Path       : /v1/office/{office_slug}/fleet
     * Route Name       : api.office.fleet
     * Route Path       : GET.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Office $office
     *
     * @return PaginatorContract
     */
    public function index(Request $request, Office $office): PaginatorContract
    {
        $query = $office->fleets();

        $this->fleetController->applyFilter($query, $request);

        return $query->paginate(request('per_page', 15));
    }

    /**
     * Create new fleet
     * Route Path       : /v1/office/{office_slug}/fleet
     * Route Name       : api.office.fleet.store
     * Route Method     : POST.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Office       $office
     *
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, Office $office): JsonResponse
    {
        $request->merge(['base_hash' => $office->hash]);

        return $this->fleetController->store($request);
    }

    /**
     * Get Details information about the fleet.
     * Route Path       : /v1/office/{office_slug}/fleet/{fleet_hash}
     * Route Name       : api.office.fleet.show
     * Route Method     : GET.
     *
     * @param \App\Models\Office $office
     * @param \App\Models\Fleet $fleet
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function show(Office $office, Fleet $fleet): JsonResponse
    {
        $this->shouldBelongsTo($fleet, $office);

        return $this->fleetController->show($fleet);
    }

    /**
     * Update existing fleet.
     * Route Path       : /v1/office/{office_slug}/fleet/{fleet_hash}
     * Route Name       : api.office.fleet.update
     * Route Method     : PUT.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Office       $office
     * @param \App\Models\Fleet        $fleet
     *
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function update(Request $request, Office $office, Fleet $fleet): JsonResponse
    {
        $this->shouldBelongsTo($fleet, $office);

        return $this->fleetController->update($request, $fleet);
    }

    /**
     * Delete existing Fleet
     * Route Path       : /v1/office/{office_slug}/fleet/{fleet_hash}
     * Route Name       : api.office.fleet.destroy
     * Route Method     : DELETE.
     *
     * @param \App\Models\Office $office
     * @param \App\Models\Fleet $fleet
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function destroy(Office $office, Fleet $fleet): JsonResponse
    {
        $this->shouldBelongsTo($fleet, $office);

        return $this->fleetController->destroy($fleet);
    }

    /**
     * Determine if the fleet is belongs to an office.
     *
     * @param \App\Models\Fleet  $fleet
     * @param \App\Models\Office $office
     *
     * @throws \Throwable
     */
    private function shouldBelongsTo(Fleet $fleet, Office $office)
    {
        throw_if($fleet->base_id !== $office->id, new AuthorizationException('Fleet is not part of this office.'));
    }
}
