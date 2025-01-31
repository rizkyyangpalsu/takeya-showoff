<?php

use Dentro\Accounting\Contracts\Account;

return [
    // separators x-xxx.xx
    'separators' => [
        'type' => '-',
        'group' => '.',
    ],
    'account_types' => [
        '1' => Account::TYPE_ASSET,
        '2' => Account::TYPE_LIABILITY,
        '3' => Account::TYPE_EQUITY,
        '4' => Account::TYPE_REVENUE,
        '5' => Account::TYPE_EXPENSE,
        '6' => Account::TYPE_OTHER,
    ],

    // Default model
    'models' => [
        'journal' => \App\Models\Accounting\Journal::class,
        'entry' => \Dentro\Accounting\Entities\Journal\Entry::class,
        'account' => \App\Models\Accounting\Account::class,
        'general_ledger' => \Dentro\Accounting\Entities\GeneralLedger::class,
    ],

    // Standard equation of accounting system
    // Dividend + Expenses + Asset = Liabilities + Owner's Equity + Revenue',
    'left' => [
        '1', '5',
    ],
    'right' => [
        '2', '3', '4',
    ],

    // Standard accounting statements
    'statements' => [
        'balance_sheet' => [
            'name' => 'Balance Sheet',
            'cash_only' => false,
            'accounts' => ['1', '2', '3', '4', '5'],
        ],
        'income' => [
            'name' => 'Income',
            'cash_only' => true,
            'accounts' => ['4', '5', '6'],
        ],
        'cash_flow' => [
            'name' => 'Cash Flow',
            'cash_only' => true,
            'with_journals' => true,
            'accounts' => ['1'],
        ],
    ],
];
