<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Customer\Transaction;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Collection;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Dentro\Accounting\Concerns\HasEntries;
use Dentro\Accounting\Contracts\EntryAuthor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Class User.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $user_type
 * @property \Carbon\Carbon $email_verified_at
 * @property string $password
 * @property string $remember_token
 * @property array $additional_data
 * @property-read \Illuminate\Database\Eloquent\Collection<Customer\Transaction> $transactions
 * @property-read \Illuminate\Database\Eloquent\Collection<Office> $offices
 * @property-read \Illuminate\Database\Eloquent\Collection<Permission> $permissions
 * @method static permission(string $permissionName)
 */
class User extends Authenticatable implements EntryAuthor
{
    use HashableId;
    use HasEntries;
    use HasRoles;
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use SoftDeletes;
    use MustVerifyEmail {
        hasVerifiedEmail as parentHasVerifiedEmail;
    }

    public const USER_TYPE_SUPER_ADMIN = 'SUPER_ADMIN';
    public const USER_TYPE_ADMIN = 'ADMIN';
    public const USER_TYPE_STAFF_WAREHOUSE = 'STAFF_WAREHOUSE';
    public const USER_TYPE_STAFF_WAREHOUSE_DRIVER = 'STAFF_WAREHOUSE_DRIVER';
    public const USER_TYPE_STAFF_WORKSHOP = 'STAFF_WORKSHOP';
    public const USER_TYPE_STAFF_BUS = 'STAFF_BUS';
    public const USER_TYPE_CUSTOMER = 'CUSTOMER';
    public const USER_TYPE_AGENT = 'AGENT';

    protected $table = 'users';

    protected string $hashKey = 'USER_STRING_BASE_HASH_KEY';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'additional_data',
        'user_type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'original_offices',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The additional attributes that should be appended.
     *
     * @var string[]
     */
    protected $appends = [
        'hash',
        'profile_photo_url',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'additional_data' => 'array',
    ];

    public static function getUserTypes(): array
    {
        return [
            self::USER_TYPE_SUPER_ADMIN,
            self::USER_TYPE_ADMIN,
            self::USER_TYPE_STAFF_WAREHOUSE,
            self::USER_TYPE_STAFF_WAREHOUSE_DRIVER,
            self::USER_TYPE_STAFF_WORKSHOP,
            self::USER_TYPE_STAFF_BUS,
            self::USER_TYPE_CUSTOMER,
            self::USER_TYPE_AGENT,
        ];
    }

    public function transactions(): Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function original_offices(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Office::class, 'office_user', 'user_id', 'office_id')
            ->as('workplaces');
    }

    public function getOfficesAttribute(): Collection | array
    {
        if ($this->user_type === self::USER_TYPE_SUPER_ADMIN) {
            return Office::query()->get();
        }

        return $this->original_offices()->get();
    }

    public function getOfficesQuery(): Builder|Relations\BelongsToMany
    {
        if ($this->user_type === self::USER_TYPE_SUPER_ADMIN) {
            return Office::query();
        }

        return $this->original_offices();
    }


    public function getOfficeAttribute(): ?Office
    {
        return $this->original_offices()->first();
    }

    /** Attribute
     * Set attribute mutator for `password`.
     *
     * @param $value
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = bcrypt($value);
    }

    /**
     * Scope for search.
     *
     * @param Builder $builder
     * @param $keyword
     * @return void
     */
    public function scopeSearch(Builder $builder, $keyword): void
    {
        $builder->where('name', 'like', '%'.$keyword.'%');
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->user_type !== self::USER_TYPE_CUSTOMER || ! is_null($this->email_verified_at);
    }

    /** {@inheritdoc} */
    public static function boot()
    {
        parent::boot();

        self::saved(function (self $user) {
            $user->syncRoles([$user->user_type]);
        });
    }
}
