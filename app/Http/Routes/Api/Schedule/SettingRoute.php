<?php

namespace App\Http\Routes\Api\Schedule;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Schedule\SettingController;

class SettingRoute extends BaseRoute
{
    protected string $prefix = 'schedule/setting';

    protected string $name = 'api.schedule.setting';

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

        $this->router->get($this->prefix('{setting_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->post($this->prefix, [
            'as' => $this->name('store'),
            'uses' => $this->uses('store'),
        ]);

        $this->router->put($this->prefix('{setting_hash}'), [
            'as' => $this->name('update'),
            'uses' => $this->uses('update'),
        ]);

        $this->router->post($this->prefix('{setting_hash}'), [
            'as' => $this->name('duplicate'),
            'uses' => $this->uses('duplicate'),
        ]);

        $this->router->delete($this->prefix('{setting_hash}'), [
            'as' => $this->name('destroy'),
            'uses' => $this->uses('destroy'),
        ]);

        $this->router->patch($this->prefix('{setting_hash}'), [
            'as' => $this->name('reactivate'),
            'uses' => $this->uses('reactivate'),
        ]);

        $this->router->patch($this->prefix('{setting_hash}/deactivate'), [
            'as' => $this->name('deactivate'),
            'uses' => $this->uses('deactivate'),
        ]);
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return SettingController::class;
    }
}
