<?php

namespace App\Http\Routes\Api\Schedule\Setting\Detail;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Schedule\Setting\Detail\SeatsStateController;

class SeatsStateRoute extends BaseRoute
{
    protected string $prefix = 'schedule/setting/{setting_hash}/detail/{setting_detail_hash}/seats-state';

    protected string $name = 'api.schedule.setting.detail.seats-state';

    protected array|string $middleware = [
        'permission:manage schedule',
    ];

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix, [
            'as' => $this->name,
            'uses' => $this->uses('index'),
        ]);

        $this->router->patch($this->prefix, [
            'as' => $this->name('update'),
            'uses' => $this->uses('update'),
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return SeatsStateController::class;
    }
}
