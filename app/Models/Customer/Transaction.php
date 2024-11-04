<?php

namespace App\Models\Customer;

use App\Http\Controllers\Api\QRCodeController;
use App\Models\Attachment;
use App\Models\User;
use App\Models\Office;
use App\Contracts\HasLayout;
use App\Models\Fleet\Layout;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use App\Models\Schedule\Reservation;
use Illuminate\Database\Eloquent\Model;
use App\Models\Customer\Transaction\Item;
use Illuminate\Database\Eloquent\Relations;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Dentro\Accounting\Concerns\HasJournals;
use Dentro\Accounting\Contracts\Recordable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Transaction.
 *
 * @property  int  id
 * @property  string  code
 * @property  int  total_passenger
 * @property  int  total_price
 * @property  string  payment_method
 * @property  array  payment_data
 * @property  string  status
 * @property  \Carbon\Carbon  expired_at
 * @property  \Carbon\Carbon  canceled_at
 * @property  \Carbon\Carbon  paid_at
 * @property-read  User  user
 * @property-read  Reservation  reservation
 * @property-read  \Illuminate\Database\Eloquent\Collection<int, Transaction\Passenger>  passengers
 * @property  Layout  layout
 * @property-read  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Schedule\Reservation\Trip>  trips
 * @property-read  \Illuminate\Database\Eloquent\Collection<int, Item>  items
 * @property-read  string  qr_url
 * @property  array additional_data
 * @property  \Carbon\Carbon $created_at
 * @property  string $type
 */
class Transaction extends Model implements HasLayout, Recordable
{
    use HasFactory, HashableId, HasJournals, SoftDeletes;

    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_PENDING = 'pending';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DISCARD = 'discard';
    public const STATUS_REVERSAL = 'reversal';
    public const STATUS_COMPLETE = 'complete';

    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REFUND = 'refund';

    public const PAYMENT_METHOD_AGENT = 'agent';
    public const PAYMENT_METHOD_TRANSFER = 'transfer';

    protected $table = 'transactions';

    protected $fillable = [
        'code',
        'total_passenger',
        'total_price',
        'payment_method',
        'payment_data',
        'status',
        'expired_at',
        'canceled_at',
        'paid_at',
        'total_revenue',
        'total_reversal'
    ];

    protected $dates = [
        'expired_at',
        'canceled_at',
        'paid_at',
    ];

    protected $casts = [
        'payment_data' => 'array',
        'total_price' => 'float',
        'total_passenger' => 'int',
        'additional_data' => 'array',
    ];

    protected $hidden = [
        'id',
        'reservation_id',
        'user_id',
        'payment_data',
        'layout',
    ];

    protected $appends = [
        'hash',
        'qr_url',
    ];

    protected $with = [
        'office',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELED,
            self::STATUS_DISCARD,
            self::STATUS_PAID,
            self::STATUS_REVERSAL,
        ];
    }

    public function user(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function reservation(): Relations\BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function layout(): Relations\HasOneThrough
    {
        return $this->hasOneThrough(Layout::class, Reservation::class, 'id', 'id', 'reservation_id', 'layout_id');
    }

    public function trips(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Reservation\Trip::class, 'transaction_trip')->orderBy('index');
    }

    public function office(): Relations\BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id', 'id');
    }

    public function passengers(): Relations\HasMany
    {
        return $this->hasMany(Transaction\Passenger::class);
    }

    public function items(): Relations\HasMany
    {
        return $this->hasMany(Item::class, 'transaction_id', 'id');
    }

    public function attachments(): Relations\HasMany
    {
        return $this->hasMany(Attachment::class, 'transaction_id', 'id');
    }

    public function getLayout(): Layout
    {
        return $this->layout;
    }

    public function getSeatsAttribute(): Collection
    {
        $passengersSeatId = $this->passengers->pluck('seat_id');

        return $this->layout->seats->map(fn (Layout\Seat $seat) => array_merge($seat->toArray(), [
            'status' => match (true) {
                $passengersSeatId->contains($seat->id) && $this->status === self::STATUS_PAID => 'occupy',
                $passengersSeatId->contains($seat->id) => 'book',
                default => 'available',
            },
        ]));
    }

    public function getQrUrlAttribute(): string
    {
        return action([QRCodeController::class, 'index'], [
            'code' => $this->code,
        ]);
    }

    public static function getEligiblePaymentMethod(User|Customer $user): array
    {
        if (in_array($user->user_type, [
            User::USER_TYPE_AGENT,
            User::USER_TYPE_SUPER_ADMIN,
            User::USER_TYPE_ADMIN,
            User::USER_TYPE_STAFF_BUS,
        ], true)) {
            return [
                self::PAYMENT_METHOD_AGENT,
                self::PAYMENT_METHOD_TRANSFER,
            ];
        }

        return [
            self::PAYMENT_METHOD_TRANSFER,
        ];
    }
}
