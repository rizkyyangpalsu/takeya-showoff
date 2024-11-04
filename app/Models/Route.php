<?php

namespace App\Models;

use App\Models\Route\Track;
use App\Models\Schedule\Setting;
use Illuminate\Support\Fluent;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use App\Models\Schedule\Setting\Detail\PriceModifier;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Route.
 *
 * @property int $id
 * @property string $name
 * @property float $total_price
 * @property-read string $hash
 * @property-read \Illuminate\Database\Eloquent\Collection<Route\Track> $tracks
 * @property-read \Illuminate\Database\Eloquent\Collection<Route\Price> $prices
 * @property-read  Collection $prices_table
 */
class Route extends Model
{
    use HasFactory, HashableId;

    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'id',
        'laravel_through_key',
    ];

    protected $appends = [
        'hash',
        'points_count',
    ];

    public function tracks(): Relations\HasMany
    {
        return $this->hasMany(Route\Track::class);
    }

    public function prices(): Relations\HasMany
    {
        return $this->hasMany(Route\Price::class);
    }

    public function priceModifiers(): Relations\HasMany
    {
        return $this->hasMany(PriceModifier::class, 'route_id', 'id');
    }

    public function settings(): Relations\HasMany
    {
        return $this->hasMany(Setting::class, 'route_id', 'id');
    }

    public function getPointsCountAttribute()
    {
        return ! empty($this->tracks_count) ? $this->tracks_count + 1 : null;
    }

    public function getPricesTableAttribute(): ?Collection
    {
        // hidden relation price due to those functional can be replaced with prices_table attribute
        $this->makeHidden('prices');

        // get a point sorted by depart to arrival
        $points = $this->tracks->map(fn (Track $track) => [$track->origin, $track->destination])->flatten(1)
            ->unique('id')
            ->values();

        $prices = $this->prices
            ->groupBy(fn (Route\Price $price) => $price->origin_id)
            ->map(fn (Collection $collection) => $collection->groupBy(fn (Route\Price $price) => $price->destination_id));

        return $points->map(fn (Track\Point $origin) => new Fluent([
            'origin' => $origin->makeHidden(['id', 'created_at', 'updated_at']),
            'destinations' => $points->values()
                ->map(fn (Track\Point $destination) => new Fluent([
                    'destination' => $destination->makeHidden(['id', 'created_at', 'updated_at']),
                    'nominal' => (float) $prices->get($origin->id)?->get($destination->id)?->first()?->nominal,
                ])),
        ]));
    }
}
