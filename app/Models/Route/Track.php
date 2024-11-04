<?php

namespace App\Models\Route;

use App\Models\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Track.
 *
 * @property  int $id
 * @property  float $base_price
 * @property  int $duration
 * @property  int $destination_transit_duration
 * @property  string origin_id
 * @property  string destination_id
 * @property-read  Track\Point $origin
 * @property-read  Track\Point $destination
 * @property-read  null|object $pivot
 * @property-read  string $hash
 * @property  int index
 */
class Track extends Model
{
    use HasFactory, HashableId;

    protected $fillable = [
        'duration',
        'destination_transit_duration',
        'route_id',
        'index',
        'origin_id',
        'destination_id',
    ];

    protected $appends = [
        'hash',
    ];

    protected $hidden = [
        'id',
        'route_id',
        'index',
        'origin_id',
        'destination_id',
    ];

    protected $casts = [
        'duration' => 'integer',
    ];

    public function route(): Relations\BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function origin(): Relations\BelongsTo
    {
        return $this->belongsTo(Track\Point::class, 'origin_id');
    }

    public function destination(): Relations\BelongsTo
    {
        return $this->belongsTo(Track\Point::class, 'destination_id');
    }

    protected static function boot()
    {
        parent::boot();

        self::addGlobalScope('order_index', fn (Builder $builder) => $builder->orderBy('index'));
    }
}
