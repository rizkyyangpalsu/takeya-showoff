<?php

namespace App\Jobs\BankAccount;

use App\Models\BankAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateNewBankAccount
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Filtered attributes.
     *
     * @var array
     */
    public array $attributes;

    /**
     * Instance Of BankAccount.
     *
     * @var BankAccount
     */
    public BankAccount $bankAccount;

    /**
     * Create a new job instance.
     *
     * @param array $attributes
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(array $attributes)
    {
        $this->attributes = Validator::make($attributes, [
            'name' => 'required',
            'account' => ['required',
                Rule::unique('bank_accounts')->where(function ($query) use ($attributes) {
                    return $query->where('name', $attributes['name'])->where('account', $attributes['account']);
                }),
            ],
            'bank_code' => 'required',
        ])->validate();

        $this->bankAccount = new BankAccount();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->bankAccount->fill($this->attributes);

        $this->bankAccount->save();
    }
}
