<?php

namespace Tests;

use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            ThrottleRequests::class,
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        TestResponse::macro('assertJsonStructureIsSimplePaginate', function () {
            $this->assertOk();

            $this->assertJsonStructure([
                'current_page', 'data', 'first_page_url', 'from', 'next_page_url',
                'path', 'per_page', 'prev_page_url', 'to',
            ]);
        });

        TestResponse::macro('assertJsonStructureIsFullPaginate', function () {
            $this->assertOk();

            $this->assertJsonStructure([
                'current_page', 'data', 'first_page_url', 'from', 'last_page', 'last_page_url',
                'links', 'next_page_url', 'path', 'per_page', 'prev_page_url', 'to', 'total',
            ]);
        });

        TestResponse::macro('assertActionSuccess', function () {
            $this->assertSuccessful();
            $this->assertJsonStructure(['code', 'message', 'data']);
        });
    }

    public function getUser(?callable $userResolver = null): User
    {
        /**
         * @var $user User
         */
        $user = ! $userResolver ? User::query()->where('user_type', User::USER_TYPE_SUPER_ADMIN)->first() : $userResolver();

        $this->assertNotNull($user);

        return $user;
    }
}
