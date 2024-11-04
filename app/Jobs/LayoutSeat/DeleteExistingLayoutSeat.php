<?php

namespace App\Jobs\LayoutSeat;

use App\Models\Fleet\Layout;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteExistingLayoutSeat
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\Fleet\Layout
     */
    public Layout $layout;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Fleet\Layout $layout
     */
    public function __construct(Layout $layout)
    {
        $this->layout = $layout;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $this->layout->delete();
    }
}
