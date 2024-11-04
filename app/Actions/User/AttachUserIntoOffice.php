<?php

namespace App\Actions\User;

use App\Models\User;
use App\Models\Office;
use App\Concerns\BasicResponse;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class AttachUserIntoOffice
{
    use AsAction, BasicResponse;

    public function rules(): array
    {
        return [
            'office_hash' => ['required', new ExistsByHash(Office::class)],
        ];
    }

    public function asController(ActionRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        abort_if($user->user_type === User::USER_TYPE_SUPER_ADMIN, 403);

        $inputs = $request->validated();

        $this->handle($user, Office::hashToId($inputs['office_hash']));

        return $this->success($user->fresh('original_offices')->toArray());
    }

    public function handle(User $user, $officeId)
    {
        $user->original_offices()->attach($officeId);
    }
}
