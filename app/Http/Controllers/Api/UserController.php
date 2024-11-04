<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Office;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Jobs\Users\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Jobs\Users\DeleteExistingUser;
use App\Jobs\Users\UpdateExistingUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Get all users
     * Route Path       : /v1/api/user
     * Route Name       : api.user
     * Route Method     : GET.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('original_offices');

        $inputUserType = $request->input('user_type');
        $query->when($inputUserType, function ($builder, $userType) {
            if (is_array($userType)) {
                $builder->whereIn('user_type', $userType);
            } else {
                $builder->where('user_type', 'like', "%$userType%");
            }
        });

        $query->when($request->filled('office_hash'), function (Builder $builder) {
            $builder->whereHas('original_offices', function (Builder $builder) {
                $builder->where('id', Office::hashToId(request('office_hash')));
            });
        });

        $query->when($request->input('keyword'), function (Builder $builder, $keyword) {
            $likeClause = $this->getMatchLikeClause($builder);

            $builder->where(fn (Builder $builder) => $builder
                ->where('name', $likeClause, "%$keyword%")
                ->orWhere('email', $likeClause, "%$keyword%"));
        });

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(request('per_page', 15)));
    }

    /**
     * Create new user.
     * Route Path       : /v1/api/user
     * Route Name       : api.user.store
     * Route Method     : POST.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $job = new CreateNewUser($request->all(), $request->input('user_type'));

        $this->dispatch($job);

        return $this->success($job->user->refresh()->toArray())->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Get User Information
     * Route Path       : /v1/api/user/{user_hash}
     * Route Name       : api.user.show
     * Route Method     : GET.
     *
     * @param User $user
     *
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        return $this->success($user->append('office')->toArray());
    }

    /**
     * Update user information
     * Route Path       : /v1/api/user/{user_hash}
     * Route Name       : api.user.update
     * Route Method     : PUT.
     *
     * @param Request $request
     * @param User $user
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->dispatch(new UpdateExistingUser($user, $request->all(), $request->input('user_type')));

        return $this->success($user->refresh()->toArray());
    }

    /**
     * Delete existing user.
     * Route Path       : /v1/api/user/{user_hash}
     * Route Name       : api.user.destroy
     * Route Method     : DELETE.
     *
     * @param User $user
     *
     * @return JsonResponse
     */
    public function destroy(User $user)
    {
        $this->dispatch(new DeleteExistingUser($user));

        return $this->success($user->refresh()->toArray());
    }
}
