<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Models\Office\Staff;
use App\Concerns\Geo\HasGeoColumn;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use App\Models\Departure\Allowance;
use App\Models\Geo\Regency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\SoftDeletes;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Dentro\Accounting\Concerns\HasEntries;
use Dentro\Accounting\Contracts\EntryAuthor;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Company Model.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $address
 * @property int $parent
 * @property bool $has_workshop
 * @property bool $has_warehouse
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 *
 * @property-read string $hash
 * @property-read \App\Models\Fleet[]|\Illuminate\Database\Eloquent\Collection $fleets
 * @property string $code
 * @property int|string $province_id
 * @property int|string $regency_id
 */
class Office extends Model implements EntryAuthor
{
    use SoftDeletes, HashableId, HasFactory, HasEntries, HasGeoColumn;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'has_workshop',
        'has_warehouse',
        'office_id',
        'regency_id',
        'province_id',
        'code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'office_id' => 'int',
        'deleted_at' => 'datetime',
        'has_workshop' => 'boolean',
        'has_warehouse' => 'boolean',
        'income' => 'float',
        'expense' => 'float',
        'allowances' => 'float',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'hash',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [
        'parent',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'office_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'province_id',
        'regency_id',
    ];

    /** {@inheritdoc} */
    public static function boot()
    {
        parent::boot();

        self::creating(function (self $office) {
            if (! $office->slug) {
                $office->slug = Str::slug($office->name).'-'.Str::random(4);
            }
        });
    }

    /**
     * Define `hasMany` relationship with Fleet model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fleets(): Relations\HasMany
    {
        return $this->hasMany(Fleet::class, 'base_id', 'id');
    }

    /**
     * Define `belongsToMany` relationship with user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'office_user', 'office_id', 'user_id');
    }

    /**
     * Define `hasMany` relationship with account model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts(): Relations\HasMany
    {
        return $this->hasMany(Account::class, 'group_code', 'id');
    }

    /**
     * Define `belongsTo` relationship with office model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'office_id', 'id');
    }

    /**
     * Define `hasMany` relationship with office model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descendants(): Relations\HasMany
    {
        return $this->hasMany(self::class, 'office_id', 'id');
    }

    /**
     * Define `hasMany` relationship with journal model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function journals(): Relations\HasMany
    {
        return $this->hasMany(Journal::class, 'group_code', 'id');
    }

    /**
     * Define `hasMany` relationship with Allowance model.
     *
     * @return Relations\HasMany
     */
    public function allowances(): Relations\HasMany
    {
        return $this->hasMany(Allowance::class, 'office_id', 'id');
    }

    /**
     * Scope for search.
     *
     * @param Builder $builder
     * @param $keyword
     * @return Builder
     */
    public function scopeSearch(Builder $builder, $keyword): Builder
    {
        return $builder->where('name', 'like', '%'.$keyword.'%');
    }

    public function regency(): Relations\BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_id', 'id');
    }
}
