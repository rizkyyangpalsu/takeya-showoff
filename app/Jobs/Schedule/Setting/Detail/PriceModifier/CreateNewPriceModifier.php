<?php

namespace App\Jobs\Schedule\Setting\Detail\PriceModifier;

use App\Models\Route;
use Illuminate\Validation;
use Illuminate\Bus\Queueable;
use App\Jobs\Rule\CreateNewRule;
use App\Models\Schedule\Setting;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class CreateNewPriceModifier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Setting\Detail\PriceModifier|null
     */
    public ?Setting\Detail\PriceModifier $priceModifier;

    /**
     * @var Setting\Detail
     */
    private Setting\Detail $detail;

    private array $attributes;

    /**
     * CreateNewPriceModifier constructor.
     *
     * @param Setting\Detail $settingDetail
     * @param array $attributes
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(Setting\Detail $settingDetail, array $attributes = [])
    {
        $this->detail = $settingDetail;
        $this->attributes = Validator::make($attributes, [
            'priority' => [
                'required',
                Validation\Rule::unique(Setting\Detail\PriceModifier::class)->where('setting_detail_id', $settingDetail->id),
            ],
            'is_combined' => 'nullable|boolean',
            'name' => 'required|string',
            'display_text' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.price_hash' => ['required', new ExistsByHash(Route\Price::class)],
            'items.*.amount' => 'required|integer',
        ])->validate();

        $this->priceModifier = null;
    }

    public function handle()
    {
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->priceModifier = $this->detail->priceModifiers()->create(array_merge($this->attributes, [
            'route_id' => $this->detail->route->id,
        ]));

        CreateNewRule::dispatch($this->priceModifier, [
            'logical_operator' => 'AND',
        ]);

        SyncRoutePriceToPriceModifierItem::dispatch($this->priceModifier, $this->attributes['items'] ?? []);
    }
}
