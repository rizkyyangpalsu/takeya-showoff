<?php

namespace App\Models\Departure;

use App\Models\Departure;
use App\Models\Office;
use App\Models\User;
use Dentro\Accounting\Concerns\HasJournals;
use Dentro\Accounting\Contracts\Recordable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;

class Combined extends Model  implements Recordable
{
    use HasFactory, HashableId, HasJournals;

    protected $table = 'departure_combined';

    protected $fillable = ['name', 'user_id', 'office_id', 'total_allowances', 'total_incomes', 'total_costs', 'additional_data'];

    protected $hidden = ['id', 'user_id', 'office_id'];

    protected $appends = ['hash'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function departures(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Departure::class, 'departure_combined_pivot', 'combined_id', 'departure_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function office(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
