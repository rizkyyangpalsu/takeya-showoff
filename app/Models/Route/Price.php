<?php

namespace App\Models\Route;

use App\Models\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use App\Models\Schedule\Setting\Detail\PriceModifier;

/**
 * Class Track.
 *
 * @property  int id
 * @property  float base_price
 * @property  float nominal
 * @property-read  Track\Point origin
 * @property-read  Track\Point destination
 * @property-read  string hash
 * @property  int origin_id
 * @property  int destination_id
 */
class Price extends Model
{
    use HashableId;

    protected $fillable = [
        'nominal',
        'route_id',
        'origin_id',
        'destination_id',
    ];

    protected $appends = [
        'hash',
    ];

    protected $hidden = [
        'id',
        'route_id',
        'origin_id',
        'destination_id',
        'schedule_setting_detail_id',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
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

    public function modifiers(): Relations\HasMany
    {
        return $this->hasMany(PriceModifier\Item::class, 'price_id', 'id');
    }
}
