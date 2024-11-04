<?php

namespace Tests\Feature\Api\Departure;

use App\Models\Office;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use Illuminate\Database\Eloquent\Builder;

class CostApiTest extends DepartureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->getStaff());
    }

    public function testCanGetDepartureCost()
    {
        $departure = $this->getDeparture();

        $response = $this->get(route('api.departure.cost', [
            'departure_hash' => $departure->hash,
        ]));

        $response->assertJsonStructureIsFullPaginate();
    }

    public function testCanAddCost()
    {
        $departure = $this->getDeparture();

        $office = $this->getOffice();

        $expense1 = 20000;
        $expense2 = 80000;

        $response = $this->post(route('api.departure.cost.store', [
            'departure_hash' => $departure->hash,
        ]), [
            'office_hash' => $office->hash,
            'expenses' => [
                [
                    'account_hash' => $this->getChartOfAccount($office)->hash,
                    'amount' => $expense1,
                    'description' => 'Expense 1',
                ],
                [
                    'account_hash' => $this->getChartOfAccount($office)->hash,
                    'amount' => $expense2,
                    'description' => 'Expense 2',
                ],
            ],
        ]);

        $response->assertActionSuccess();

        $this->assertEquals($expense1 + $expense2, $response->json('data.amount'), "Amount doesn't equal");
    }

    public function testCanUpdateExistingCost()
    {
        $departure = $this->getDeparture();
        $office = $this->getOffice();

        $journal = $departure->journals()->whereHas('entries', function (Builder $builder) {
            $builder->whereHas('account', fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE));
        })->first();

        $expense1 = 20000;
        $expense2 = 80000;

        $response = $this->put(route('api.departure.cost.update', [
            'departure_hash' => $departure->hash,
            'journal_hash' => $journal->getAttribute('hash'),
        ]), [
            'office_hash' => $office->hash,
            'expenses' => [
                [
                    'account_hash' => $this->getChartOfAccount($office)->hash,
                    'amount' => $expense1,
                    'description' => 'Expense 1',
                ],
                [
                    'account_hash' => $this->getChartOfAccount($office)->hash,
                    'amount' => $expense2,
                    'description' => 'Expense 2',
                ],
            ],
        ]);

        $response->assertActionSuccess();

        $this->assertEquals($expense1 + $expense2, $response->json('data.amount'), "Amount doesn't equal");
    }

    public function testCanDeleteExistingCost()
    {
        $departure = $this->getDeparture();

        $before = Journal::query()->count();

        $journal = $departure->journals()->whereHas('entries', function (Builder $builder) {
            $builder->whereHas('account', fn (Builder $builder) => $builder->where('type_code', Account::TYPE_EXPENSE));
        })->first();

        $this->assertDatabaseCount('journals', $before);

        $response = $this->delete(route('api.departure.cost.destroy', [
            'departure_hash' => $departure->hash,
            'journal_hash' => $journal->getAttribute('hash'),
        ]));

        $response->assertActionSuccess();

        $this->assertSoftDeleted('journals', [
            'id' => $journal->getAttribute('id'),
        ]);
    }

    private function getChartOfAccount(Office $office): Account
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $office
            ->accounts()
            ->where('group_code', $office->id)
            ->inRandomOrder()
            ->first();
    }
}
