<?php

namespace Tests\Feature\Api\Transaction;

use Tests\TestCase;
use App\Models\Customer\Transaction;
use App\Models\Schedule\Reservation;
use Database\Seeders\TransactionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HistoryApiTest extends TestCase
{
    use RefreshDatabase;

    public bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->getUser());
        $this->seed(TransactionsTableSeeder::class);
    }

    public function testCanGetHistory()
    {
        $response = $this->getJson(route('api.transaction.history'));

        $response->assertJsonStructureIsFullPaginate();
        $response->assertJsonCount(4, 'data');

        // filter status
        $response = $this->getJson(route('api.transaction.history', [
            'status' => Transaction::STATUS_PAID,
            'with' => 'reservation.route',
         ]));

        $response->assertJsonCount(3, 'data');
        $this->assertNotEmpty($response->json('data.0.reservation.route'));

        $response = $this->getJson(route('api.transaction.history', [
            'status' => [Transaction::STATUS_PENDING, Transaction::STATUS_CANCELED],
        ]));

        $response->assertJsonCount(1, 'data');

        // filter date
        $response = $this->getJson(route('api.transaction.history', [
            'start_date' => now()->subDays(5)->format('Y-m-d'),
            'end_date' => now()->subDay()->format('Y-m-d'),
        ]));

        $response->assertJsonCount(0, 'data');

        $response = $this->getJson(route('api.transaction.history', [
            'start_date' => now()->subDays(5)->format('Y-m-d'),
        ]));

        $response->assertJsonCount(4, 'data');

        // filter by keyword
        // make sure all transaction paid before we go
        Transaction::query()->where('status', '!=', Transaction::STATUS_PAID)->cursor()->each->update(['status' => Transaction::STATUS_PAID]);
        /** @var Reservation $reservation */
        $reservation = Reservation::query()->whereHas('transactions', null, '=', 1)->first();
        $response = $this->getJson(route('api.transaction.history', [
            'keyword' => $reservation->code,
        ]));

        $response->assertJsonCount(1, 'data');

        // filter by is_active
        $response = $this->getJson(route('api.transaction.history', [
            'is_active' => false,
        ]));

        $response->assertJsonCount(0, 'data');

        $response = $this->getJson(route('api.transaction.history', [
            'is_active' => true,
        ]));

        $response->assertJsonCount(4, 'data');

        $reservation = Reservation::query()->first();

        $reservation->trips->each(fn (Reservation\Trip $trip) => $trip->update([
            'departure' => $trip->departure->subDays(20),
            'arrival' => $trip->arrival->subDays(20),
        ]));

        $response = $this->getJson(route('api.transaction.history', [
            'is_active' => true,
            'with' => ['reservation.route', 'reservation.layout', 'trips']
        ]));

        $this->assertNotEmpty($response->json('data.0.reservation.route'));
        $this->assertNotEmpty($response->json('data.0.reservation.layout'));
        $this->assertNotEmpty($response->json('data.0.trips'));
        $this->assertNotEmpty($response->json('data.0.total_price'));

        $response->assertJsonCount(Transaction::query()->where('reservation_id', '!=', $reservation->id)->count(), 'data');
    }

    public function testCanGetPrint(): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()->first();

        $response = $this->getJson(route('api.transaction.history.print', ['transaction_hash' => $transaction->hash]));

        $response->assertOk();
    }
}
