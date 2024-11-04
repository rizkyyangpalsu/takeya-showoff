<?php

namespace App\Actions\Reservation;

use Carbon\Carbon;
use App\Models\Route\Track;
use App\Models\Schedule\Setting;
use App\Models\Schedule\Reservation;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class MatchingReservationFromSchedule
{
    use AsAction;

    public function handle(Carbon $date, Setting\Detail $settingDetail): void
    {
        [$hour, $minute] = explode(':', $settingDetail->departure);

        $date->setTime($hour, $minute);

        $reservationsQuery = $settingDetail->reservations()
            ->where('departure_schedule', $date->format('Y-m-d H:i:s'));

        collect(range($reservationsQuery->count(), $settingDetail->fleet - 1))
            ->map(function ($index) use ($date, $settingDetail) {
                /** @var Reservation|null $reservation */
                if ($reservation = Reservation::query()
                    ->where('departure_schedule', $date->format('Y-m-d H:i:s'))
                    ->where('index', $index)
                    ->where('route_id', $settingDetail->setting->route_id)
                    ->where('layout_id', $settingDetail->layout_id)
                    ->first()) {
                    if ($reservation->setting_details()->where('id', $settingDetail->id)->count() === 0) {
                        $reservation->setting_details()->attach($settingDetail);
                    }

                    return $reservation;
                }

                $reservation = new Reservation();
                $reservation->code = $this->generateCode($date, $index);
                $reservation->departure_schedule = $date;
                $reservation->index = $index;
                $reservation->route()->associate($settingDetail->setting->route_id);
                $reservation->layout()->associate($settingDetail->layout_id);
                $reservation->save();

                // attach setting details
                $reservation->setting_details()->attach($settingDetail);

                $timeCursor = clone $date;

                // setup tracks
                $settingDetail->setting->route->tracks->each(function (Track $track) use ($reservation, &$timeCursor, $settingDetail) {
                    $reservation->trips()->create([
                        'index' => $track->index,
                        'origin_id' => $track->origin_id,
                        'destination_id' => $track->destination_id,
                        'seat_configuration' => $settingDetail->seat_configuration,
                        'departure' => $timeCursor->format('Y-m-d H:i:s'),
                        'arrival' => $timeCursor->addMinutes($track->duration)->format('Y-m-d H:i:s'),
                    ]);

                    $timeCursor->addMinutes($track->destination_transit_duration);
                });

                return $reservation;
            })->each(function (Reservation $reservation) {
                $reservation->trips()->cursor()->each(fn (Reservation\Trip $trip) => $trip->update([
                    'origin' => optional(Track\Point::query()->where('id', $trip->origin_id)->first())->only(['code', 'name', 'terminal']),
                    'destination' => optional(Track\Point::query()->where('id', $trip->destination_id)->first())->only(['code', 'name', 'terminal']),
                ]));
            });
    }

    private function generateCode(Carbon $date, int $index): string
    {
        return 'RSVP/'.$date->format('ymd').'/'.str_pad($index, 2, 0, STR_PAD_LEFT).'/'.Str::random(4);
    }
}
