<?php

namespace App\Jobs\Fleet;

use App\Models\Fleet;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Events\Fleet\DeletingFleet;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteExistingFleet
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Fleet instance.
     *
     * @var \App\Models\Fleet
     */
    public Fleet $fleet;

    /**
     * DeleteExistingFleet constructor.
     *
     * @param \App\Models\Fleet $fleet
     */
    public function __construct(Fleet $fleet)
    {
        $this->fleet = $fleet;
    }

    /**
     * Handle the job.
     */
    public function handle()
    {
        $fleet = $this->fleet;

        DB::transaction(function () use ($fleet) {
            event(new DeletingFleet($fleet));

            $fleet->delete();
        });
    }
}
