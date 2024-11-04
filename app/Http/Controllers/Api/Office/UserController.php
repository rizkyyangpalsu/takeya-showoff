<?php

namespace App\Http\Controllers\Api\Office;

use App\Models\User;
use App\Models\Office;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Get all users within the office
     * Route Path       : /v1/office/{office_slug}/user
     * Route Name       : api.office.user
     * Route Method     : GET.
     *
     * @param \App\Models\Office $office
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Office $office)
    {
        $query = $office->users()->newQuery();

        $query->when(request()->filled('user_type'), function ($builder) {
            $builder->whereIn('user_type', request('user_type'));
        });

        $query->orderBy('created_at', 'desc');

        return response()->json($query->paginate(request('per_page', 15)));
    }
}
