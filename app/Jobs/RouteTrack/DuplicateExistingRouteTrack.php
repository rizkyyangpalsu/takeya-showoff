<?php

namespace App\Jobs\RouteTrack;

use App\Jobs\Schedule\Setting\Detail\PriceModifier\SyncRoutePriceToPriceModifierItem;
use App\Models\Route;
use App\Models\Schedule\Setting\Detail\PriceModifier;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DuplicateExistingRouteTrack
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\Route
     */
    public Route $route;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        // Duplicate Route
        $newRoute = $this->route->replicate();
        $newRoute->name = $this->route->name.' (copy)';
        $newRoute->save();

        // Duplicate Route Tracks
        $this->route->tracks->each(function ($track) use ($newRoute) {
            $newTrack = $track->replicate();
            $newTrack->route_id = $newRoute->id;
            $newTrack->save();
        });

        // Duplicate Route Prices
        $this->route->prices->each(function ($price) use ($newRoute) {
            $newPrice = $price->replicate();
            $newPrice->route_id = $newRoute->id;
            $newPrice->save();
        });

        // sync price modifier that related to this route
        $this->route->priceModifiers()->cursor()
            ->each(fn (PriceModifier $priceModifier) => dispatch_sync(new SyncRoutePriceToPriceModifierItem($priceModifier)));
    }
}
