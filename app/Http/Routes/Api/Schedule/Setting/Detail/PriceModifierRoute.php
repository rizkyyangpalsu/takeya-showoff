<?php

namespace App\Http\Routes\Api\Schedule\Setting\Detail;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Schedule\Setting\Detail\PriceModifierController;

class PriceModifierRoute extends BaseRoute
{
    protected string $prefix = 'schedule/setting/{setting_hash}/detail/{setting_detail_hash}/prices';

    protected string $name = 'api.schedule.setting.detail.price';

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

        $this->router->post($this->prefix, [
            'as' => $this->name('store'),
            'uses' => $this->uses('store'),
        ]);

        $this->router->get($this->prefix('{price_modifier_hash}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->put($this->prefix('{price_modifier_hash}'), [
            'as' => $this->name('update'),
            'uses' => $this->uses('update'),
        ]);

        $this->router->patch($this->prefix('{price_modifier_hash}/rule'), [
            'as' => $this->name('update.rule'),
            'uses' => $this->uses('updateRule'),
        ]);

        $this->router->delete($this->prefix('{price_modifier_hash}'), [
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
        return PriceModifierController::class;
    }
}
