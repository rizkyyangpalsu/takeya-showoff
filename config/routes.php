<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Preloads
    |--------------------------------------------------------------------------
    | String of class name that instance of \Dentro\Yalr\Contracts\Bindable
    | Preloads will always been called even when laravel routes has been cached.
    | It is the best place to put Rate Limiter and route binding related code.
    */

    'preloads' => [
        App\Http\Routes\Binder::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Router group settings
    |--------------------------------------------------------------------------
    | Groups are used to organize and group your routes. Basically the same
    | group that used in common laravel route.
    |
    | 'group_name' => [
    |     // laravel group route options can contains 'middleware', 'prefix',
    |     // 'as', 'domain', 'namespace', 'where'
    | ]
    */

    'groups' => [
        'pure' => [],
        'web' => [
            'middleware' => 'web',
            'prefix' => '',
        ],
        'api-public' => [
            'middleware' => ['api'],
            'prefix' => 'v1',
        ],
        'api' => [
            'middleware' => ['api', 'auth:sanctum'],
            'prefix' => 'v1',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    | Below is where our route is loaded, it read `groups` section above.
    | keys in this array are the name of route group and values are string
    | class name either instance of \Dentro\Yalr\Contracts\Bindable or
    | controller that use attribute that inherit \Dentro\Yalr\RouteAttribute
    */


    'pure' => [
        App\Http\Routes\MobileRoute::class,
    ],

    'web' => [
        App\Http\Routes\DefaultRoute::class,
        App\Http\Routes\SanctumRoute::class,
        /** @inject web **/
    ],
    'api-public' => [
        App\Http\Routes\Api\Public\ItemRoute::class,
        App\Http\Routes\Api\Public\DeliveryRoute::class,
    ],
    'api' => [
        App\Http\Routes\Api\Accounting\AccountRoute::class,
        App\Http\Routes\Api\Accounting\JournalRoute::class,
        App\Http\Routes\Api\Accounting\StatementRoute::class,
        App\Http\Routes\Api\Accounting\LedgerRoute::class,
        App\Http\Routes\Api\Schedule\TrackRoute::class,
        App\Http\Routes\Api\Schedule\PointRoute::class,
        App\Http\Routes\Api\Schedule\SettingRoute::class,
        App\Http\Routes\Api\Schedule\Setting\DetailRoute::class,
        App\Http\Routes\Api\Schedule\Setting\Detail\PriceModifierRoute::class,
        App\Http\Routes\Api\Office\OfficeRoute::class,
        App\Http\Routes\Api\Office\UserRoute::class,
        App\Http\Routes\Api\Office\FleetRoute::class,
        App\Http\Controllers\Api\Office\FinanceController::class,
        App\Http\Routes\Api\Fleet\LayoutRoute::class,
        App\Http\Routes\Api\Fleet\FleetRoute::class,
        App\Http\Routes\Api\UserRoute::class,
        App\Http\Routes\Api\CustomerRoute::class,
        App\Http\Routes\Api\StaffRoute::class,
        App\Http\Routes\Api\ProfileRoute::class,
        App\Http\Routes\Api\ConstantRoute::class,
        App\Http\Routes\Api\ZiggyRoute::class,
        App\Http\Routes\Api\TransactionRoute::class,
        App\Http\Routes\Api\Departure\CombinedRoute::class,
        App\Http\Routes\Api\DepartureRoute::class,
        App\Http\Routes\Api\Departure\CrewRoute::class,
        App\Http\Routes\Api\Schedule\Setting\Detail\SeatsStateRoute::class,
        App\Http\Routes\Api\Schedule\ReservationRoute::class,
        App\Http\Routes\Api\Departure\AllowanceRoute::class,
        App\Http\Routes\Api\Departure\PassengerRoute::class,
        App\Http\Routes\Api\Departure\TransactionRoute::class,
        App\Http\Routes\Api\Departure\CostRoute::class,
        App\Http\Routes\Api\Departure\IncomeRoute::class,
        App\Http\Routes\Api\GeoRoute::class,
        App\Http\Routes\Api\ReportRoute::class,
        App\Http\Routes\Api\BankAccountRoute::class,
        App\Http\Routes\Api\Logistic\ServiceRoute::class,
        App\Http\Routes\Api\Logistic\PriceRoute::class,
        App\Http\Routes\Api\Logistic\DeliveryRoute::class,
        App\Http\Routes\Api\Logistic\ManifestRoute::class,
        App\Http\Routes\Api\Logistic\ItemRoute::class,
        App\Http\Routes\Api\Logistic\ReportRoute::class,
        App\Http\Routes\Api\TemplateRoute::class,
        App\Http\Routes\Api\NotificationRoute::class,
        /** @inject api **/
    ],
];
