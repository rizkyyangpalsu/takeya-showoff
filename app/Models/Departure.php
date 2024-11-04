<?php

namespace App\Models;

use App\Models\Departure\Crew;
use App\Models\Route\Track\Point;
use App\Models\Schedule\Reservation;
use App\Models\Schedule\Reservation\Trip;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Dentro\Accounting\Concerns\HasJournals;
use Dentro\Accounting\Contracts\Recordable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Departure class.
 *
 * @property int|string $id
 * @property int $reservation_id
 * @property int $fleet_id
 * @property int $origin_id
 * @property int $destination_id
 * @property string $name
 * @property string $type
 * @property string $status
 * @property Carbon $departure_time
 * @property Carbon $arrival_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * @property-read Reservation|null $reservation
 * @property-read Fleet|null $fleet
 * @property-read Point|null $origin
 * @property-read Point|null $destination
 * @property-read Collection|Crew[] $crews
 */
class Departure extends Model implements Recordable
{
    use HashableId, SoftDeletes, HasJournals;

    public const DEPARTURE_TYPE_SCHEDULED = 'scheduled';
    public const DEPARTURE_TYPE_CHARTERED = 'chartered';
    public const DEPARTURE_TYPE_OTHER = 'other';

    public const DEPARTURE_STATUS_PLANNED = 'planned';
    public const DEPARTURE_STATUS_ONGOING = 'ongoing';
    public const DEPARTURE_STATUS_COMPLETED = 'completed';

    protected $table = 'departures';

    protected $fillable = [
        'reservation_id',
        'origin_id',
        'destination_id',
        'fleet_id',
        'name',
        'type',
        'status',
        'distance',
        'allowance',
        'departure_time',
        'arrival_time',
    ];

    protected $casts = [
        'distance' => 'float',
        'allowance' => 'float',
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'updated_at' => 'datetime',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'id',
        'deleted_at',
        'fleet_id',
        'origin_id',
        'destination_id',
        'reservation_id',
    ];

    protected $appends = [
        'hash',
    ];

    /**
     * Get departure type.
     *
     * @return string[]
     */
    public static function getDepartureTypes(): array
    {
        return [
            self::DEPARTURE_TYPE_SCHEDULED,
            self::DEPARTURE_TYPE_CHARTERED,
            self::DEPARTURE_TYPE_OTHER,
        ];
    }

    /**
     * Get departure status.
     *
     * @return string[]
     */
    public static function getDepartureStatus(): array
    {
        return [
            self::DEPARTURE_STATUS_PLANNED,
            self::DEPARTURE_STATUS_ONGOING,
            self::DEPARTURE_STATUS_COMPLETED,
        ];
    }

    /**
     * Define `belongsTo` relationship with Reservation model.
     *
     * @return BelongsTo
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id', 'id');
    }

    /**
     * Define `belongsTo` relationship with Point Id.
     *
     * @return BelongsTo
     */
    public function origin(): BelongsTo
    {
        return $this->belongsTo(Point::class, 'origin_id', 'id');
    }

    /**
     * Define `belongsTo` relationship with Point Id.
     *
     * @return BelongsTo
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Point::class, 'destination_id', 'id');
    }

    /**
     * Define `belongsTo` relationship with Fleet Id.
     *
     * @return BelongsTo
     */
    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class, 'fleet_id', 'id');
    }

    /**
     * Define `hasMany` relationship with Staff (crew) model.
     *
     * @return HasMany
     */
    public function crews(): HasMany
    {
        return $this->hasMany(Departure\Crew::class, 'departure_id', 'id');
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(Departure\Allowance::class);
    }

    public function reservationPassengers(): HasManyThrough
    {
        $query = $this->reservation?->passengers();

        if ($this->origin_id !== null && $this->destination_id !== null) {
            /** @var Trip $tripOrigin */
            $tripOrigin = $this->reservation->trips()->where('origin_id', $this->origin_id)->first();
            /** @var Trip $tripDestination */
            $tripDestination = $this->reservation->trips()->where('destination_id', $this->destination_id)->first();

            $query->whereHas(
                'transaction',
                fn (Builder $transactionQuery) => $transactionQuery->whereHas(
                    'trips',
                    fn (Builder $tripQuery) => $tripQuery->whereBetween('index', [$tripOrigin->index, $tripDestination->index])
                )
            );
        }

        return $query;
    }

    public function reservationTransaction(): HasMany
    {
        $query = $this->reservation?->transactions();

        if ($query && $this->origin_id !== null && $this->destination_id !== null) {
            /** @var Trip $tripOrigin */
            $tripOrigin = $this->reservation->trips()->where('origin_id', $this->origin_id)->first();
            /** @var Trip $tripDestination */
            $tripDestination = $this->reservation->trips()->where('destination_id', $this->destination_id)->first();

            $query->whereHas(
                'trips',
                fn (Builder $tripQuery) => $tripQuery->whereBetween('index', [$tripOrigin->index, $tripDestination->index])
            );
        }

        return $query;
    }
}
