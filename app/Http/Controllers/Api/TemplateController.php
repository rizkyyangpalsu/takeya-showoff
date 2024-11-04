<?php

namespace App\Http\Controllers\Api;

use App\Actions\Template\CreateNewTemplate;
use App\Actions\Template\DeleteExistingTemplate;
use App\Actions\Template\UpdateExistingTemplate;
use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Template;
use Illuminate\Http\Request;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'type'  =>  'required',
        ]);

        $templates = Template::where('type', $request->type)->with('office');

        return $templates->paginate($request->input('per_page'));
    }

    public function searchFirst(Request $request)
    {
        $request->validate([
            'type'          =>  'required',
            'office_hash'   =>  ['required', new ExistsByHash(Office::class)]
        ]);

        $officeId = Office::hashToId($request->office_hash);

        $template = Template::where('type', $request->type)->where('office_id', $officeId)->first();

        return $this->success($template);
    }

    public function show(Template $template)
    {
        return $this->success($template->load('office'));
    }

    public function store(Request $request, CreateNewTemplate $action)
    {
        return $this->success($action->create($request->all()));
    }

    public function update(Request $request, Template $template, UpdateExistingTemplate $action)
    {
        return $this->success($action->create($request->all(), $template));
    }

    public function destroy(Template $template, DeleteExistingTemplate $action)
    {
        return $this->success($action->destroy($template));
    }
}
