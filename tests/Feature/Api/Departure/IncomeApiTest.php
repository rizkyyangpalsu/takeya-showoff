<?php

namespace Tests\Feature\Api\Departure;

use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;
use Illuminate\Database\Eloquent\Builder;

class IncomeApiTest extends DepartureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->getStaff());
    }

    public function testCanGetDepartureIncome()
    {
        $departure = $this->getDeparture();

        $response = $this->get(route('api.departure.income', [
            'departure_hash' => $departure->hash,
        ]));

        $response->assertJsonStructureIsFullPaginate();
    }

    public function testCanAddIncome()
    {
        $departure = $this->getDeparture();

        $office = $this->getOffice();

        $amount = 100000;

        $response = $this->post(route('api.departure.income.store', [
            'departure_hash' => $departure->hash,
        ]), [
            'office_hash' => $office->hash,
            'amount' => 100000,
            'description' => 'Penumpang baru',
        ]);

        $response->assertActionSuccess();

        $this->assertEquals($amount, $response->json('data.amount'), "Amount doesn't equal");
    }

    public function testCanUpdateExistingIncome()
    {
        $departure = $this->getDeparture();
        $office = $this->getOffice();

        $journal = $departure->journals()->whereHas('entries', function (Builder $builder) {
            $builder->whereHas('account', fn (Builder $builder) => $builder->where('type_code', Account::TYPE_REVENUE));
        })->first();

        $amount = 100000;

        $response = $this->put(route('api.departure.income.update', [
            'departure_hash' => $departure->hash,
            'journal_hash' => $journal->getAttribute('hash'),
        ]), [
            'office_hash' => $office->hash,
            'amount' => 100000,
            'description' => 'Penumpang baru',
        ]);

        $response->assertActionSuccess();

        $this->assertEquals($amount, $response->json('data.amount'), "Amount doesn't equal");
    }

    public function testCanDeleteExistingIncome()
    {
        $departure = $this->getDeparture();

        $before = Journal::query()->count();

        $journal = $departure->journals()->whereHas('entries', function (Builder $builder) {
            $builder->whereHas('account', fn (Builder $builder) => $builder->where('type_code', Account::TYPE_REVENUE));
        })->first();

        $this->assertDatabaseCount('journals', $before);

        $response = $this->delete(route('api.departure.income.destroy', [
            'departure_hash' => $departure->hash,
            'journal_hash' => $journal->getAttribute('hash'),
        ]));

        $response->assertActionSuccess();

        $this->assertSoftDeleted('journals', [
            'id' => $journal->getAttribute('id'),
        ]);
    }
}
