<?php

namespace App\Support\Schedule\Collector;

use Carbon\Carbon;
use App\Models\Schedule\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait SettingResolver
{
    protected function getQuery(Carbon $date, int $departureId, int $destinationId): Builder
    {
        $query = Setting::query();

        $this->filterActiveOrExistsInTrips($query, $date);
        $this->filterMatchDay($query, $date);
        $this->filterMatchTrack($query, $departureId, $destinationId);

        return $query;
    }

    /**
     * @param \Carbon\Carbon $date
     * @param int $departureId
     * @param int $destinationId
     * @return \Illuminate\Support\Collection<Setting>
     */
    protected function resolveSettings(Carbon $date, int $departureId, int $destinationId): Collection
    {
        $foundedRouteIds = [];

        return $this
            ->getQuery($date, $departureId, $destinationId)
            ->get()
            // filtering only one of the first setting that have the same route that valid to be return value
            ->filter(static function (Setting $setting) use ($foundedRouteIds) {
                $validUniqueRouteId = ! in_array($setting->route_id, $foundedRouteIds, true);

                if ($validUniqueRouteId) {
                    $foundedRouteIds[] = $setting->route_id;
                }

                return $validUniqueRouteId;
            })
            ->values();
    }

    private function filterActiveOrExistsInTrips(Builder $query, Carbon $date): void
    {
        $query->where(fn (Builder $builder) => $builder->whereNull('started_at')->orWhereDate('started_at', '<=', $date));
        $query->where(fn (Builder $builder) => $builder->whereNull('expired_at')->orWhereDate('expired_at', '>=', $date));
    }

    private function filterMatchDay(Builder $query, Carbon $date): void
    {
        // get schedule setting from given date
        $dayIso = $date->dayOfWeekIso;

        $query->whereJsonContains('options->days', $dayIso);
    }

    private function filterMatchTrack(Builder $builder, int $departureId, int $destinationId): void
    {
        $builder->whereHas('prices', fn (Builder $builder) => $builder
            ->where('origin_id', $departureId)
            ->where('destination_id', $destinationId));
    }
}
