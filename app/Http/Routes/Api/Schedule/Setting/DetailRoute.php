<?php

namespace App\Http\Routes\Api\Schedule\Setting;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Schedule\Setting\DetailController;

class DetailRoute extends BaseRoute
{
    protected string $prefix = 'schedule/setting/{setting_hash}/detail';

    protected string $name = 'api.schedule.setting.detail';

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

        $this->router->get($this->prefix('{setting_detail_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->post($this->prefix, [
            'as' => $this->name('store'),
            'uses' => $this->uses('store'),
        ]);

        $this->router->put($this->prefix('{setting_detail_hash}'), [
            'as' => $this->name('update'),
            'uses' => $this->uses('update'),
        ]);

        $this->router->delete($this->prefix('{setting_detail_hash}'), [
            'as' => $this->name('destroy'),
            'uses' => $this->uses('destroy'),
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return DetailController::class;
    }
}
