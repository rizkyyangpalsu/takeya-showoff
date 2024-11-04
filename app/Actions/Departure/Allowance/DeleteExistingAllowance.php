<?php

namespace App\Actions\Departure\Allowance;

use App\Events\Departure\AllowanceWillBeDeleted;
use App\Models\Departure;
use App\Concerns\BasicResponse;
use App\Models\Office\Staff;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteExistingAllowance
{
    use AsAction, BasicResponse;

    /**
     * @param \App\Models\Departure\Allowance $allowance
     * @return \App\Models\Departure\Allowance
     * @throws \Exception
     */
    public function handle(Departure\Allowance $allowance): Departure\Allowance
    {
        $allowance->delete();

        return $allowance;
    }

    public function asController(ActionRequest $request, Departure $departure, Departure\Allowance $allowance): Departure\Allowance
    {
        $staff = $request->user() instanceof Staff ? $request->user() : Staff::query()->findOrFail($request->user()->id);

        event(new AllowanceWillBeDeleted($departure, $allowance, $staff));

        return $this->handle($allowance);
    }

    public function jsonResponse(Departure\Allowance $allowance): JsonResponse
    {
        return $this->success($allowance->toArray());
    }
}
