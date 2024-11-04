<?php

namespace App\Jobs\Offices;

use App\Models\Office;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteExistingOffice
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Office instance.
     *
     * @var \App\Models\Office
     */
    public Office $office;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Office $office
     */
    public function __construct(Office $office)
    {
        $this->office = $office;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $this->office->delete();
    }
}
