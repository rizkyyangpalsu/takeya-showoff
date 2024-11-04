<?php

namespace App\Models\Logistic;

use App\Models\Geo\Regency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;

/**
 * @property \Carbon\Carbon|mixed|null $created_at
 * @property \Carbon\Carbon|mixed|null $updated_at
 */
class Price extends Model
{
    use HasFactory;
    use HashableId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'logistic_prices';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'origin_city_id',
        'destination_city_id',
        'logistic_service_id',
        'title_type',
        'type',
        'type_weight',
        'type_volume',
        'price_calc_type',
        'price_calc_type_value',

        'price_weight',
        'min_weight',
        'price_volume',
        'min_volume'
    ];

    protected $appends = [
        'hash',
    ];

    protected $hidden = [
        'id',
        'destination_city_id',
        'logistic_service_id',
    ];

    public function originCity()
    {
        return $this->belongsTo(Regency::class, 'origin_city_id');
    }

    public function destinationCity()
    {
        return $this->belongsTo(Regency::class, 'destination_city_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'logistic_service_id');
    }
}
