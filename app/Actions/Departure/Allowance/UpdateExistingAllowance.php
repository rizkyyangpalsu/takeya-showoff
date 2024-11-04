<?php

namespace App\Actions\Departure\Allowance;

use App\Events\Departure\AllowanceUpdated;
use App\Models\Office;
use App\Models\Departure;
use App\Concerns\BasicResponse;
use App\Models\Office\Staff;
use Illuminate\Http\JsonResponse;
use LogicException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class UpdateExistingAllowance
{
    use AsAction, BasicResponse;

    public function rules(): array
    {
        return [
            'office_hash' => ['nullable', new ExistsByHash(Office::class)],
            'executor_hash' => ['nullable', new ExistsByHash(Office\Staff::class)],
            'receiver_hash' => ['nullable', new ExistsByHash(Office\Staff::class)],
            'name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @param \Lorisleiva\Actions\ActionRequest $request
     * @param \App\Models\Departure $departure
     * @param \App\Models\Departure\Allowance $allowance
     * @return \App\Models\Departure\Allowance
     * @throws \Throwable
     */
    public function asController(ActionRequest $request, Departure $departure, Departure\Allowance $allowance): Departure\Allowance
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
            $allowance,
            $departure,
            array_key_exists('office_hash', $inputs) ? Office::byHashOrFail($inputs['office_hash']) : null,
            $staff,
            $executor,
            $receiver,
            $inputs
        );
    }

    /**
     * @param \App\Models\Departure\Allowance $allowance
     * @return \Illuminate\Http\JsonResponse
     */
    public function jsonResponse(Departure\Allowance $allowance): JsonResponse
    {
        return $this->success($allowance->toArray());
    }

    /**
     * @param \App\Models\Departure\Allowance $allowance
     * @param \App\Models\Departure $departure
     * @param \App\Models\Office|null $office
     * @param Staff|null $staff
     * @param \App\Models\Office\Staff|null $executor
     * @param \App\Models\Office\Staff|null $receiver
     * @param array $inputs
     * @return \App\Models\Departure\Allowance
     * @throws \Throwable
     */
    public function handle(
        Departure\Allowance $allowance,
        Departure $departure,
        ?Office $office,
        ?Office\Staff $staff,
        ?Office\Staff $executor,
        ?Office\Staff $receiver,
        array $inputs
    ): Departure\Allowance {
        throw_if((int) $allowance->departure_id !== (int) $departure->id, LogicException::class, 'Allowance is not part of departure.');

        if ($office !== null) {
            $allowance->office()->associate($office);
        }

        if ($executor !== null) {
            $allowance->executor()->associate($executor);
        }

        if ($receiver !== null) {
            $allowance->receiver()->associate($receiver);
        }

        $allowance->fill($inputs);

        $allowance->save();

        event(new AllowanceUpdated($departure, $allowance, $staff));

        return $allowance;
    }
}
