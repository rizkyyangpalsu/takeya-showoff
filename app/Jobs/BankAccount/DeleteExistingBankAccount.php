<?php

namespace App\Jobs\BankAccount;

use App\Models\BankAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteExistingBankAccount
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * @var BankAccount
     */
    public BankAccount $bankAccount;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BankAccount $bankAccount)
    {
        $this->bankAccount = $bankAccount;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle()
    {
        return $this->bankAccount->delete();
    }
}
