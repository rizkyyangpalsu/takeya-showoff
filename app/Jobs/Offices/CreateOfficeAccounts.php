<?php

namespace App\Jobs\Offices;

use App\Models\Office;
use League\Csv\Reader;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\Accounting\CreateNewAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateOfficeAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Office instance.
     *
     * @var Office
     */
    public Office $office;

    /**
     * Create a new job instance.
     *
     * @param Office $office
     */
    public function __construct(Office $office)
    {
        $this->office = $office;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     * @throws \League\Csv\Exception
     */
    public function handle(): void
    {
        $path = storage_path('app/accounting/accounts.csv');

        $csv = Reader::createFromPath($path);
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $record) {
            dispatch(new CreateNewAccount([
                'code' => $record['code'],
                'description' => $record['description'],
                'type_code' => $record['type_code'],
                'group_code' => $this->office->id,
                'is_cash' => $record['is_cash'],
            ]));
        }
    }
}
