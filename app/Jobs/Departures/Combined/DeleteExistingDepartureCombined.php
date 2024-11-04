<?php

namespace App\Jobs\Departures\Combined;

use App\Models\Departure\Combined;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteExistingDepartureCombined
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Combined
     */
    public Combined $combined;

    /**
     * Create a new job instance.
     *
     * @param Combined $combined
     */
    public function __construct(Combined $combined)
    {
        $this->combined = $combined;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->combined->journals()->delete();

        $this->combined->delete();
    }
}
