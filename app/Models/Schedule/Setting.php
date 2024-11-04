<?php

namespace App\Models\Schedule;

use App\Models\Schedule\Setting\Detail;
use App\Models\Schedule\Setting\Detail\PriceModifier;
use App\Support\ScheduleSettingOptions;
use Carbon\Carbon;
use App\Models\Route;
use Illuminate\Database\Eloquent\Model;
use App\Casts\ScheduleSettingOptionsJson;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Veelasky\LaravelHashId\Eloquent\HashableId;

/**
 * Class Setting.
 *
 * @property int id
 * @property string name
 * @property int priority
 * @property Carbon started_at
 * @property Carbon expired_at
 * @property-read Route route
 * @property-read Collection<Setting\Detail> details
 * @property int route_id
 * @property string hash
 * @property ScheduleSettingOptions options
 */
class Setting extends Model
{
    use HashableId, SoftDeletes;

    protected $table = 'schedule_settings';

    protected $fillable = [
        'name',
        'options',
        'started_at',
        'expired_at',
        'priority',
    ];

    protected $hidden = [
        'id',
        'route_id',
    ];

    protected $dates = [
        'started_at',
        'expired_at',
    ];

    protected $casts = [
        'priority' => 'int',
        'options' => ScheduleSettingOptionsJson::class,
    ];

    protected $appends = [
        'hash',
    ];

    public function route(): Relations\BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function prices(): Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            Route\Price::class,
            Route::class,
            'id',
            'route_id',
            'route_id',
            'id'
        );
    }

    public function priceModifiers(): Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            PriceModifier::class,
            Detail::class,
            'setting_id',
            'setting_detail_id',
        );
    }

    public function details(): Relations\HasMany
    {
        return $this->hasMany(Setting\Detail::class);
    }

    protected static function boot()
    {
        parent::boot();

        self::addGlobalScope('order_priority', function (Builder $builder) {
            $builder->orderBy('priority');
        });
    }
}
