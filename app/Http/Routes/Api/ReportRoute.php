<?php

namespace App\Http\Routes\Api;

use Dentro\Yalr\BaseRoute;
use App\Actions\Office\GetOfficeFinancialReport;

class ReportRoute extends BaseRoute
{
    /**
     * Registered route name.
     *
     * @var string
     */
    protected string $name = 'api.report';

    /**
     * Route path prefix.
     *
     * @var string
     */
    protected string $prefix = '/report';

    /**
     * Register routes handled by this class.
     *
     * @return void
     */
    public function register(): void
    {
        $this->router->get($this->prefix('office'), [
            'as' => $this->name('office'),
            'uses' => GetOfficeFinancialReport::class,
        ]);
    }
}
