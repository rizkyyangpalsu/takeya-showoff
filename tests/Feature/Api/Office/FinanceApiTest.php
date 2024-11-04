<?php

namespace Tests\Feature\Api\Office;

use App\Http\Controllers\Api\Office\FinanceController;
use App\Http\Controllers\Api\ProfileController;
use App\Models\User;
use Database\Seeders\TransactionsTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    public bool $seed = true;

    public function test_can_get_finance_data(): void
    {
        $this->seed(TransactionsTableSeeder::class);
        $this->actingAs($this->getUser());

        $profileResponse = $this->getJson(action([ProfileController::class, 'index']));
        $profileResponse->assertSuccessful();

        $officeSlug = $profileResponse->json('data.offices.0.slug');

        $response = $this->getJson(action([FinanceController::class, 'index'], ['office_slug' => $officeSlug]));

        $response->assertSuccessful();
    }

    public function test_can_get_finance_matrix_data(): void
    {
        $this->seed(TransactionsTableSeeder::class);
        $this->actingAs($this->getUser());

        $profileResponse = $this->getJson(action([ProfileController::class, 'index']));
        $profileResponse->assertSuccessful();

        /** @var User $user */
        $user = User::query()->where('user_type', User::USER_TYPE_STAFF_BUS)->first();

        $officeSlug = $profileResponse->json('data.offices.0.slug');

        $response = $this->getJson(action([FinanceController::class, 'matrix'], [
            'staff_hash' => $user->hash,
            'office_slug' => $officeSlug,
        ]));

        $response->assertSuccessful();
    }
}
