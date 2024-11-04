<?php

namespace App\Support;

use JsonSerializable;
use App\Models\Fleet\Layout;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class SeatConfigurator implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * Layout instance.
     *
     * @var \App\Models\Fleet\Layout|null
     */
    protected ?Layout $layout;

    /**
     * Seat data.
     *
     * @var array
     */
    protected array $seats = [];

    /**
     * Unavailable seats.
     *
     * @var array
     */
    protected array $unavailable = [];

    /**
     * Available seats.
     *
     * @var array
     */
    protected array $available = [];

    /**
     * Booked seats, seat that has been booked, but not paid.
     *
     * @var array
     */
    protected array $booked = [];

    /**
     * Occupied, seat that has been purchased by the occupant.
     *
     * @var array
     */
    protected array $occupied = [];

    /**
     * Reserved seats, reserved seats.
     *
     * @var array
     */
    protected array $reserved = [];

    /**
     * SeatConfigurator constructor.
     *
     * @param \App\Models\Fleet\Layout|null $layout
     * @param array                         $existing
     */
    public function __construct(?Layout $layout = null, $existing = [])
    {
        if (count($existing)) {
            $this->extract($existing);
        }

        if (! empty($layout)) {
            $this->setLayout($layout);
        }
    }

    /**
     * Mark seat(s) as booked.
     *
     * @param mixed ...$seatId
     *
     * @return $this
     */
    public function bookSeat(...$seatId): self
    {
        $ids = $this->extractValidIds(func_get_args());
        $this->booked = collect($this->booked)->merge($ids)->unique()->all();
        $this->makeUnavailable($ids);

        return $this;
    }

    /**
     * Make seats as unavailable.
     *
     * @param array $ids
     *
     * @return $this
     */
    public function makeUnavailable(array $ids): self
    {
        $this->unavailable = collect($this->unavailable)->merge($ids)->unique()->all();
        $this->available = collect(array_keys($this->seats))->diff($this->unavailable)->all();

        return $this;
    }

    /**
     * Make seat(s) as reserved.
     *
     * @param mixed ...$seatId
     *
     * @return $this
     */
    public function reserveSeat(...$seatId): self
    {
        $ids = $this->extractValidIds(func_get_args());
        $this->reserved = collect($this->reserved)->merge($ids)->unique()->all();
        $this->makeUnavailable($ids);

        return $this;
    }

    /**
     * Mark seat(s) as occupied.
     *
     * @param mixed ...$seatId
     *
     * @return $this
     */
    public function occupySeat(...$seatId): self
    {
        $ids = $this->extractValidIds(func_get_args());
        $this->occupied = collect($this->occupied)->merge($ids)->unique()->all();
        $this->makeUnavailable($ids);

        return $this;
    }

    /**
     * Set layout.
     *
     * @param \App\Models\Fleet\Layout $layout
     *
     * @return $this
     */
    public function setLayout(Layout $layout): self
    {
        $this->layout = $layout;

        return $this->setSeats(
            $layout->seats
                ->filter(fn (Layout\Seat $seat) => $seat->selectable)
                ->mapWithKeys(fn (Layout\Seat $seat) => [$seat['id'] => $seat['name']])
                ->all()
        );
    }

    /**
     * Get Seats data.
     *
     * @return array
     */
    public function getSeats(): array
    {
        return $this->seats;
    }

    /**
     * Set seats data.
     *
     * @param array $seats
     *
     * @return $this
     */
    public function setSeats(array $seats = []): self
    {
        $this->seats = $seats;
        $this->syncSeats();

        return $this;
    }

    /**
     * Get all unavailable seat ids.
     *
     * @return array
     */
    public function getUnavailable(): array
    {
        return $this->unavailable;
    }

    /**
     * Get all available seat ids.
     *
     * @return array
     */
    public function getAvailable(): array
    {
        return $this->available;
    }

    /**
     * Get all reserved seat ids.
     *
     * @return array
     */
    public function getReserved(): array
    {
        return $this->reserved;
    }

    /**
     * Get all booked seat ids.
     *
     * @return array
     */
    public function getBooked(): array
    {
        return $this->booked;
    }

    /**
     * Get all occupied seat ids.
     *
     * @return array
     */
    public function getOccupied(): array
    {
        return $this->occupied;
    }

    /**
     * Make seats available.
     *
     * @param array $ids
     *
     * @return $this
     */
    public function makeAvailable(array $ids): self
    {
        $this->available = array_merge($this->available, $ids);
        $this->unavailable = collect($this->unavailable)->diff($ids)->all();
        $this->booked = collect($this->booked)->diff($ids)->all();
        $this->reserved = collect($this->reserved)->diff($ids)->all();
        $this->occupied = collect($this->occupied)->diff($ids)->all();

        return $this;
    }

    /**
     * Create fresh object from existing layout.
     *
     * @return $this
     */
    public function fresh(): self
    {
        return new static($this->layout);
    }

    /** {@inheritdoc} */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /** {@inheritdoc} */
    public function toArray(): array
    {
        return [
            'seats' => $this->seats,
            'unavailable' => $this->unavailable,
            'available' => $this->available,
            'booked' => $this->booked,
            'occupied' => $this->occupied,
            'reserved' => $this->reserved,
        ];
    }

    /** {@inheritdoc} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * merge 2 seat configurator into 1.
     *
     * @param \App\Support\SeatConfigurator $seatConfigurator
     * @return $this
     */
    public function merge(self $seatConfigurator): self
    {
        $this->reserveSeat(...$seatConfigurator->reserved);
        $this->bookSeat(...$seatConfigurator->booked);
        $this->occupySeat(...$seatConfigurator->occupied);

        return $this;
    }

    /**
     * Extract from existing data.
     *
     * @param array $existing
     */
    protected function extract(array $existing)
    {
        foreach ($existing as $k => $v) {
            if (property_exists($this, $k)) {
                $this->{$k} = $v;
            }
        }

        $this->syncSeats();
    }

    /**
     * Sync all seats with the seats data.
     *
     * @return void
     */
    protected function syncSeats(): void
    {
        $this->unavailable = collect($this->unavailable)->filter(fn ($item) => array_key_exists($item, $this->seats))->all();
        $this->available = collect($this->available)->filter(fn ($item) => array_key_exists($item, $this->seats))->all();
        $this->booked = collect($this->booked)->filter(fn ($item) => array_key_exists($item, $this->seats))->all();
        $this->reserved = collect($this->reserved)->filter(fn ($item) => array_key_exists($item, $this->seats))->all();
        $this->occupied = collect($this->occupied)->filter(fn ($item) => array_key_exists($item, $this->seats))->all();

        // reposition
        $this->bookSeat(...$this->booked);
        $this->reserveSeat(...$this->reserved);
        $this->occupySeat(...$this->occupied);
    }

    /**
     * Extract valid seat ids.
     *
     * @param array $ids
     *
     * @return array
     */
    protected function extractValidIds(array $ids): array
    {
        return collect($ids)->filter(fn ($id) => array_key_exists($id, $this->seats))->all();
    }
}
