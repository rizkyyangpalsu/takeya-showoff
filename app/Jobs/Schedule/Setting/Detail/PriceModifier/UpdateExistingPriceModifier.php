<?php

namespace App\Jobs\Schedule\Setting\Detail\PriceModifier;

use App\Models\Route;
use Illuminate\Validation;
use Illuminate\Bus\Queueable;
use App\Models\Schedule\Setting;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Validation\ValidationException;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class UpdateExistingPriceModifier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Setting\Detail\PriceModifier|null
     */
    public Setting\Detail\PriceModifier $priceModifier;

    private array $attributes;

    /**
     * CreateNewPriceModifier constructor.
     *
     * @param Setting\Detail\PriceModifier $priceModifier
     * @param array $attributes
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function __construct(Setting\Detail\PriceModifier $priceModifier, array $attributes = [])
    {
        $this->attributes = Validator::make($attributes, [
            'priority' => [
                'nullable',
                Validation\Rule::unique(Setting\Detail\PriceModifier::class)->where('setting_detail_id', $priceModifier->setting_detail_id)->ignore($priceModifier->id),
            ],
            'is_combined' => 'nullable|boolean',
            'name' => 'nullable|string',
            'display_text' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.price_hash' => ['required', new ExistsByHash(Route\Price::class)],
            'items.*.amount' => 'required|integer',
        ])->validate();

        if (array_key_exists('priority', $this->attributes)) {
            throw_if($priceModifier->priority == 1, ValidationException::withMessages(['priority' => __('cannot modify primary price modifier')]));
        }

        $this->priceModifier = $priceModifier;
    }

    public function handle()
    {
        $this->priceModifier->update($this->attributes);

        if (array_key_exists('items', $this->attributes)) {
            collect($this->attributes['items'])->each(function (array $attribute) {
                $priceId = Route\Price::hashToId($attribute['price_hash']);

                $this->priceModifier->items()->updateOrCreate([
                    'price_id' => $priceId,
                ], [
                    'price_id' => $priceId,
                    'amount' => $attribute['amount'],
                ]);
            });
        }
    }
}
