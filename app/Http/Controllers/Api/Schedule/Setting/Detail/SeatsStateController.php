<?php

namespace App\Http\Controllers\Api\Schedule\Setting\Detail;

use Illuminate\Http\Request;
use App\Models\Schedule\Setting;
use App\Models\Fleet\Layout\Seat;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Jobs\Schedule\Setting\Detail\UpdateExistingSettingDetail;

class SeatsStateController extends Controller
{
    /**
     * @param \App\Models\Schedule\Setting $setting
     * @param \App\Models\Schedule\Setting\Detail $detail
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnusedParameterInspection
     */
    public function index(Setting $setting, Setting\Detail $detail): JsonResponse
    {
        $seats = $detail->layout->seats;

        $seatConfigurator = $detail->seat_configuration;

        return $this->success($seats
            ->map(fn (Seat $seat) => array_merge(
                $seat->makeHidden(['created_at', 'updated_at'])->toArray(),
                [
                    'status' => match (true) {
                        in_array($seat->id, $seatConfigurator->getReserved(), true) => 'reserved',
                        in_array($seat->id, $seatConfigurator->getUnavailable(), true) => 'unavailable',
                        default => 'available',
                    },
                ]
            ))
            ->toArray());
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Schedule\Setting $setting
     * @param \App\Models\Schedule\Setting\Detail $detail
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Setting $setting, Setting\Detail $detail): JsonResponse
    {
        $job = new UpdateExistingSettingDetail($detail, $request->all());

        $this->dispatchSync($job);

        return $this->success($job->settingDetail->toArray());
    }
}
