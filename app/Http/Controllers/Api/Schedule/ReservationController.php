<?php

namespace App\Http\Controllers\Api\Schedule;

use App\Models\Customer\Transaction\Passenger;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Schedule\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReservationController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        $query = Reservation::query();

        $query->withCount('passengers');

        $this->applyFilter($query, $request);
        $this->applySort($query, $request);

        $data = $query->paginate($request->input('per_page', 15));

        /** @noinspection PhpUndefinedMethodInspection */
        collect($data->items())->each->load('layout', 'route.tracks.origin', 'route.tracks.destination');

        return $data;
    }

    public function show(Reservation $reservation): JsonResponse
    {
        $reservation->load('trips', 'layout', 'route', 'setting_details.setting');

        $reservation->trips
            ->each(fn (Reservation\Trip $trip) => $trip->setRelation('reservation', $reservation))
            ->each(fn (Reservation\Trip $trip) => $trip->append(['seats_state']));

        $passengers = collect($reservation->passengers()->get()->each(function ($item) {
            $item->setHidden(['id'])->makeVisible(['transaction_id']);
        }));

        $transactions = $reservation->transactions()->with('trips')
            ->whereIn('id', $passengers->pluck('transaction_id')->unique()->values())->get()->keyBy('id');

        $passengers->each(fn (Passenger $passenger) => $passenger->setRelation('transaction', $transactions->get($passenger->transaction_id)));

        $reservation->setRelation('passengers', $passengers);

        return $this->success($reservation->toArray());
    }

    public function getBookers(Reservation $reservation, Reservation\Trip $trip): JsonResponse
    {
        return $this->success($trip->bookers);
    }

    private function applyFilter(Builder $query, Request $request): void
    {
        $query->when(
            $request->input('route_hash'),
            fn (Builder $builder, string $route_hash) => $builder->where('route_id', Route::hashToId($route_hash))
        );

        $query->when($request->input('start_date') ?? false, fn (Builder $builder, $startDate) => $builder->whereDate('departure_schedule', '>=', $startDate));

        $query->when($request->input('end_date') ?? false, fn (Builder $builder, $endDate) => $builder->whereDate('departure_schedule', '<=', $endDate));

        $query->when(
            $request->input('keyword'),
            fn (Builder $builder, string $keyword) => $builder->where(
                fn (Builder $builder) => $builder
                    ->orWhere('code', 'like', '%'.$keyword.'%')
                    ->orWhere('departure_schedule', 'like', '%'.$keyword.'%')
                    ->orWhereHas(
                        'trips',
                        fn (Builder $builder) => $builder->where(fn (Builder $builder) => $builder
                            ->where('origin', 'like', '%'.$keyword.'%')
                            ->orWhere('destination', 'like', '%'.$keyword.'%'))
                    )
            )
        );

        $query->when(
            $request->has('has_departure'),
            fn (Builder $builder) => $builder
                ->has('departure', $request->boolean('has_departure') ? '>=' : '<')
        );
    }
}
