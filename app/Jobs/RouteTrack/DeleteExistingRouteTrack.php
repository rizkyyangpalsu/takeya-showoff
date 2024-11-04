<?php

namespace App\Jobs\RouteTrack;

use App\Models\Route;
use App\Models\Schedule\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\QueryException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Validation\ValidationException;

class DeleteExistingRouteTrack
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\Route
     */
    public Route $route;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        throw_if(
            Setting::query()->where('route_id', $this->route->id)->count() !== 0,
            ValidationException::withMessages([
                'route' => 'route is in use in schedule setting.'
            ])
        );

        try {
            $this->route->delete();
        } catch (QueryException $e) {
            // TODO: throw Validation if constrains exception occurred
            throw $e;
        }
    }
}
