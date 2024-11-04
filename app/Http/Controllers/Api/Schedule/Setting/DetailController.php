<?php

namespace App\Http\Controllers\Api\Schedule\Setting;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Jobs\Schedule\Setting\Detail\CreateNewSettingDetail;
use App\Jobs\Schedule\Setting\Detail\DeleteExistingSettingDetail;
use App\Jobs\Schedule\Setting\Detail\UpdateExistingSettingDetail;

class DetailController extends Controller
{
    public function index(Schedule\Setting $setting): LengthAwarePaginator
    {
        $query = $setting->details();

        $query->with('layout', 'priceModifiers');

        return $query->paginate();
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function show(Schedule\Setting $setting, Schedule\Setting\Detail $detail): JsonResponse
    {
        return $this->success($detail->load('layout')->toArray());
    }

    /**
     * @param Request $request
     * @param Schedule\Setting $setting
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request, Schedule\Setting $setting): JsonResponse
    {
        $job = new CreateNewSettingDetail($setting, $request->all());

        $this->dispatchSync($job);

        return $this->success($job->settingDetail->load(['layout'])->toArray())->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param Request $request
     * @param Schedule\Setting $setting
     * @param Schedule\Setting\Detail $detail
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, Schedule\Setting $setting, Schedule\Setting\Detail $detail): JsonResponse
    {
        $job = new UpdateExistingSettingDetail($detail, $request->all(), $setting);

        $this->dispatch($job);

        return $this->success($job->settingDetail->load(['layout'])->toArray());
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function destroy(Schedule\Setting $setting, Schedule\Setting\Detail $detail): JsonResponse
    {
        $job = new DeleteExistingSettingDetail($detail);

        $this->dispatch($job);

        return $this->success($job->settingDetail->toArray());
    }
}
