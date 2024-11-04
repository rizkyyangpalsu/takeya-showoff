<?php

namespace App\Actions\Departure\Allowance;

use App\Models\Office;
use App\Models\Departure;
use App\Models\Office\Staff;
use App\Concerns\BasicResponse;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\ActionRequest;
use App\Events\Departure\AllowanceAdded;
use Lorisleiva\Actions\Concerns\AsAction;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class AddAllowanceToDeparture
{
    use AsAction, BasicResponse;

    public function rules(): array
    {
        return [
            'office_hash' => ['required', new ExistsByHash(Office::class)],
            'executor_hash' => ['nullable', new ExistsByHash(Office\Staff::class)],
            'receiver_hash' => ['nullable', new ExistsByHash(Office\Staff::class)],
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function asController(ActionRequest $request, Departure $departure): Departure\Allowance
    {
        $inputs = $request->validated();

        // Get current user
        $staff = $request->user() instanceof Staff ? $request->user() : Staff::query()->findOrFail($request->user()->id);

        /** @var Staff $executor */
        $executor = array_key_exists('executor_hash', $inputs) && ! empty($inputs['executor_hash'])
            ? Office\Staff::byHashOrFail($inputs['executor_hash'])
            : null;

        /** @var Staff $receiver */
        $receiver = array_key_exists('receiver_hash', $inputs) && ! empty($inputs['receiver_hash'])
            ? Office\Staff::byHashOrFail($inputs['receiver_hash'])
            : null;

        return $this->handle(
            $departure,
            Office::byHashOrFail($inputs['office_hash']),
            $staff,
            $executor,
            $receiver,
            $inputs
        );
    }

    public function jsonResponse(Departure\Allowance $allowance): JsonResponse
    {
        return $this->success($allowance->toArray());
    }

    public function handle(
        Departure $departure,
        Office $office,
        Office\Staff $staff,
        ?Office\Staff $executor = null,
        ?Office\Staff $receiver = null,
        array $inputs = []
    ): Departure\Allowance {
        $allowance = new Departure\Allowance($inputs);
        $allowance->departure()->associate($departure);
        $allowance->office()->associate($office);

        if ($executor !== null) {
            $allowance->executor()->associate($executor);
        }

        if ($receiver !== null) {
            $allowance->receiver()->associate($receiver);
        }

        $allowance->save();

        event(new AllowanceAdded($departure, $allowance, $staff));

        return $allowance;
    }
}
