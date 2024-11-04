<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Actions\Logistic\Service\CreateNewService;
use App\Actions\Logistic\Service\UpdateExistingService;
use App\Actions\Logistic\Service\DeleteExistingService;
use App\Http\Controllers\Controller;
use App\Models\Logistic\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->input('keyword')) {
            $services = Service::where('name', $this->getMatchLikeClause(Service::query()), '%'.$request->input('keyword').'%')
            ->orWhere('etd', $this->getMatchLikeClause(Service::query()), '%'.$request->input('keyword').'%')
            ->paginate($request->input('per_page'));
        } else {
            $services = Service::orderBy('created_at', 'desc')->paginate($request->input('per_page'));
        }

        return response()->json($services);
    }

    public function show(Service $service)
    {
        return $this->success($service);
    }

    public function store(Request $request, CreateNewService $action)
    {
        return $this->success($action->create($request->all()));
    }

    public function update(Request $request, Service $service, UpdateExistingService $action)
    {
        return $this->success($action->create($request->all(), $service));
    }

    public function destroy(Service $service, DeleteExistingService $action)
    {
        return $this->success($action->create($service));
    }
}
