<?php

namespace App\Models\Schedule\Setting\Detail;

use Carbon\Carbon;
use App\Models\Route;
use App\Concerns\HasRules;
use App\Models\Route\Price;
use App\Models\Route\Track;
use Illuminate\Support\Fluent;
use App\Models\Route\Track\Point;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Collection;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Schedule\Setting\Detail\PriceModifier\Item;

/**
 * Class PriceModifier.
 *
 * @property  int id
 * @property  int setting_detail_id
 * @property  int priority
 * @property  bool is_combined
 * @property  string name
 * @property  string display_text
 * @property  Carbon created_at
 * @property  Carbon updated_at
 * @property-read  string hash
 * @property-read  Collection items
 * @property-read  Collection tracks
 * @property  bool is_shown
 * @property  \App\Models\Route route
 */
class PriceModifier extends Model
{
    use HasFactory, HasRules, HashableId;

    protected $table = 'price_modifiers';

    protected $fillable = [
        'route_id',
        'priority',
        'is_combined',
        'is_shown',
        'name',
        'display_text',
    ];

    protected $hidden = [
        'route_id',
        'setting_detail_id',
        'id',
    ];

    protected $appends = [
        'hash',
    ];

    protected $casts = [
        'priority' => 'int',
        'is_combined' => 'bool',
        'is_shown' => 'bool',
    ];

    public function route(): Relations\BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function tracks(): Relations\HasManyThrough
    {
        return $this->hasManyThrough(Route\Track::class, Route::class, 'id', 'route_id', 'route_id', 'id');
    }

    public function items(): Relations\HasMany
    {
        return $this->hasMany(PriceModifier\Item::class);
    }

    public function getPricesTableAttribute(): Collection | \Illuminate\Support\Collection
    {
        $this->makeHidden('items');
        $this->makeHidden('tracks');

        if (! $this->relationLoaded('items')) {
            $this->load('items.price');
        }

        if (! $this->relationLoaded('tracks')) {
            $this->load(['tracks.origin', 'tracks.destination']);
        }

        // get a point sorted by depart to arrival
        $points = $this->tracks->map(fn (Track $track) => [$track->origin, $track->destination])->flatten(1)
            ->unique('id')
            ->values();

        $items = $this->items
            ->groupBy(fn (PriceModifier\Item $item) => $item->price->origin_id)
            ->map(fn (Collection $collection) => $collection
                ->groupBy(fn (PriceModifier\Item $item) => $item->price->destination_id));

        return $points->map(fn (Point $origin) => new Fluent([
            'origin' => $origin->makeHidden(['created_at', 'updated_at']),
            'destinations' => $points->values()
                ->map(function (Point $destination) use ($origin, $items) {
                    /** @var Item|null $item */
                    $item = $items->get($origin->id)?->get($destination->id)?->first();

                    /** @var Price $price */
                    $price = optional($item)->price;

                    return new Fluent([
                        'price_hash' => optional($price)->hash,
                        'destination' => $destination->makeHidden(['created_at', 'updated_at']),
                        'nominal' => (float) optional($price)->nominal,
                        'modifier' => (int) optional($item)->amount,
                    ]);
                }),
        ]))->values();
    }

    /**
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     * @noinspection PhpUnused
     */
    public function getInterpretedRulesAttribute(): ?array
    {
        if ($this->priority === 1) {
            return null;
        }

        $getValidDaysRules = function () {
            $rule = $this->rules()->where('name', 'valid_days')->first();
            if (! $rule) {
                return null;
            }

            return $rule->items()->cursor()->map->value;
        };

        return [
            'valid_days' => $getValidDaysRules(),
        ];
    }

    protected static function boot()
    {
        parent::boot();

        self::addGlobalScope('order_priority', function (Builder $builder) {
            $builder->orderBy('priority');
        });
    }
}
