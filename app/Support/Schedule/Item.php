<?php

namespace App\Support\Schedule;

use Illuminate\Support\Carbon;
use JsonSerializable;
use App\Models\Route\Track;
use App\Models\Fleet\Layout\Seat;
use App\Support\SeatConfigurator;
use Illuminate\Support\Collection;
use App\Models\Schedule\Reservation;
use Illuminate\Database\Query\Builder;
use App\Models\Schedule\Setting\Detail;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Serializable;

class Item implements JsonSerializable, Jsonable, Arrayable, Serializable
{
    use HidesAttributes;
    use Collector\PriceModifierResolver;

    private Carbon $departureSchedule;

    /**
     * First point when customer start their trip.
     *
     * @var int
     */
    private int $departureIndex;

    /**
     * Last point when customer finish their trip.
     *
     * @var int
     */
    private int $destinationIndex;

    public function __construct(
        public Detail $settingDetail,
        private Carbon $date,
        public int $departureId,
        public int $destinationId,
        private int $nominal,
        private Collection $priceModifiers
    ) {
        $this->settingDetail->route->load(['tracks.origin', 'tracks.destination']);

        $this->visible = [
            'hash',
            'first_depart',
            'fleet',
            'prices',
            'route',
            'layout',
            'tracks',
            'points',
        ];

        $this->setDepartureSchedule($date);
    }

    public function getPrice(): Item\Price
    {
        $price = new Item\Price($this->nominal);

        $this->priceModifiers->each(function (Detail\PriceModifier $priceModifier) use ($price) {
            // if relation items not loaded, it's indicate that we got priceModifiers from cache
            // we will do the same query from Collector@loadSettingDetails to get the right priceModifier
            if (! $priceModifier->relationLoaded('items')) {
                $priceModifierItemsQuery = $priceModifier->items()->join('prices', fn (JoinClause $clause) => $clause
                    ->on('price_modifier_items.price_id', '=', 'prices.id')
                    ->select(['price_modifier_items.*'.'prices.nominal'])
                    ->where('prices.origin_id', $this->departureId)
                    ->where('prices.destination_id', $this->destinationId));

                $priceModifier->setRelation('items', $priceModifierItemsQuery->cursor());
            }

            $modifier = (float) $priceModifier->items->first()->amount;
            $price->addNominal($modifier);

            if ($priceModifier->is_shown) {
                $price->addDescription(new Item\Price\Detail($priceModifier->display_text, $modifier));
            }

            return $priceModifier->is_combined;
        });

        return $price;
    }

