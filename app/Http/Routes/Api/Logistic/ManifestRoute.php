<?php

namespace App\Http\Routes\Api\Logistic;

use App\Http\Controllers\Api\Logistic\ManifestController;
use Dentro\Yalr\BaseRoute;

class ManifestRoute extends BaseRoute
{
    protected string $name = 'api.logistic.manifest';
    protected string $prefix = '/logistic/manifest';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('get-item'), [
            'as'    =>  $this->name('get-item'),
            'uses'  =>  $this->uses('getItem'),
        ]);

        $this->router->get($this->prefix(), [
            'as'    =>  $this->name,
            'uses'  =>  $this->uses('index'),
        ]);

        $this->router->get($this->prefix('{logistic_manifest_hash}'), [
            'as'    =>  $this->name('show'),
            'uses'  =>  $this->uses('show'),
        ]);

        $this->router->post($this->prefix(), [
            'as'    =>  $this->name('create-or-update'),
            'uses'  =>  $this->uses('createOrUpdate'),
        ]);

        $this->router->post($this->prefix('remove-item'), [
            'as'    =>  $this->name('remove-item'),
            'uses'  =>  $this->uses('removeItem'),
        ]);

        $this->router->post($this->prefix('expedition/{logistic_manifest_hash}'), [
            'as'    =>  $this->name('expedition'),
            'uses'  =>  $this->uses('changeToExpedition'),
        ]);

        $this->router->get($this->prefix('in-bound/{receipt}'), [
            'as'    =>  $this->name('scan-in-bound'),
            'uses'  =>  $this->uses('scanInBound'),
        ]);

        $this->router->post($this->prefix('unloading'), [
            'as'    =>  $this->name('unloading'),
            'uses'  =>  $this->uses('unloading'),
        ]);

        $this->router->delete($this->prefix('{logistic_manifest_hash}'), [
            'as' => $this->name('destroy'),
            'uses' => $this->uses('destroy'),
        ]);
    }

    public function controller(): string
    {
        return ManifestController::class;
    }
}
