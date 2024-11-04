<?php

namespace App\Rules\Contexts;

use Carbon\Carbon;
use LogicException;
use App\Contracts\RuleContext;

class Request implements RuleContext
{
    /**
     * @var array
     */
    private array $inputs;

    public function __construct()
    {
        $this->inputs = app('request')->all();
    }

    public function getContextProperties(): array
    {
        $properties = [];

        switch (true) {
            case array_key_exists('day', $this->inputs) && in_array($this->inputs['day'], range(1, 7)):
                $properties['day'] = $this->inputs['day'];
                break;
            case array_key_exists('datetime', $this->inputs):
            case array_key_exists('date', $this->inputs):
                $properties['day'] = (new Carbon($this->inputs['datetime'] ?? $this->inputs['date'] ?? null))->dayOfWeekIso;
                break;
        }

        return array_merge($this->inputs, $properties);
    }

    /**
     * @param $property
     * @return mixed
     * @throws \Throwable
     */
    public function getValueForContext($property)
    {
        $properties = $this->getContextProperties();

        throw_if(! array_key_exists($property, $properties), LogicException::class, 'unknown property '.$property);

        return $properties[$property];
    }

    public function getContextName(): string
    {
        return 'request';
    }
}
