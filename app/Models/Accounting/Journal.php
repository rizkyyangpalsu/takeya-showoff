<?php

namespace App\Models\Accounting;

use App\Models\Office;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Dentro\Accounting\Entities\Journal as BaseJournal;

/**
 * @property string note
 */
class Journal extends BaseJournal
{
    use HashableId;

    protected $hidden = [
        'id',
    ];

    protected $appends = [
        'hash',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'group_code', 'id');
    }
}
