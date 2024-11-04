<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public bool $seed = true;

    public function test_api_mobile_can_do_a_register(): void
    {
        $response = $this->postJson(route('api-mobile.register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'user_type' => User::USER_TYPE_CUSTOMER,
        ]);
    }

    public function test_api_mobile_can_do_a_login(): void
    {
        $response = $this->postJson(route('api-mobile.login'), [
            'username' => 'customer@this',
            'password' => 'password',
            'device_name' => 'Devi\'s phone.'
        ]);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'code',
            'message',
            'data' => [
                'token',
            ],
        ]);
    }

    public function test_api_mobile_can_do_a_forgot_password(): void
    {
        /** @var User $user */
        $user = User::query()->where('user_type', User::USER_TYPE_CUSTOMER)->firstOrFail();

        $response = $this->postJson(route('api-mobile.password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSuccessful();
    }
}
