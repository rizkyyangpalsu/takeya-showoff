<?php

namespace App\Http\Routes\Api;

use App\Http\Controllers\Api\TemplateController;
use Dentro\Yalr\BaseRoute;

class TemplateRoute extends BaseRoute
{
    protected string $prefix = '/template';

    protected string $name = 'api.template';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix(), [
            'as'    =>  $this->name,
            'uses'  =>  $this->uses('index'),
        ]);

        $this->router->get($this->prefix('search-first'), [
            'as'    =>  $this->name('search-first'),
            'uses'  =>  $this->uses('searchFirst'),
        ]);

        $this->router->get($this->prefix('{template_hash}'), [
            'as'    =>  $this->name('show'),
            'uses'  =>  $this->uses('show'),
        ]);

        $this->router->post($this->prefix(), [
            'as'    =>  $this->name('store'),
            'uses'  =>  $this->uses('store'),
        ]);

        $this->router->put($this->prefix('{template_hash}'), [
            'as'    =>  $this->name('update'),
            'uses'  =>  $this->uses('update'),
        ]);

        $this->router->delete($this->prefix('{template_hash}'), [
            'as' => $this->name('destroy'),
            'uses' => $this->uses('destroy'),
        ]);
    }

    public function controller(): string
    {
        return TemplateController::class;
    }
}
