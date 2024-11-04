<?php

namespace App\Models\Geo;

use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Country model.
 *
 * @property int                                                                         $id
 * @property string                                                                      $name
 * @property string                                                                      $alpha2
 * @property string                                                                      $alpha3
 * @property string                                                                      $numeric
 * @property \Carbon\Carbon                                                              $created_at
 * @property \Carbon\Carbon                                                              $updated_at
 *
 * @property-read string                                                                 $hash
 * @property-read \App\Models\Geo\Province[]|\Illuminate\Database\Eloquent\Collection    $provinces
 * @property-read \App\Models\Geo\Regency[]|\Illuminate\Database\Eloquent\Collection     $regencies
 */
class Country extends Model
{
    use HashableId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'geo_countries';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'alpha2',
        'alpha3',
        'numeric',
    ];

    protected $hidden = [
        'id',
    ];

    /**
     * Define `hasMany` relationship with Province model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class, 'country_id', 'id');
    }

    /**
     * Define `hasMany` relationship with City model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function regencies(): HasMany
    {
        return $this->hasMany(Regency::class, 'country_id', 'id');
    }
}