    public function toArray(): array
    {
        $data = [];

        if (in_array('hash', $this->visible) && ! in_array('hash', $this->hidden, true)) {
            $data['hash'] = $this->getHash();
        }

        if (in_array('first_depart', $this->visible) && ! in_array('first_depart', $this->hidden, true)) {
            $data['first_depart'] = $this->settingDetail->departure;
        }

        if (in_array('fleet', $this->visible) && ! in_array('fleet', $this->hidden, true)) {
            $data['fleet'] = $this->settingDetail->fleet;
        }

        if (in_array('points', $this->visible) && ! in_array('points', $this->hidden, true)) {
            $data['points'] = [
                'origin' => $this->getFirstPoint(),
                'departure' => $this->getDeparturePoint(),
                'destination' => $this->getDestinationPoint(),
            ];
        }

        if (in_array('prices', $this->visible) && ! in_array('prices', $this->hidden, true)) {
            $data['prices'] = $this->getPrice();
        }

        if (in_array('route', $this->visible) && ! in_array('route', $this->hidden, true)) {
            $data['route'] = $this->settingDetail->route->makeHidden(['tracks', 'created_at', 'updated_at', 'points_count'])->toArray();
        }

        if (in_array('layout', $this->visible) && ! in_array('layout', $this->hidden, true)) {
            $data['layout'] = $this->settingDetail->layout->makeHidden('seats')->toArray();
        }

        if (in_array('tracks', $this->visible) && ! in_array('tracks', $this->hidden, true)) {
            $data['tracks'] = $this->getTracks()->toArray();
        }

        if (in_array('seat_configurations', $this->visible) && ! in_array('seat_configurations', $this->hidden, true)) {
            $data['seat_configurations'] = $this->getSeatConfigurations();
        }

        if (in_array('state_seats', $this->visible)) {
            $data['state_seats'] = $this->getStateSeats();
        }

        return $data;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR | $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getSeatConfigurations(): Collection
    {
        $basicSeatConfiguration = $this->settingDetail->seat_configuration;
        $reservations = $this->settingDetail->reservations()
            ->where('departure_schedule', $this->getDepartureSchedule()->format('Y-m-d H:i:s'))
            ->get();

        return collect(range(0, $this->settingDetail->fleet - 1))
            ->map(fn () => $basicSeatConfiguration)
            ->map(function (SeatConfigurator $basicSeatConfiguration, int $index) use ($reservations) {
                if ($reservation = $reservations->where('index', $index)->first()) {
                    $seatConfiguration = clone $basicSeatConfiguration;

                    $reservation->trips()->where('schedule_reservation_trips.index', '>=', fn (Builder $builder) => $builder->select('index')
                        ->where('origin_id', $this->departureId)
                        ->where('reservation_id', $reservation->id)
                        ->from('schedule_reservation_trips'))
                        ->where('schedule_reservation_trips.index', '<=', fn (Builder $builder) => $builder->select('index')
                            ->where('destination_id', $this->destinationId)
                            ->where('reservation_id', $reservation->id)
                            ->from('schedule_reservation_trips'))
                        ->cursor()
                        ->each(fn (Reservation\Trip $trip) => $seatConfiguration->merge($trip->seat_configuration));

                    return $seatConfiguration;
                }

                return $basicSeatConfiguration;
            });
    }

    /**
     * @return Carbon
     */
    public function getDate(): Carbon
    {
        return $this->date;
    }

    public function getDepartureSchedule(): Carbon
    {
        return clone $this->departureSchedule;
    }

    public function getDeparturePoint(): Track\Point
    {
        return $this->settingDetail->route->tracks->get($this->departureIndex)?->origin;
    }

    public function getDestinationPoint(): Track\Point
    {
        return $this->settingDetail->route->tracks->get($this->destinationIndex)?->destination;
    }

    public function setDepartureSchedule(Carbon $date): void
    {
        [$hour, $minute] = explode(':', $this->settingDetail->departure);

        $this->departureSchedule = $date->setTime($hour, $minute);

        $this->setupTracksDecoration();
    }

    public function serialize(): bool|string|null
    {
        $data = [];

        $data['detail_id'] = $this->settingDetail->id;
        $data['departure_id'] = $this->departureId;
        $data['destination_id'] = $this->destinationId;
        $data['date'] = $this->date;
        $data['departure_schedule'] = $this->getDepartureSchedule();
        $data['date_tz'] = $this->date->timezoneName;

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     */
    public function unserialize($data)
    {
        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        $this->date = new Carbon($data['date'], $data['date_tz']);
        $this->date->timezone($data['date_tz']);
        $this->departureId = $data['departure_id'];
        $this->destinationId = $data['destination_id'];
        $this->departureSchedule = Carbon::createFromTimestamp(strtotime($data['departure_schedule']));

        /** @var Detail $settingDetail */
        $settingDetail = Detail::query()->with('setting')->findOrFail($data['detail_id']);

        $this->settingDetail = $settingDetail;

        $this->settingDetail->route->load(['tracks.origin', 'tracks.destination']);
        $this->loadRelationPriceModifierFor($this->settingDetail, $data['departure_id'], $data['destination_id']);

        $this->priceModifiers = $this->settingDetail->priceModifiers;
        $this->nominal = (float) $this->settingDetail->priceModifiers->first()->items->first()->nominal;

        $this->visible = [
            'hash',
            'first_depart',
            'fleet',
            'prices',
            'route',
            'layout',
            'tracks',
            'points',
        ];

        $this->setupTracksDecoration();
    }

    public static function fromHash(string $hash): self
    {
        return unserialize(decrypt($hash), ['allowed_class' => __CLASS__]);
    }

    private function getStateSeats(): Collection
    {
        $seats = $this->settingDetail->layout->seats;

        return $this->getSeatConfigurations()->map(function (SeatConfigurator $seatConfigurator) use ($seats) {
            return $seats->map(fn (Seat $seat) => array_merge(
                $seat->makeHidden(['created_at', 'updated_at'])->toArray(),
                [
                    'status' => match (true) {
                        in_array($seat->id, $seatConfigurator->getReserved(), true) => 'reserved',
                        in_array($seat->id, $seatConfigurator->getOccupied(), true) => 'occupied',
                        in_array($seat->id, $seatConfigurator->getBooked(), true) => 'booked',
                        in_array($seat->id, $seatConfigurator->getUnavailable(), true) => 'unavailable',
                        default => 'available',
                    },
                ]
            ));
        });
    }

    private function getTracks(): Collection
    {
        return $this->settingDetail->route->tracks;
    }

    private function setupTracksDecoration(): void
    {
        $passingState = false;

        $departTimeCursor = $this->getDepartureSchedule();

        $this->settingDetail->route->tracks->transform(function (Track $track, $index) use (&$passingState, &$departTimeCursor) {
            if (! $passingState && (int) $track->origin_id === $this->departureId) {
                $passingState = true;
                $this->departureIndex = $index;
            }

            $track->origin->makeHidden(['created_at', 'updated_at']);
            $track->destination->makeHidden(['created_at', 'updated_at']);

            $track->setAttribute('passing', $passingState);
            $track->origin->setAttribute('etd', $departTimeCursor->clone());
            $track->destination->setAttribute('eta', $departTimeCursor->addMinutes($track->duration)->clone());

            $departTimeCursor->addMinutes($track->destination_transit_duration);

            if ($passingState && (int) $track->destination_id === $this->destinationId) {
                $passingState = false;
                $this->destinationIndex = $index;
            }

            return $track;
        });
    }

    private function getFirstPoint(): Track\Point
    {
        return $this->settingDetail->route->tracks->first()->origin;
    }

    private function getHash(): string
    {
        return encrypt(serialize($this));
    }
}
