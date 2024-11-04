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
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class CreateNewRouteTrack
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $attributes;

    /**
     * @var \App\Models\Route
     */
    public Route $route;

    /**
     * Create a new job instance.
     *
     * @param $attributes
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct($attributes)
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
        ])->after(self::ensureAllPriceIsFilledInEachPoints($attributes['tracks'], $attributes['prices']))->validate();

        $this->route = new Route();
    }

    public static function ensureAllPriceIsFilledInEachPoints(array $tracks, array $inputPrices): Closure
    {
        $orderedPoints = collect($tracks)
            ->map(fn (array $data) => [$data['origin_hash'], $data['destination_hash']])
            ->flatten()
            ->unique()
            ->values();

        $prices = collect($inputPrices);

        return static function (\Illuminate\Validation\Validator $validator) use ($orderedPoints, $prices) {
            $validator->errors()->addIf(
                $orderedPoints->count() - 1 !== $prices->count(),
                'prices',
                __('Given prices doesn\'t match with given tracks.')
            );

            $prices->each(function (array $data) use ($orderedPoints, $validator) {
                ['origin_hash' => $originHash, 'destinations' => $destinations] = $data;
                $destinationsHash = collect($destinations)->pluck('hash');
                $indexPoint = $orderedPoints->flip()->get($originHash);
                $orderedPoints->slice($indexPoint + 1)
                    ->each(fn ($destinationHash) => $validator->errors()->addIf(
                        ! $destinationsHash->contains($destinationHash),
                        'prices.origin.'.$originHash.'.destination.'.$destinationHash,
                        __('Missing destination '.$destinationHash.' for origin '.$originHash)
                    ));
            });
        };
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->route->fill(Arr::only($this->attributes, ['name']));
        $this->route->save();

        collect($this->attributes['tracks'])->map(fn (array $data) => array_merge($data, [
            'origin_id' => Point::hashToId($data['origin_hash']),
            'destination_id' => Point::hashToId($data['destination_hash']),
        ]))->except(['origin_hash', 'destination_hash'])
            ->each(fn (array $data, $index) => $this->route->tracks()->create(array_merge($data, ['index' => $index])));

        $route = $this->route;
        $prices = $this->attributes['prices'];

        dispatch(self::pricesCreator($route, $prices));
    }

    private static function pricesCreator(Route $route, array $prices): Closure
    {
        return static function () use ($route, $prices) {
            collect($prices)->each(function (array $data) use ($route) {
                $originId = Point::hashToId($data['origin_hash']);
                collect($data['destinations'])->each(
                    fn (array $data) => $route->prices()->create(
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
            });
        };
    }
}
