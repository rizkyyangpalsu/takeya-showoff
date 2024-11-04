<?php

namespace App\Models\Schedule;

use Carbon\Carbon;
use App\Models\Fleet;
use App\Models\Route;
use App\Models\Departure;
use App\Contracts\HasLayout;
use App\Models\Fleet\Layout;
use App\Support\SeatConfigurator;
use App\Models\Customer\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Veelasky\LaravelHashId\Eloquent\HashableId;

/**
 * Class Reservation.
 *
 * @property  int id
 * @property  string code
 * @property  Carbon departure_schedule
 * @property  int reserved
 * @property  int booked
 * @property-read  Route route
 * @property-read  Layout layout
 * @property-read  Fleet|null fleet
 * @property-read  \Illuminate\Database\Eloquent\Collection<Setting\Detail> setting_details
 * @property-read  \Illuminate\Database\Eloquent\Collection passengers
 * @property-read  Departure|null departure
 * @property  SeatConfigurator seat_configuration
 * @property  int index
 * @property  \Illuminate\Database\Eloquent\Collection<\App\Models\Schedule\Reservation\Trip> trips
 */
class Reservation extends Model implements HasLayout
{
    use HashableId;

    protected $table = 'schedule_reservations';

    protected $fillable = [
        'code',
        'departure_schedule',
    ];

    protected $casts = [
        'departure_schedule' => 'datetime',
    ];

    protected $hidden = [
        'id',
        'pivot',
        'index',
        'route_id',
        'layout_id',
        'fleet_id',
    ];

    protected $appends = [
        'hash',
    ];

    /**
     * reservation can have more than 1 setting details in case of overshadow
     * BIG NOTE : if there is more than 1 setting details, it should be compatible with route and layout.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function setting_details(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Setting\Detail::class, 'setting_detail_reservation');
    }

    public function route(): Relations\BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    public function layout(): Relations\BelongsTo
    {
        return $this->belongsTo(Layout::class, 'layout_id', 'id');
    }

    public function fleet(): Relations\BelongsTo
    {
        return $this->belongsTo(Fleet::class, 'fleet_id', 'id');
    }

    public function transactions(): Relations\HasMany
    {
        return $this->hasMany(Transaction::class, 'reservation_id', 'id')
            ->where('status', Transaction::STATUS_PAID);
    }

    public function passengers(): Relations\HasManyThrough
    {
        return $this->hasManyThrough(Transaction\Passenger::class, Transaction::class)
            ->whereHas('transaction', fn (Builder $builder) => $builder->where('status', Transaction::STATUS_PAID));
    }

    public function trips(): Relations\HasMany
    {
        return $this->hasMany(Reservation\Trip::class);
    }

    public function departure(): Relations\HasOne
    {
        return $this->hasOne(Departure::class, 'reservation_id', 'id');
    }

    /** {@inheritdoc} */
    public function getLayout(): Layout
    {
        return $this->layout;
    }
}
