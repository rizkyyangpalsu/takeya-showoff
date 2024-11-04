<?php

namespace App\Jobs\Schedule\Setting\Detail\PriceModifier;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Schedule\Setting\Detail\PriceModifier;

class ChangeRulesOfPriceModifier
{
    /**
     * @var \App\Models\Schedule\Setting\Detail\PriceModifier
     */
    public PriceModifier $priceModifier;
    private array $attributes;

    /**
     * ChangeRulesOfPriceModifier constructor.
     * @param \App\Models\Schedule\Setting\Detail\PriceModifier $priceModifier
     * @param array $attributes
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function __construct(PriceModifier $priceModifier, array $attributes)
    {
        $this->priceModifier = $priceModifier;

        throw_if($priceModifier->priority == 1, ValidationException::withMessages(['priority' => __('cannot modify rules of primary price modifier')]));

        $this->attributes = Validator::make($attributes, [
            'valid_days' => 'nullable|array',
            'valid_days.*' => 'required|numeric|between:1,7',
        ])->validated();
    }

    public function handle()
    {
        if (array_key_exists('valid_days', $this->attributes)) {
            $this->changeValidDaysRules();
        }
    }

    private function changeValidDaysRules()
    {
        /**
         * @var $rule \App\Models\Rule
         */
        $rule = $this->priceModifier->rules()->firstOrCreate([
            'name' => 'valid_days',
            'logical_operator' => 'OR',
        ]);

        $items = collect($this->attributes['valid_days'])->map(fn ($day) => $rule->items()->updateOrCreate([
            'context' => 'request',
            'context_property' => 'day',
            'operator' => 'EQUAL_TO',
            'value' => $day,
            'value_type' => 'int',
            'assertion' => true,
        ]));

        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        $rule->items()->whereNotIn('id', $items->map->id)->cursor()->each->delete();
    }
}
