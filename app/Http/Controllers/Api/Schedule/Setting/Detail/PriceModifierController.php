<?php

/** @noinspection PhpUnusedParameterInspection */

namespace App\Http\Controllers\Api\Schedule\Setting\Detail;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Schedule\Setting;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Jobs\Schedule\Setting\Detail\PriceModifier\CreateNewPriceModifier;
use App\Jobs\Schedule\Setting\Detail\PriceModifier\ChangeRulesOfPriceModifier;
use App\Jobs\Schedule\Setting\Detail\PriceModifier\DeleteExistingPriceModifier;
use App\Jobs\Schedule\Setting\Detail\PriceModifier\UpdateExistingPriceModifier;

class PriceModifierController extends Controller
{
    public function index(Setting $setting, Setting\Detail $detail): LengthAwarePaginator
    {
        $paginate = $detail->priceModifiers()->paginate(\request('per_page'));

        collect($paginate->items())->each(fn (Setting\Detail\PriceModifier $priceModifier) => $priceModifier->append(['interpreted_rules']));

        return $paginate;
    }

    public function show(Setting $setting, Setting\Detail $detail, Setting\Detail\PriceModifier $priceModifier): JsonResponse
    {
        $priceModifier->load('items.price');
        $priceModifier->append(['prices_table']);

        return $this->success($priceModifier->toArray());
    }

    /**
     * @param Request $request
     * @param Setting $setting
     * @param Setting\Detail $detail
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, Setting $setting, Setting\Detail $detail): JsonResponse
    {
        $job = new CreateNewPriceModifier($detail, $request->all());

        $this->dispatchSync($job);

        return $this->success([])->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param Request $request
     * @param Setting $setting
     * @param Setting\Detail $detail
     * @param Setting\Detail\PriceModifier $priceModifier
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function update(
        Request $request,
        Setting $setting,
        Setting\Detail $detail,
        Setting\Detail\PriceModifier $priceModifier
    ): JsonResponse {
        $job = new UpdateExistingPriceModifier($priceModifier, $request->all());

        $this->dispatchSync($job);

        return $this->success($priceModifier->fresh());
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Schedule\Setting $setting
     * @param \App\Models\Schedule\Setting\Detail $detail
     * @param \App\Models\Schedule\Setting\Detail\PriceModifier $priceModifier
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function updateRule(
        Request $request,
        Setting $setting,
        Setting\Detail $detail,
        Setting\Detail\PriceModifier $priceModifier
    ): JsonResponse {
        $job = new ChangeRulesOfPriceModifier($priceModifier, $request->all());

        $this->dispatchSync($job);

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $priceModifier = $priceModifier->fresh();

        return $this->success($priceModifier->append(['interpreted_rules'])->toArray());
    }

    /**
     * @param Setting $setting
     * @param Setting\Detail $detail
     * @param Setting\Detail\PriceModifier $priceModifier
     * @return JsonResponse
     * @throws \Throwable
     */
    public function destroy(Setting $setting, Setting\Detail $detail, Setting\Detail\PriceModifier $priceModifier): JsonResponse
    {
        $job = new DeleteExistingPriceModifier($priceModifier);

        $this->dispatchSync($job);

        return $this->success($job->priceModifier->toArray());
    }
}
