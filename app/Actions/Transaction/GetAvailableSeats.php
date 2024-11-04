<?php

namespace App\Actions\Transaction;

use App\Support\Schedule\Item;
use App\Concerns\BasicResponse;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Validator;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;

class GetAvailableSeats
{
    use AsAction, BasicResponse;

    public function rules(): array
    {
        return [
            'hash' => ['required'],
        ];
    }

    /**
     * @param Validator $validator
     * @param \Lorisleiva\Actions\ActionRequest $request
     * @throws \Exception
     */
    public function afterValidator(Validator $validator, ActionRequest $request): void
    {
        if ($validator->errors()->hasAny(['hash'])) {
            return;
        }

        try {
            Crypt::decryptString($request->input('hash'));
        } catch (DecryptException) {
            $validator->errors()->add('hash', __('cannot decrypt hash.'));
        }
    }

    /**
     * @param \Lorisleiva\Actions\ActionRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function handle(ActionRequest $request): JsonResponse
    {
        $request->validated();

        $item = Item::fromHash($request->input('hash'));

        if (! $item) {
            return $this->success([]);
        }

        return $this->success($item->setVisible(['layout', 'state_seats', 'prices'])->toArray());
    }
}
