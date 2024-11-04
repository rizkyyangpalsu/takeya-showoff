<?php

namespace App\Jobs\Accounting;

use Illuminate\Bus\Queueable;
use Illuminate\Validation\Rule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Dentro\Accounting\Entities\Account;

class UpdateExistingAccount implements ShouldQueue
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
     * @param Account $account
     * @param array $attributes
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(Account $account, array $attributes)
    {
        $this->account = $account;

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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->account->fill($this->attributes);

        $this->account->save();
    }
}
