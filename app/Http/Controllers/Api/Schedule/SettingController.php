<?php

namespace App\Http\Controllers\Api\Schedule;

use App\Models\Route;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Jobs\Schedule\Setting\CreateNewScheduleSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Jobs\Schedule\Setting\DeactiveExistingScheduleSetting;
use App\Jobs\Schedule\Setting\ReactvateExistingScheduleSetting;
use App\Jobs\Schedule\Setting\UpdateExistingScheduleSetting;
use App\Jobs\Schedule\Setting\DuplicateExistingScheduleSetting;

class SettingController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        $query = Schedule\Setting::query();

        $query = $query->when($request->status, function ($query, $val) {
            if ($val === 'active') {
                $query->withoutTrashed();
            } elseif ($val === 'inactive') {
                $query->onlyTrashed();
            }
        })->when(! $request->status, function ($query) {
            $query->withTrashed();
        })->when($request->route_hash, function ($query, $val) {
            $query->where('route_id', Route::hashToId($val));
        });

        $query->with([
            'route',
        ]);

        return $query->paginate();
    }

    public function show(Schedule\Setting $setting): JsonResponse
    {
        $setting->load([
            'route.tracks',
            'details.layout',
        ]);

        return $this->success($setting->toArray());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $job = new CreateNewScheduleSetting($request->all());

        $this->dispatchSync($job);

        return $this->success($job->setting->fresh(['route']))->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param Request $request
     * @param Schedule\Setting $setting
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, Schedule\Setting $setting): JsonResponse
    {
        $job = new UpdateExistingScheduleSetting($setting, $request->all());

        $this->dispatchSync($job);

        return $this->success($job->setting->fresh(['route']));
    }

    /**
     * @param Schedule\Setting $setting
     * @return JsonResponse
     * @throws ValidationException
     */
    public function duplicate(Schedule\Setting $setting): JsonResponse
    {
        $job = new DuplicateExistingScheduleSetting($setting);

        $this->dispatchSync($job);

        return $this->success($job->setting->fresh(['route']));
    }

    public function destroy(Schedule\Setting $setting): JsonResponse
    {
        $job = new DeactiveExistingScheduleSetting($setting);

        $this->dispatchSync($job);

        return $this->success($job->setting->toArray());
    }

    public function reactivate(Schedule\Setting $setting): JsonResponse
    {
        $job = new ReactvateExistingScheduleSetting($setting);

        $this->dispatchSync($job);

        return $this->success($job->setting->toArray());
    }
}
