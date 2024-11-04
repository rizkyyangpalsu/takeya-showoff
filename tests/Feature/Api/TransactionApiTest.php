<?php

namespace Tests\Feature\Api;

use App\Actions\Transaction\ReversalTransaction;
use App\Actions\Transaction\SetTransactionToExpired;
use App\Models\Schedule\Reservation\Trip;
use Carbon\Carbon;
use Database\Seeders\TransactionsTableSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Tests\TestCase;
use App\Models\User;
use App\Models\Route;
use App\Models\Office;
use App\Models\Accounting\Journal;
use App\Models\Customer\Transaction;
use Illuminate\Testing\TestResponse;
use App\Support\Accounting\AccountResolver;
use Database\Seeders\ScheduleSettingSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase, WithFaker, AccountResolver, InteractsWithDatabase;

    public bool $seed = true;

    private string $date;
    private Route\Track\Point $departurePoint;
    private Route\Track\Point $destinationPoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ScheduleSettingSeeder::class);
        $this->actingAs($this->getUser());
    }

    /**
     * @return \Illuminate\Testing\TestResponse
     * @throws \Exception
     */
    public function sendInquiry(): TestResponse
    {
        [$date, $departurePoint, $destinationPoint] = $this->getParams();

        return $this->get(route('api.transaction.inquiry', [
            'date' => $date,
            'departure_hash' => $departurePoint->hash,
            'destination_hash' => $destinationPoint->hash,
        ]), [
            'Accept' => 'application/json',
        ]);
    }

    public function getActor(?callable $userResolver = null): User
    {
        $user = $this->getUser(
            $userResolver ?? static fn () => User::permission('manage transaction')->first()
        );
        $this->actingAs($user);

        return $user;
    }

    /**
     * @param string $inquiryHash
     * @return \Illuminate\Testing\TestResponse
     */
    public function sendInquirySeat(string $inquiryHash): TestResponse
    {
        return $this->getJson(route('api.transaction.inquiry.seat', [
            'hash' => $inquiryHash,
        ]));
    }

    /**
     * @param $inquiryHash
     * @param $seatsHash
     * @param null $customActorHash
     * @param array|null $additionalData
     * @return \Illuminate\Testing\TestResponse
     */
    public function sendReservation($inquiryHash, $seatsHash, $customActorHash = null, array|null $additionalData = null): TestResponse
    {
        $user = $this->getActor(
            $customActorHash
                ? static fn () => User::query()->where('user_type', User::USER_TYPE_SUPER_ADMIN)->first()
                : null
        );

        $data = [
            'hash' => $inquiryHash,
            'additional_data' => $additionalData,
            'passengers' => collect($seatsHash)->map(fn ($seatHash) => [
                'seat_hash' => $seatHash,
                'name' => $this->faker->name,
                'additional_data' => [
                    'phone' => $this->faker->phoneNumber,
                ],
            ])->toArray(),
        ];

        if ($customActorHash) {
            $data['actor_hash'] = $customActorHash;
        }

        return $this->postJson(route('api.transaction.reserve'), $data, [
            'OfficeHash' => $user->offices->first()->hash,
        ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     * @throws \Exception
     */
    public function testInquiryCanGetResult(): void
    {
        // test not in the first track
        $this->date = now()->addDays(2)->format('Y-m-d');

        /** @var Route $route */
        $route = Route::query()->first();

        $this->departurePoint = $route->tracks->get($route->tracks->count() - 2)->origin;
        $this->destinationPoint = $route->tracks->last()->destination;

        $responseNormalInquiry = $this->sendInquiry();
        $responseNormalInquiry->assertActionSuccess();
        self::assertNotCount(0, $responseNormalInquiry->json('data'));

        // test inquiry using regency
        $responseGeoInquiry = $this->get(route('api.transaction.inquiry', [
            'date' => $this->date,
            'departure_regency_hash' => $this->departurePoint->regency->hash,
            'destination_regency_hash' => $this->destinationPoint->regency->hash,
        ]), [
            'Accept' => 'application/json',
        ]);

        $responseGeoInquiry->assertActionSuccess();

        self::assertEquals(
            Arr::except($responseNormalInquiry->json('data.0'), 'hash'),
            Arr::except($responseGeoInquiry->json('data.0'), 'hash'),
        );
    }

    public function testCanInquiryUsingGeoRegency(): void
    {
        $date = now()->addDays(2)->format('Y-m-d');

        /** @var Route $route */
        $route = Route::query()->first();

        $departureRegency = $route->tracks->get($route->tracks->count() - 2)->origin->regency;
        $destinationRegency = $route->tracks->last()->destination->regency;

        $response = $this->get(route('api.transaction.inquiry', [
            'date' => $date,
            'departure_regency_hash' => $departureRegency->hash,
            'destination_regency_hash' => $destinationRegency->hash,
        ]), [
            'Accept' => 'application/json',
        ]);

        $response->assertActionSuccess();
    }

    /**
     * @throws \Exception
     */
    public function testCanGetInquirySeat(): void
    {
        $inquiryResponse = $this->sendInquiry();
        $inquiryResponse->assertOk();

        $response = $this->sendInquirySeat($inquiryResponse->json('data.0.hash'));

        $response->assertActionSuccess();

        // using hash response
        $responseUsingHash = $this->get(route('api.transaction.inquiry.seat', [
            'hash' => $inquiryResponse->json('data.0.hash'),
        ]), [
            'Accept' => 'application/json',
        ]);

        $responseUsingHash->assertActionSuccess();

        self::assertEquals($response->json(), $responseUsingHash->json());
    }

    public function testCanCalculatePricing(): void
    {
        $this->actingAs($user = $this->getActor());

        $inquiryResponse = $this->sendInquiry();
        $inquiryResponse->assertOk();

        $inquirySeatsResponse = $this->sendInquirySeat($inquiryResponse->json('data.0.hash'));
        $inquirySeatsResponse->assertOk();

        $seatsHash = collect($inquirySeatsResponse->json('data.state_seats.0'))
            ->where('selectable', true)
            ->random(3)
            ->pluck('hash')
            ->toArray();

        $data = [
            'hash' => $inquiryResponse->json('data.0.hash'),
            'passengers' => collect($seatsHash)->map(fn ($seatHash) => [
                'seat_hash' => $seatHash,
                'name' => $this->faker->name,
                'additional_data' => [
                    'phone' => $this->faker->phoneNumber,
                ],
            ])->toArray(),
        ];

        $response = $this->postJson(route('api.transaction.pricing'), $data, [
            'OfficeHash' => $user->offices->first()->hash,
        ]);

        $response->assertActionSuccess();
    }

    /**
     * @throws \Exception
     */
    public function testCanReserveASeats(): void
    {
        $inquiryResponse = $this->sendInquiry();
        $inquiryResponse->assertOk();

        $inquirySeatsResponse = $this->sendInquirySeat($inquiryResponse->json('data.0.hash'));
        $inquirySeatsResponse->assertOk();

        $seatsHash = collect($inquirySeatsResponse->json('data.state_seats.0'))
            ->where('selectable', true)
            ->random(3)
            ->pluck('hash')
            ->toArray();

        $response = $this->sendReservation($inquiryResponse->json('data.0.hash'), $seatsHash);
        $response->assertOk();

        // request a seats again and make sure we get error 422
        $response = $this->sendReservation($inquiryResponse->json('data.0.hash'), $seatsHash);
        $response->assertStatus(422);

        // request a seats in next day and we wonder if it success and filled in database with 2 reservations data
        $this->date = Carbon::createFromFormat('Y-m-d', $this->date)->addDays(2)->format('Y-m-d');
        $inquiryResponse = $this->sendInquiry();
        $inquiryResponse->assertOk();

        $additionalData = [
            'name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
        ];
        $response = $this->sendReservation(
            $inquiryResponse->json('data.0.hash'),
            $seatsHash,
            additionalData: $additionalData
        );
        $response->assertOk();

        $this->assertDatabaseCount('schedule_reservations', 2);

        $this->assertSame($additionalData, $response->json('data.additional_data'));

        // lets hit again...
        $response = $this->sendReservation($inquiryResponse->json('data.0.hash'), $seatsHash);
        $response->assertStatus(422);

        $this->assertDatabaseCount('schedule_reservations', 2);

        $seatsHash = collect($inquirySeatsResponse->json('data.state_seats.0'))
            ->where('selectable', true)
            ->whereNotIn('hash', $seatsHash)
            ->random(3)
            ->pluck('hash')
            ->toArray();

        /** @var User $customActor */
        $customActor = User::query()->where('user_type', User::USER_TYPE_AGENT)->firstOrFail();
        $response = $this->sendReservation($inquiryResponse->json('data.0.hash'), $seatsHash, $customActor->hash);
        $response->assertActionSuccess();

        self::assertSame($customActor->toArray(), $response->json('data.user'));

        // hit's again
        $inquirySeatsResponse = $this->sendInquirySeat($inquiryResponse->json('data.0.hash'));
        $inquirySeatsResponse->assertOk();

        $seatsHash = collect($inquirySeatsResponse->json('data.state_seats.0'))
            ->where('selectable', true)
            ->where('status', 'available')
            ->random(3)
            ->pluck('hash')
            ->toArray();

        $responseUsingHash = $this->postJson(route('api.transaction.reserve'), [
            'hash' => $inquiryResponse->json('data.0.hash'),
            'passengers' => collect($seatsHash)->map(fn ($seatHash) => [
                'seat_hash' => $seatHash,
                'name' => $this->faker->name,
                'additional_data' => [
                    'phone' => $this->faker->phoneNumber,
                ],
            ])->toArray(),
        ], [
            'OfficeHash' => $customActor->offices->first()->hash,
        ]);

        $responseUsingHash->assertActionSuccess();

        $transaction = Transaction::byHash($responseUsingHash->json('data.hash'));

        $transaction->trips->each(
            fn (Trip $trip) => $transaction->passengers()->pluck('seat_id')->each(
                fn (int $id) => $this->assertContains($id, $trip->seat_configuration->getBooked())
            )
        );
    }

    public function testCanPurchaseReservation(): void
    {
        $user = $this->getUser(
            fn () => User::permission('manage transaction')->inRandomOrder()->first()
        );
        $this->actingAs($user);

        $inquiryResponse = $this->sendInquiry();
        $inquiryResponse->assertOk();

        $inquirySeatsResponse = $this->sendInquirySeat($inquiryResponse->json('data.0.hash'));

        $inquirySeatsResponse->assertOk();

        $seatsHash = collect($inquirySeatsResponse->json('data.state_seats.0'))
            ->where('selectable', true)
            ->random(3)
            ->pluck('hash')
            ->toArray();

        $response = $this->sendReservation($inquiryResponse->json('data.0.hash'), $seatsHash);

        $response->assertOk();

        /** @var Office $selectedOffice */
        $selectedOffice = $user->offices->first();

        $purchase = $this->post(route('api.transaction.purchase', [
            'transaction_hash' => $response->json('data.hash'),
        ]), [], [
            'OfficeHash' => $selectedOffice->hash,
        ]);

        $purchase->assertOk();

        /** @var Transaction $transaction */
        $transaction = Transaction::byHash($purchase->json('data.hash'));

        /** @var Journal $journal */
        $journal = $transaction
            ->journals()
            ->with('entries.account')
            ->where('amount', $purchase->json('data.total_price'))
            ->first();

        self::assertEquals(true, $journal !== null, 'journal exists');
        self::assertEquals($selectedOffice->id, $journal->group_code, 'journal office match');

        $transaction->trips->each(
            fn (Trip $trip) => $transaction->passengers()->pluck('seat_id')->each(
                fn (int $id) => $this->assertContains($id, $trip->seat_configuration->getOccupied())
            )
        );
    }

    public function testCanUploadAttachment(): void
    {
        $user = $this->getUser(
            fn () => User::permission('manage transaction')->inRandomOrder()->first()
        );
        $this->actingAs($user);

        $inquiryResponse = $this->sendInquiry();
        $inquiryResponse->assertOk();

        $inquirySeatsResponse = $this->sendInquirySeat($inquiryResponse->json('data.0.hash'));

        $inquirySeatsResponse->assertOk();

        $seatsHash = collect($inquirySeatsResponse->json('data.state_seats.0'))
            ->where('selectable', true)
            ->random(3)
            ->pluck('hash')
            ->toArray();

        $response = $this->sendReservation($inquiryResponse->json('data.0.hash'), $seatsHash);

        $response->assertOk();

        $purchase = $this->post(route('api.transaction.purchase.attachment', [
            'transaction_hash' => $response->json('data.hash'),
        ]), [
            'attachment' => UploadedFile::fake()->image('resi.png'),
        ], [
            'Content-Type' => 'multipart/form-data; boundary=something'
        ]);

        $purchase->assertOk();
    }

    public function testCanExpiringTransaction(): void
    {
        $this->seed(TransactionsTableSeeder::class);

        $pendingTransactions = Transaction::query()->where('status', Transaction::STATUS_PENDING)->get();
        $pendingTransactions->each(fn (Transaction $transaction) => $transaction->update(['expired_at' => now()->subMinute()]));

        $this->app->call(SetTransactionToExpired::class.'@asCommand');

        $pendingTransactions
            ->each(fn (Transaction $transaction) => $this->assertSame(Transaction::STATUS_EXPIRED, $transaction->fresh()->status))
            ->each(fn (Transaction $transaction) => $transaction->trips->each(
                fn (Trip $trip) => $transaction->passengers()->pluck('seat_id')->each(
                    fn (int $id) => $this->assertContains($id, $trip->seat_configuration->getAvailable())
                )
            ));
    }

    public function testCanReversalTransaction(): void
    {
        $this->seed(TransactionsTableSeeder::class);

        $user = $this->getUser(
            fn () => User::query()->where('user_type', User::USER_TYPE_SUPER_ADMIN)->first()
        );
        $this->actingAs($user);

        /** @var Transaction $transaction */
        $transaction = Transaction::query()->whereNotNull('paid_at')->first();

        $response = $this->patchJson(action(ReversalTransaction::class, ['transaction_hash' => $transaction->hash]));

        $response->assertActionSuccess();

        self::assertEquals(
            0,
            Journal::query()->whereHasMorph(
                'recordable',
                Transaction::class,
                fn (Builder $builder) => $builder->where('id', $transaction->id)
            )->count()
        );

        $transaction->fresh('trips')->trips->each(
            fn (Trip $trip) => $transaction->passengers()->pluck('seat_id')->each(
                fn (int $id) => $this->assertContains($id, $trip->seat_configuration->getAvailable())
            )
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getParams(): array
    {
        if (isset($this->date, $this->departurePoint, $this->destinationPoint)) {
            return [$this->date, $this->departurePoint, $this->destinationPoint];
        }

        $this->date = now()->addDays(2)->format('Y-m-d');

        /** @var Route $route */
        $route = Route::query()->first();

        $this->departurePoint = $route->tracks->first()->origin;
        $this->destinationPoint = $route->tracks->get(random_int(1, $route->tracks->count() - 1))->destination;

        return [$this->date, $this->departurePoint, $this->destinationPoint];
    }
}
