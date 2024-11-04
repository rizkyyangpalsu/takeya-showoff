<?php

namespace App\Actions\Departure;

use App\Models\User;
use App\Models\Departure;
use Illuminate\Validation\Rule;
use App\Concerns\BasicResponse;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Events\Departure\DepartureStatusChanged;

class UpdateExistingDepartureStatus
{
    use AsAction, BasicResponse;

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Departure::getDepartureStatus())],
        ];
    }

    public function asController(ActionRequest $request, Departure $departure)
    {
        $request->validated();

        $departure = $this->handle($departure, $request->input('status'), $request->user());

        return $this->success($departure->fresh()->toArray());
    }

    public function handle(Departure $departure, string $status, User $user): Departure
    {
        $departure->fill([
            'status' => $status,
        ]);
        $departure->save();

        event(new DepartureStatusChanged($departure, $user));

        return $departure;
    }
}
