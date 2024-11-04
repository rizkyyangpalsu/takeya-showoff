<?php

namespace App\Http\Controllers\Api;

use App\Models\Office;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;

class StaffController extends Controller
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
        $query = Office\Staff::query()->with('original_offices');

        $query->when(request()->filled('office_hash'), function (Builder $builder) {
            $builder->whereHas('original_offices', function (Builder $builder) {
                $builder->where('id', Office::hashToId(request('office_hash')));
            });
        });

        $query->when(\request()->filled('keyword'), function (Builder $builder) {
            $builder->where('name', 'ilike', '%'.\request('keyword').'%');
        });

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(request('per_page', 15)));
    }
}
