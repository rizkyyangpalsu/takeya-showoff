<?php

namespace App\Jobs\Points;

use Illuminate\Bus\Queueable;
use App\Models\Route\Track\Point;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteExistingPoint
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\Route\Track\Point
     */
    public Point $point;

    /**
     * Create a new job instance.
     *
     * @param Point $point
     */
    public function __construct(Point $point)
    {
        $this->point = $point;
    }

    /**
     * Handle the job.
     */
    public function handle()
    {
        return $this->point->delete();
    }
}
