<?php

namespace App\Http\Controllers\Api;

use App\Models\Office;
use App\Models\Customer\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;

class CustomerController extends Controller
{
    /**
     * Get all users
     * Route Path       : /v1/api/user
     * Route Name       : api.user
     * Route Method     : GET.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $query = Customer::query()->with('offices');

        $query->when(request()->filled('office_hash'), function (Builder $builder) {
            $builder->whereHas('offices', function (Builder $builder) {
                $builder->where('id', Office::hashToId(request('office_hash')));
            });
        });

        $query->when(\request()->filled('keyword'), function (Builder $builder) {
            $builder->search(\request('keyword'));
        });

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(request('per_page', 15)));
    }
}
