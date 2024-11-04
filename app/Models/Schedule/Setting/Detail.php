<?php

namespace App\Models\Schedule\Setting;

use App\Models\Route;
use App\Contracts\HasLayout;
use App\Models\Fleet\Layout;
use App\Models\Schedule\Setting;
use App\Support\SeatConfigurator;
use App\Casts\SeatConfigurationJson;
use App\Models\Schedule\Reservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Collection;
use Veelasky\LaravelHashId\Eloquent\HashableId;

/**
 * Class Detail.
 *
 * @property  int $id
 * @property  string $departure
 * @property  int $fleet
 * @property-read  Setting $setting
 * @property-read  Layout $layout
 * @property-read  string hash
 * @property-read  Route route
 * @property  int setting_id
 * @property-read  Collection priceModifiers
 * @property-read  Collection reservations
 * @property  SeatConfigurator seat_configuration
 * @property  string layout_id
 */
class Detail extends Model implements HasLayout
{
    use HashableId;

    protected $table = 'schedule_setting_details';

    protected $fillable = [
        'departure',
        'fleet',
    ];

    protected $hidden = [
        'id',
        'setting_id',
        'layout_id',
        'seat_configuration',
    ];

    protected $casts = [
        'fleet' => 'int',
        'seat_configuration' => SeatConfigurationJson::class,
    ];

    protected $appends = [
        'hash',
    ];

    public function route(): Relations\HasOneThrough
    {
        return $this->hasOneThrough(Route::class, Setting::class, 'id', 'id', 'setting_id', 'route_id');
    }

    public function setting(): Relations\BelongsTo
    {
        return $this->belongsTo(Setting::class);
    }

    public function layout(): Relations\BelongsTo
    {
        return $this->belongsTo(Layout::class);
    }

    public function reservations(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'setting_detail_reservation');
    }

    public function priceModifiers(): Relations\HasMany
    {
        return $this->hasMany(Detail\PriceModifier::class, 'setting_detail_id');
    }

    public function priceModifierItems(): Relations\HasManyThrough
    {
        return $this->hasManyThrough(Detail\PriceModifier\Item::class, Detail\PriceModifier::class);
    }

    /** {@inheritdoc} */
    public function getLayout(): Layout
    {
        return $this->layout;
    }
}
