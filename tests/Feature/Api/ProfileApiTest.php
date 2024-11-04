<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Office;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public bool $seed = true;

    public function testProfileEndpoint(): void
    {
        $this->actingAs($this->getUser());

        $response = $this->get('/v1/profile');

        $response->assertActionSuccess();
    }

    public function testSuperAdmin(): void
    {
        $this->actingAs($this->getUser(fn () => User::query()->where('user_type', User::USER_TYPE_SUPER_ADMIN)->first()));

        $response = $this->get('/v1/profile');

        $response->assertActionSuccess();

        $data = $response->decodeResponseJson();

        $actualOfficeCount = Office::query()->count();

        $this->assertEquals(count($data['data']['offices']), $actualOfficeCount);

        $this->assertEquals(Permission::query()->count(), count($response->json('data.permissions')));
    }

    public function testStaff(): void
    {
        /** @var User $user */
        $user = User::query()->whereNotIn('user_type', [User::USER_TYPE_SUPER_ADMIN])->first();

        $this->actingAs($this->getUser(fn () => $user));

        $response = $this->get('/v1/profile');

        $response->assertActionSuccess();

        $data = $response->decodeResponseJson();

        $actualOfficeCount = $user->original_offices()->count();

        $this->assertEquals(count($data['data']['offices']), $actualOfficeCount);
    }

    public function testUpdateUserPassword(): void
    {
        $user = $this->getUser();

        $this->actingAs($user);

        $newPassword = 'new_password';

        $response = $this->patchJson('/v1/profile/password', [
            'old_password' => 'password',
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertActionSuccess();

        $user = $user->fresh();

        $this->assertEquals(true, Hash::check($newPassword, $user->password), 'password match');
    }
}
