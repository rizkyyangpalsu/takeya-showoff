<?php

namespace App\Support;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class ScheduleSettingOptions implements Arrayable, Jsonable, JsonSerializable
{
    private array $days;

    public function __construct(array $options)
    {
        $this->setDays($options['days'] ?? []);
    }

    public function toArray()
    {
        return [
            'days' => $this->getDays(),
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return array
     */
    public function getDays(): array
    {
        return $this->days;
    }

    /**
     * @param array $days
     */
    public function setDays(array $days): void
    {
        $this->days = collect($days)
            ->filter(fn ($day) => in_array((int) $day, range(1, 7), true))
            ->map(fn (mixed $day) => (int) $day)->toArray();
    }
}
