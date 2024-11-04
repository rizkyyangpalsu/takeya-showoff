<?php

namespace App\Actions\Transaction;

use App\Models\User;
use Illuminate\Support\Carbon;
use App\Models\Geo\Regency;
use App\Concerns\BasicResponse;
use App\Models\Route\Track\Point;
use Illuminate\Http\JsonResponse;
use App\Support\Schedule\Collector;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Exceptions\Schedule\ScheduleNotFound;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class GetAvailableSchedule
{
    use AsAction, BasicResponse;

    public function rules(): array
    {
        $rules = [
            'date' => 'required|date_format:Y-m-d|',
            'departure_hash' => ['nullable', new ExistsByHash(Point::class)],
            'destination_hash' => ['required_with:departure_hash', new ExistsByHash(Point::class)],
            'departure_regency_hash' => ['required_without:departure_hash', new ExistsByHash(Regency::class)],
            'destination_regency_hash' => ['required_with:departure_regency_hash', new ExistsByHash(Regency::class)],
        ];

        if (\auth()->user() && \auth()->user()->user_type === User::USER_TYPE_ADMIN) {
            $rules['date'] = $rules['date'].'after_or_equal:'.today()->subDays(21)->toString();
        } elseif (\auth()->user() && \auth()->user()->user_type !== User::USER_TYPE_SUPER_ADMIN) {
            $rules['date'] = $rules['date'].'after_or_equal:'.today()->toString();
        }

        return $rules;
    }

    /**
     * @param ActionRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function asController(ActionRequest $request): JsonResponse
    {
        $request->validated();

        [
            $date,
            $departureId,
            $destinationId,
            $departureRegencyId,
            $destinationRegencyId,
            $visible,
        ] = $this->extractRequest($request->all());

        return match (true) {
            (bool) $departureId => $this->singlePointQuery($date, $departureId, $destinationId, $visible),
            (bool) $departureRegencyId => $this->geoQuery($date, $departureRegencyId, $destinationRegencyId, $visible),
        };
    }

    private function extractRequest(array $inputs): array
    {
        $date = new Carbon($inputs['date']);
        $departureId = array_key_exists('departure_hash', $inputs) ? Point::hashToId($inputs['departure_hash']) : null;
        $destinationId = array_key_exists('destination_hash', $inputs) ? Point::hashToId($inputs['destination_hash']) : null;
        $departureRegencyId = array_key_exists('departure_regency_hash', $inputs) ? Regency::hashToId($inputs['departure_regency_hash']) : null;
        $destinationRegencyId = array_key_exists('destination_regency_hash', $inputs) ? Regency::hashToId($inputs['destination_regency_hash']) : null;
        $visible = array_key_exists('visible', $inputs) ? $inputs['visible'] : [];

        return [$date, $departureId, $destinationId, $departureRegencyId, $destinationRegencyId, $visible];
    }

    /**
     * @param Carbon $date
     * @param int $departureId
     * @param int $destinationId
     * @return JsonResponse
     * @throws \Exception
     */
    private function singlePointQuery(Carbon $date, int $departureId, int $destinationId, array $visible): JsonResponse
    {
        return $this->success((Collector::fromCache($date, $departureId, $destinationId)->visible($visible))->toArray());
    }

    /**
     * @param Carbon $date
     * @param int $departureRegencyId
     * @param int $destinationRegencyId
     * @return JsonResponse
     * @throws ScheduleNotFound
     */
    private function geoQuery(Carbon $date, int $departureRegencyId, int $destinationRegencyId, array $visible): JsonResponse
    {
        $departures = Point::query()->where('regency_id', $departureRegencyId)->get();
        $destinations = Point::query()->where('regency_id', $destinationRegencyId)->get();

        if ($departures->isEmpty() || $destinations->isEmpty()) {
            throw new ScheduleNotFound();
        }

        $distributes = $departures->map(fn (Point $departure) => $destinations->map(fn (Point $destination) => [
            'departure_id' => $departure->id,
            'destination_id' => $destination->id,
        ]))->flatten(1);

        $collectorList = $distributes->map(function (array $data) use ($date, $visible) {
            try {
                return Collector::fromCache($date, $data['departure_id'], $data['destination_id'])->visible($visible);
            } catch (ScheduleNotFound) {
                return null;
            }
        })->filter(fn (?Collector $collector) => $collector);

        return $this->success($collectorList->map(fn (Collector $collector) => $collector->toArray())->flatten(1));
    }
}
