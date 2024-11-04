<?php

namespace App\Jobs\Accounting;

use App\Models\Office;
use Illuminate\Bus\Queueable;
use Illuminate\Validation\Rule;
use App\Models\Accounting\Account;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateNewAccount
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
     * @var Account
     */
    public Account $account;

    /**
     * Create a new job instance.
     *
     * @param array $attributes
     * @param Office|null $office
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(array $attributes, ?Office $office = null)
    {
        if (! is_null($office)) {
            $attributes = array_merge($attributes, [
                'group_code' => $office->slug,
            ]);
        }

        $this->attributes = Validator::make($attributes, [
            'code' => 'required|integer',
            'description' => 'nullable|max:255',
            'type_code' => [
                'required', Rule::in([
                    Account::TYPE_ASSET,
                    Account::TYPE_EQUITY,
                    Account::TYPE_EXPENSE,
                    Account::TYPE_LIABILITY,
                    Account::TYPE_REVENUE,
                    Account::TYPE_OTHER,
                ]),
            ],
            'type_description' => 'nullable|max:255',
            'group_code' => 'nullable|max:255',
            'group_description' => 'nullable|max:255',
            'is_cash' => 'boolean',
        ])->validate();

        $this->account = new Account($this->attributes);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->account->save();
    }
}
