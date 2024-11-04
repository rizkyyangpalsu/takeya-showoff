<?php

namespace App\Jobs\Schedule\Setting\Detail\PriceModifier;

use App\Models\Route;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Schedule\Setting\Detail\PriceModifier;

class SyncRoutePriceToPriceModifierItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PriceModifier $priceModifier,
        private array $items = []
    ) {
    }

    public function handle()
    {
        $route = $this->priceModifier->route;
        $routePrices = $route->prices()->cursor();

        $routePrices->each(function (Route\Price $price) {
            // checking if price modifier exist or not
            $this->priceModifier->items()->firstOrCreate([
                'price_id' => $price->id,
            ], [
                'price_id' => $price->id,
                'amount' => Arr::get(
                    Arr::first($this->items, fn ($item) => $item['price_hash'] === $price->hash, ['amount' => 0]),
                    'amount',
                    0
                ),
            ]);
        });
    }
}
