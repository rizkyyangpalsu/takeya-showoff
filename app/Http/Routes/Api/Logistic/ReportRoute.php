<?php

namespace App\Http\Routes\Api\Logistic;

use App\Http\Controllers\Api\Logistic\ReportController;
use Dentro\Yalr\BaseRoute;

class ReportRoute extends BaseRoute
{
    protected string $name = 'api.logistic.report';
    protected string $prefix = '/logistic/report';
    protected array|string $middleware = 'permission:view logistic report';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix(), [
            'as'    =>  $this->name,
            'uses'  =>  $this->uses('getTodayData'),
        ]);

        $this->router->get($this->prefix('range'), [
            'as'    =>  $this->name('range'),
            'uses'  =>  $this->uses('getRangeData'),
        ]);
    }

    public function controller(): string
    {
        return ReportController::class;
    }
}
