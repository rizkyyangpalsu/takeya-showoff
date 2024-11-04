<?php

namespace App\Models\Schedule\Reservation;

use App\Contracts\HasLayout;
use App\Models\Fleet\Layout;
use App\Models\Fleet\Layout\Seat;
use Illuminate\Support\Collection;
use App\Casts\SeatConfigurationJson;
use App\Models\Customer\Transaction;
use App\Models\Schedule\Reservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;
use Veelasky\LaravelHashId\Eloquent\HashableId;

/**
 * Class Trip.
 *
 * @property  \Carbon\Carbon departure
 * @property  \Carbon\Carbon arrival
 * @property  \App\Support\SeatConfigurator seat_configuration
 * @property  string origin_id
 * @property  string destination_id
 * @property  int index
 * @property-read  Reservation reservation
 * @property-read  \Illuminate\Database\Eloquent\Collection transactions
 * @property-read  \Illuminate\Database\Eloquent\Collection bookers
 * @property-read  \App\Models\Schedule\Reservation\Trip $origin
 * @property  string reservation_id
 * @property  \App\Models\Schedule\Reservation\Trip $destination
 * @property  int id
 */
class Trip extends Model implements HasLayout
{
    use HashableId;

    protected $table = 'schedule_reservation_trips';

    protected $fillable = [
        'reservation_id',
        'origin_id',
        'destination_id',
        'index',
        'origin',
        'destination',
        'departure',
        'arrival',
        'seat_configuration',
    ];

    protected $casts = [
        'origin' => 'object',
        'destination' => 'object',
        'departure' => 'datetime',
        'arrival' => 'datetime',
        'seat_configuration' => SeatConfigurationJson::class,
    ];

    protected $hidden = [
        'id',
        'seat_configuration',
        'reservation_id',
        'index',
        'origin_id',
        'destination_id',
        'pivot',
        'reservation',
    ];

    protected $appends = [
        'hash',
    ];

    public function reservation(): Relations\BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id', 'id');
    }

    public function transactions(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'transaction_trip')->where('status', Transaction::STATUS_PAID);
    }

    public function getLayout(): Layout
    {
        return $this->reservation->layout;
    }

    public function getSeatsStateAttribute(): Collection
    {
        $seatConfigurator = $this->seat_configuration;
        $bookers = $this->bookers;
        $office = $this->office;

        return $this->reservation->layout->seats->map(fn (Seat $seat, $index) => array_merge(
            $seat->makeHidden(['created_at', 'updated_at'])->toArray(),
            [
                'status' => match (true) {
                    in_array($seat->id, $seatConfigurator->getReserved(), true) => 'reserved',
                    in_array($seat->id, $seatConfigurator->getOccupied(), true) => 'occupied',
                    in_array($seat->id, $seatConfigurator->getBooked(), true) => 'booked',
                    in_array($seat->id, $seatConfigurator->getUnavailable(), true) => 'unavailable',
                    default => 'available',
                },
                'booker' => $bookers->get($index)['booker'] ?? null,
                'office' => $office->get($index)['office'] ?? null,
            ]
        ));
    }

    public function getBookersAttribute(): Collection
    {
        $transactions = $this->transactions;

        $passengers = $transactions->load('user', 'passengers')->map(function (Transaction $transaction) {
            $transaction->passengers->each(fn (Transaction\Passenger $passenger) => $passenger->setRelation('user', $transaction->user));

            return $transaction->passengers;
        })->flatten(1);

        $passengerSeats = $passengers->mapWithKeys(fn (Transaction\Passenger $passenger) => [(string) $passenger->seat_id => $passenger->user]);

        return $this->reservation->layout->seats->map(fn (Seat $seat) => array_merge($seat->toArray(), [
            'booker' => $passengerSeats->get((string) $seat->id),
        ]));
    }

    public function getOfficeAttribute(): Collection
    {
        $transactions = $this->transactions;

        $passengers = $transactions->load('office', 'passengers')->map(function (Transaction $transaction) {
            $transaction->passengers->each(fn (Transaction\Passenger $passenger) => $passenger->setRelation('office', $transaction->office));

            return $transaction->passengers;
        })->flatten(1);

        $passengerSeats = $passengers->mapWithKeys(fn (Transaction\Passenger $passenger) => [(string) $passenger->seat_id => $passenger->office]);

        return $this->reservation->layout->seats->map(fn (Seat $seat) => array_merge($seat->toArray(), [
            'office' => $passengerSeats->get((string) $seat->id),
        ]));
    }

    protected static function boot()
    {
        parent::boot();

        self::addGlobalScope('order_index', fn (Builder $builder) => $builder->orderBy('index'));
    }
}
