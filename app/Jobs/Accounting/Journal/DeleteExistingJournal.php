<?php

namespace App\Jobs\Accounting\Journal;

use Illuminate\Bus\Queueable;
use App\Models\Accounting\Journal;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteExistingJournal
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Attributes.
     *
     * @var array
     */
    public array $attributes;

    /**
     * Account instance.
     *
     * @var Journal
     */
    public Journal $journal;

    public function __construct(Journal $journal)
    {
        $this->journal = $journal;
    }

    /**
     * Handle job.
     *
     * @throws \Exception
     */
    public function handle()
    {
        foreach ($this->journal->entries as $entry) {
            $entry->delete();
        }

        return $this->journal->delete();
    }
}
