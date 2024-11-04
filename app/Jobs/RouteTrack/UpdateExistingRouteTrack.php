<?php

namespace App\Jobs\RouteTrack;

use Closure;
use App\Models\Route;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use App\Models\Route\Track\Point;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Validation\ValidationException;
use Veelasky\LaravelHashId\Rules\ExistsByHash;
use App\Models\Schedule\Setting\Detail\PriceModifier;
use App\Jobs\Schedule\Setting\Detail\PriceModifier\SyncRoutePriceToPriceModifierItem;

class UpdateExistingRouteTrack
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Route
     */
    public Route $route;

    public array $attributes;

    /**
     * Create a new job instance.
     *
     * @param Route $route
     * @param array $attributes
     * @throws ValidationException
     */
    public function __construct(Route $route, array $attributes)
    {
        $this->attributes = Validator::make($attributes, [
            'name' => 'required',
            'tracks' => 'required|array',
            'tracks.*.origin_hash' => ['required', new ExistsByHash(Point::class)],
            'tracks.*.destination_hash' => ['required', new ExistsByHash(Point::class)],
            'tracks.*.duration' => 'nullable|numeric',
            'tracks.*.destination_transit_duration' => 'nullable|numeric',
            'prices' => 'required|array',
            'prices.*.origin_hash' => ['required', new ExistsByHash(Point::class)],
            'prices.*.destinations' => 'required|array',
            'prices.*.destinations.*.hash' => ['required', new ExistsByHash(Point::class)],
            'prices.*.destinations.*.nominal' => 'required|numeric',
        ])->after(CreateNewRouteTrack::ensureAllPriceIsFilledInEachPoints($attributes['tracks'], $attributes['prices']))
            ->validate();

        $this->route = $route;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->route->update(Arr::only($this->attributes, ['name']));

        $usesTracks = collect($this->attributes['tracks'])
            ->map(fn (array $data) => array_merge($data, [
                'origin_id' => Route\Track\Point::hashToId($data['origin_hash']),
                'destination_id' => Route\Track\Point::hashToId($data['destination_hash']),
            ]))
            ->except(['origin_hash', 'destination_hash'])
            ->map(fn (array $data, $index) => $this->route->tracks()->updateOrCreate(
                ['route_id' => $this->route->id, 'index' => $index],
                array_merge($data, ['index' => $index])
            ));

        $this->route->tracks()->whereNotIn('id', $usesTracks->map->id)->delete();

        dispatch(self::priceUpdater($this->route, $this->attributes['prices']));
    }

    private static function priceUpdater(Route $route, array $prices): Closure
    {
        return function () use ($route, $prices) {
            $usesPrices = collect($prices)->map(function (array $data) use ($route) {
                $originId = Point::hashToId($data['origin_hash']);

                return collect($data['destinations'])->map(
                    fn (array $data) => $route->prices()->updateOrCreate(
                        [
                            'origin_id' => $originId,
                            'destination_id' => Point::hashToId($data['hash']),
                        ],
                        Arr::except(
                            array_merge(
                                $data,
                                [
                                    'origin_id' => $originId,
                                    'destination_id' => Point::hashToId($data['hash']),
                                ]
                            ),
                            'hash'
                        )
                    )
                );
            })->flatten(1);

            // remove unused prices
            $route->prices()->whereNotIn('id', $usesPrices->map->id)->delete();

            // sync price modifier that related to this route
            $route->priceModifiers()->cursor()
                ->each(fn (PriceModifier $priceModifier) => dispatch_sync(new SyncRoutePriceToPriceModifierItem($priceModifier)));
        };
    }
}
