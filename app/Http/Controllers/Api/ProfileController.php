<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Users\UpdateExistingUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get Profile information about current user.
     * Route Path       : /v1/profile
     * Route Name       : api.profile
     * Route Method     : GET.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = $this->user()->load(['original_offices'])->append(['offices']);
        $user->assignRole($user->user_type);
        $permissions = $user->getAllPermissions()->pluck('name');

        return $this->success(array_merge($user->toArray(), compact('permissions')));
    }

    /**
     * Update profile information.
     * Route Path       : /v1/profile
     * Route Name       : api.profile.update
     * Route Method     : PUT.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request): JsonResponse
    {
        $this->dispatch(new UpdateExistingUser($this->user(), $request->all()));

        return $this->success($this->user()->refresh()->toArray());
    }
}
