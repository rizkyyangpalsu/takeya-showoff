<?php

namespace App\Models\Departure;

use App\Models\Departure;
use App\Models\Office\Staff;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Crew model.
 *
 * @property int $int
 * @property int $staff_id
 * @property string $role
 * @property float $stipend
 * @property int $departure_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\Departure $departure
 * @property-read \App\Models\Office\Staff $staff
 * @property int id
 */
class Crew extends Model
{
    use HashableId;

    public const ROLE_DRIVER = 'driver';
    public const ROLE_CO_DRIVER = 'co-driver';
    public const ROLE_MECHANIC = 'mechanic';
    public const ROLE_CREW = 'crew';

    protected $table = 'departure_crews';

    protected $fillable = [
        'departure_id',
        'staff_id',
        'role',
        'stipend',
    ];

    protected $casts = [
        'stipend' => 'float',
        'updated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'id',
        'staff_id',
        'departure_id',
    ];

    protected $appends = [
        'hash',
    ];

    /**
     * Get crew roles.
     *
     * @return string[]
     */
    public static function getCrewRoles(): array
    {
        return [
            self::ROLE_DRIVER,
            self::ROLE_CO_DRIVER,
            self::ROLE_MECHANIC,
            self::ROLE_CREW,
        ];
    }

    /**
     * Define `belongsTo` relationship with Departure model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class, 'departure_id', 'id');
    }

    /**
     * Define `belongsTo` relationship with Staff model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }
}
