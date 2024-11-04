<?php

use App\Models\User;

return [
    // Accounting
    'accounting' => [
        'unearned_revenue' => 40001,
        'revenue' => 40000,
        'cash' => 10000,
        'prepaid_expenses' => 10402,
        'commission' => 60004,
    ],

    /**
     * Permissions
     * ------------------------ GLOBAL SCOPE -------------------------
     *																|
     *	SUPER_ADMIN													|
     * 	-- CAN ANYTHING												|
     *																|
     *	ADMIN														|
     *	-- CAN MANAGE OPERATIONAL MATTER							|
     *																|
     *	ACCOUNTANT 													|
     *	-- CAN MANAGE ACCOUNTING MATTER								|
     *																|
     *	STAFF_BUS													|
     *	-- DOING BUS OPERATIONAL									|
     *														        |
     *	AGENT														|
     *	-- DOING TRANSACTION AND VIEWING TRANSACTION				|
     *																|
     *	STAFF_WAREHOUSE												|
     *	-- DOING WAREHOUSE OPERATIONAL								|
     *		                                                        |
     *	STAFF_WAREHOUSE_DRIVER										|
     *	-- DOING WAREHOUSE DRIVE OPERATIONAL							|
     *																|
     * ---------------------------------------------------------------.
     */
    'permission_group' => [
        'report' => [
            'view office report',
            'view global report',
        ],
        'accounting' => [
            'manage accounting',
        ],
        'office' => [
            'manage office',
        ],
        'user' => [
            'manage user',
        ],
        'schedule' => [
            'manage schedule',
        ],
        'route-track' => [
            'manage track',
        ],
        'point' => [
            'manage point',
        ],
        'fleet' => [
            'manage fleet',
        ],
        'layout' => [
            'manage layout',
        ],
        'departure' => [
            'create departure',
            'update departure',
            'delete departure',
            'manage departure crew',
            'manage departure allowance',
            'manage departure income',
            'manage departure cost',
            'manage departure combined',
        ],
        'customer' => [
            'manage customer',
        ],
        'transaction' => [
            'inquiry',
            'reserve',
            'manage transaction',
        ],
        'reservation' => [
            'manage reservation',
        ],
        'bank-account' => [
            'manage bank accounts'
        ],
        'logistic' => [
            'manage logistic services',
            'manage logistic prices',
            'manage logistic delivery',
            'view logistic manifest',
            'create logistic outbound',
            'create logistic inbound',
            'view logistic items',
            'view logistic report',
        ]
    ],

    'roles' => [
        User::USER_TYPE_SUPER_ADMIN => [
            '*',
        ],
        User::USER_TYPE_ADMIN => [
            'report.view office report',
            'departure.*',
            'user.*',
            'departure.*',
            'customer.*',
            'transaction.*',
            'logistic.*',
        ],
        User::USER_TYPE_STAFF_BUS => [
            'departure.manage departure crew',
            'departure.manage departure allowance',
            'departure.manage departure income',
            'departure.manage departure cost',
            'transaction.*',
            'customer.*',
        ],
        User::USER_TYPE_AGENT => [
            'transaction.*',
            'customer.*',
        ],
        User::USER_TYPE_STAFF_WORKSHOP => [

        ],
        User::USER_TYPE_CUSTOMER => [
            'transaction.inquiry',
            'transaction.reserve',
        ],
        User::USER_TYPE_STAFF_WAREHOUSE => [
            'logistic.manage logistic delivery',
            'logistic.view logistic items',
            'logistic.view logistic report',
        ],
        User::USER_TYPE_STAFF_WAREHOUSE_DRIVER => [
            'logistic.view logistic manifest',
            'create logistic outbound',
            'create logistic inbound',
        ],
    ],
];
