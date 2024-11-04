<?php

namespace App\Jobs\Accounting\Journal;

use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Validation\Rule;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;
use Dentro\Accounting\Ledger\Recorder;
use Illuminate\Validation\ValidationException;
use Veelasky\LaravelHashId\Rules\ExistsByHash;
use Dentro\Accounting\Contracts\Recordable;
use Dentro\Accounting\Contracts\EntryAuthor;
use Dentro\Accounting\Contracts\Journal\Entry;
use Dentro\Accounting\Exceptions\NotBalanceJournalEntryException;

class CreateNewJournal
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

    /**
     * Entry Author instance.
     *
     * @var EntryAuthor
     */
    public EntryAuthor $author;

    /**
     * Recordable instance.
     *
     * @var Recordable|null
     */
    public ?Recordable $recordable;

    /**
     * CreateNewJournal constructor.
     * @param array $attributes
     * @param EntryAuthor $author
     * @param Recordable|null $recordable
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(array $attributes, EntryAuthor $author, ?Recordable $recordable = null)
    {
        $this->attributes = Validator::make($attributes, [
            'group_code' => 'nullable',
            'entries' => 'required|array',
            'entries.*.account_hash' => ['required', new ExistsByHash(Account::class)],
            'entries.*.amount' => 'required|numeric|min:0',
            'entries.*.type' => ['required', Rule::in([Entry::TYPE_CREDIT, Entry::TYPE_DEBIT])],
            'entries.*.memo' => 'nullable',
            'entries.*.ref' => 'nullable',
            'memo' => 'nullable',
            'ref' => 'nullable',
        ])->validate();

        $this->author = $author;
        $this->recordable = $recordable;
    }

    /**
     * Handle the job.
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws ValidationException
     */
    public function handle(): bool
    {
        /** @var Recorder $recorder */
        $recorder = app()->make(Recorder::class);

        foreach ($this->attributes['entries'] as $entry) {
            // Fetch account
            $account = Account::byHashOrFail($entry['account_hash']);

            if ($entry['type'] === Entry::TYPE_DEBIT) {
                $recorder->debit($account, $entry['amount'], $this->author, Arr::get($entry, 'memo'), Arr::get($entry, 'ref'));
            } else {
                $recorder->credit($account, $entry['amount'], $this->author, Arr::get($entry, 'memo'), Arr::get($entry, 'ref'));
            }
        }

        try {
            /** @var Journal $journal */
            $journal = $recorder->record(
                $this->recordable,
                Arr::get($this->attributes['entries'][0], 'memo'),
                Arr::get($this->attributes, 'ref'),
                Arr::get($this->attributes, 'group_code'),
            );
        } catch (NotBalanceJournalEntryException $exception) {
            throw ValidationException::withMessages(['Entri yang dimasukkan tidak balance']);
        }

        // must be set if error not thrown
        $this->journal = $journal;

        return true;
    }
}
