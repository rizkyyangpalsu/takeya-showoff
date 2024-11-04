<?php

namespace App\Actions\Transaction;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\ActionRequest;
use App\Actions\Reservation\MatchingReservationFromSchedule;
use Illuminate\Support\Facades\Cache;

class ReserveSeatFromSchedule extends CalculatePricesForReservation
{
    /**
     * @param \Lorisleiva\Actions\ActionRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function handle(ActionRequest $request): JsonResponse
    {
        $request->validate();
        $transactionData = CreateNewTransaction::validated($request->all());
        $departureSchedule = $this->item->getDepartureSchedule();
        $reservationQuery = $this->item->settingDetail
            ->reservations()
            ->where('departure_schedule', $departureSchedule->format('Y-m-d H:i:s'));

        $lock = Cache::lock('reservation_matching_'.$this->item->settingDetail->id, 1.5);

        if ($lock->get()) {
            if ($reservationQuery->count() !== $this->item->settingDetail->fleet) {
                MatchingReservationFromSchedule::run($this->item->getDepartureSchedule(), $this->item->settingDetail);
            }

            $lock->release();
        } else {
            // waiting for another lock to release
            $waiting = true;

            do {
                $isLockedByOtherProcess = ! $lock->get();

                if (! $isLockedByOtherProcess) {
                    $waiting = false;
                    $lock->release();
                }
            } while ($waiting);
        }

        $reservation = $reservationQuery
            ->where('index', $request->input('reservation_index', 0))
            ->first();

        /** @var \App\Models\Customer\Transaction $transaction */
        $transaction = CreateNewTransaction::run(
            $reservation,
            $this->customActor ?? $request->user(),
            $this->item,
            $transactionData,
            $this->getOffice($this->customActor)
        );

        $transaction->load(['items', 'passengers']);
        $transaction->reservation->makeHidden(['layout']);

        return $this->success($transaction->toArray());
    }
}
