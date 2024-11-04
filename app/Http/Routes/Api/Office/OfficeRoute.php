<?php

namespace App\Http\Routes\Api\Office;

use Dentro\Yalr\BaseRoute;
use App\Http\Controllers\Api\Office\OfficeController;

class OfficeRoute extends BaseRoute
{
    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.office';

    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/office';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('{office_slug}/descendants'), [
            'as' => $this->name('descendants'),
            'uses' => $this->uses('descendants'),
        ]);

        $this->router->get($this->prefix('{office_slug}'), [
            'as' => $this->name('show'),
            'uses' => $this->uses('show'),
        ]);

        $this->router->get($this->prefix(), [
            'as' => $this->name,
            'uses' => $this->uses('index'),
        ]);

        $this->router->group(['middleware' => 'permission:manage office'], function () {
            $this->router->put($this->prefix('{office_slug}'), [
                'as' => $this->name('update'),
                'uses' => $this->uses('update'),
            ]);

            $this->router->delete($this->prefix('{office_slug}'), [
                'as' => $this->name('destroy'),
                'uses' => $this->uses('destroy'),
            ]);

            $this->router->post($this->prefix(), [
                'as' => $this->name('store'),
                'uses' => $this->uses('store'),
            ]);
        });
    }

    /**
     * Controller used by this route.
     *
     * @return string
     */
    public function controller(): string
    {
        return OfficeController::class;
    }
}
