<?php

namespace App\Models\Geo;

use App\Models\Logistic\Price;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Regency model.
 *
 * @property int $id
 * @property int $country_id
 * @property int $province_id
 * @property string $name
 * @property string $capital
 * @property string $bsn_code
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read string                        $hash
 * @property-read \App\Models\Geo\Country|null  $country
 * @property-read \App\Models\Geo\Province|null $province
 */
class Regency extends Model
{
    use HashableId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'geo_regencies';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'country_id',
        'province_id',
        'name',
        'capital',
        'bsn_code',
    ];

    protected $hidden = [
        'id',
        'country_id',
        'province_id',
    ];

    protected $appends = [
        'hash',
    ];

    /**
     * Define `belongsTo` relationship with Province model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id', 'id');
    }

    /**
     * Define `belongsTo` relationship with Country model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function originCities(): HasMany
    {
        return $this->hasMany(Price::class, 'origin_city_id', 'id');
    }

    public function destinationCities(): HasMany
    {
        return $this->hasMany(Price::class, 'destination_city_id', 'id');
    }
}
