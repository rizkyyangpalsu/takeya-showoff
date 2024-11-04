<?php

namespace App\Support\Schedule\Item;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use JetBrains\PhpStorm\ArrayShape;

class Price implements Arrayable, \JsonSerializable, Jsonable
{
    private float $nominal;
    private Collection $descriptions;

    /**
     * Price constructor.
     * @param float $nominal
     */
    public function __construct(float $nominal)
    {
        $this->nominal = $nominal;
        $this->descriptions = new Collection();
    }

    public function addNominal(float $amount): void
    {
        $this->nominal += $amount;
    }

    public function addDescription(Price\Detail $detail): void
    {
        $this->descriptions->push($detail);
    }

    #[ArrayShape(['nominal' => 'float', 'description' => 'array'])]
    public function toArray(): array
    {
        return [
            'nominal' => $this->nominal,
            'description' => $this->descriptions->toArray(),
        ];
    }

    /**
     * @return float
     */
    public function getNominal(): float
    {
        return $this->nominal;
    }

    /**
     * @throws \JsonException
     */
    public function toJson($options = 0): bool|string
    {
        return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR | $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
