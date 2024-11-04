<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Province model.
 *
 * @property int                                                                         $id
 * @property int                                                                         $country_id
 * @property string                                                                      $name
 * @property string                                                                      $iso_code
 * @property \Carbon\Carbon                                                              $created_at
 * @property \Carbon\Carbon                                                              $updated_at
 *
 * @property-read string                                                                 $hash
 * @property-read \App\Models\Geo\Country|null                                           $country
 * @property-read \App\Models\Geo\Regency[]|\Illuminate\Database\Eloquent\Collection     $regencies
 */
class Province extends Model
{
    use HashableId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'geo_provinces';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'country_id',
        'name',
        'iso_code',
    ];

    protected $hidden = [
        'id',
        'country_id',
    ];

    protected $appends = [
        'hash',
    ];

    /**
     * Define `belongsTo` relationship with Country model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    /**
     * Define `hasMany` relationship with City model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function regencies(): HasMany
    {
        return $this->hasMany(Regency::class, 'province_id', 'id');
    }
}
