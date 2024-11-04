<?php

namespace App\Actions\User;

use App\Models\User;
use App\Concerns\BasicResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Validation\ValidationException;

class UpdateUserPassword
{
    use AsAction, BasicResponse;

    public function rules(): array
    {
        return [
            'old_password' => 'required',
            'password' => ['required', 'confirmed', Password::default()],
        ];
    }

    /**
     * @param \Lorisleiva\Actions\ActionRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function asController(ActionRequest $request): JsonResponse
    {
        $request->validated();

        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->handle($request->all(), $user);

        return $this->success($user->toArray());
    }

    /**
     * @param array $input
     * @param \App\Models\User $user
     * @throws \Throwable
     */
    public function handle(array $input, User $user)
    {
        throw_if(! Hash::check($input['old_password'], $user->getRawOriginal('password')), ValidationException::withMessages([
            'old_password' => 'Incorrect old password',
        ]));

        $user->password = $input['password'];
        $user->save();
    }
}
