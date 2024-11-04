<?php

namespace App\Http\Controllers\Api\Office;

use App\Models\Office;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\Offices\CreateNewOffice;
use Illuminate\Database\Eloquent\Builder;
use App\Jobs\Offices\DeleteExistingOffice;
use App\Jobs\Offices\UpdateExistingOffice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OfficeController extends Controller
{
    /**
     * Get all available office.
     * Route Path       : /v1/office
     * Route Name       : api.office
     * Route Method     : GET.
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function index(Request $request): LengthAwarePaginator
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->filled('force_all')) {
            $query = Office::query();
        } else {
            $query = $user->getOfficesQuery();
        }

        $query->when($request->filled('warehouse'), fn (Builder $builder) => $builder->where('has_warehouse', true));
        $query->when($request->filled('workshop'), fn (Builder $builder) => $builder->where('has_workshop', true));

        $query->when($request->filled('keyword'), fn (Builder $builder) => $builder->search($request->input('keyword')));

        $query->when($request->filled('parent') && $request->input('parent'), fn (Builder $builder) => $builder->whereNull('office_id'));

        $query->orderBy('created_at', 'desc');

        $paginate = $query->paginate($request->input('per_page', 15));

        collect($paginate->items())->each->load('regency', 'province');

        return $paginate;
    }

    /**
     * Create new office
     * Route Path       : /v1/office
     * Route Name       : api.office.store
     * Route Method     : POST.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $job = new CreateNewOffice($request->all());
        $this->dispatch($job);

        return $this->success($job->office->fresh('regency', 'province')->toArray());
    }

    /**
     * Get information about office
     * Route Path       : /v1/office
     * Route Name       : api.office.show
     * Route Method     : GET.
     *
     * @param Office $office
     *
     * @return JsonResponse
     */
    public function show(Office $office)
    {
        return $this->success($office->load('regency', 'province')->toArray());
    }

    /**
     * Update existing office.
     * Route Path       : /v1/office/{office_slug}
     * Route Name       : api.office.update
     * Route Method     : PUT.
     *
     * @param Office $office
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Office $office)
    {
        $job = new UpdateExistingOffice($office, request()->all());
        $this->dispatch($job);

        return $this->success($job->office->fresh('regency', 'province')->toArray());
    }

    /**
     * Delete existing office.
     * Route Path       : /v1/office/{office_slug}
     * Route Name       : api.office.destroy
     * Route Method     : DELETE.
     *
     * @param Office $office
     *
     * @return JsonResponse
     */
    public function destroy(Office $office)
    {
        $office->load(['regency', 'province']);
        $this->dispatch(new DeleteExistingOffice($office));

        return $this->success($office->toArray());
    }

    /**
     * Delete existing office.
     * Route Path       : /v1/office/{office_slug}/descendants
     * Route Name       : api.office.descendants
     * Route Method     : GET.
     *
     * @param Office $office
     *
     * @return LengthAwarePaginator
     */
    public function descendants(Office $office): LengthAwarePaginator
    {
        $query = $office->descendants();
        $query->orderBy('created_at', 'desc');

        $paginate = $query->paginate(request('per_page', 15));

        collect($paginate->items())->each->load('regency', 'province');

        return $paginate;
    }
}
