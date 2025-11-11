<?php

namespace App\Http\Controllers;

use App\Models\ClinicalNoteTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class ClinicalNoteTemplatesAdminController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('manageClinicalTemplates');

        $q = trim((string) $request->input('q'));
        $active = $request->input('active');

        $query = ClinicalNoteTemplate::query()->orderBy('name');
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%$q%");
            });
        }
        if ($active !== null && $active !== '') {
            if (in_array($active, ['0','1','true','false'], true)) {
                $query->where('active', in_array($active, ['1','true'], true));
            }
        }

        $templates = $query->paginate(12)->withQueryString();

        return Inertia::render('Admin/ClinicalNoteTemplates', [
            'filters' => [
                'q' => $q,
                'active' => $active,
            ],
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manageClinicalTemplates');
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
            'active' => 'boolean',
        ]);

        ClinicalNoteTemplate::create([
            'name' => $data['name'],
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'active' => $data['active'] ?? true,
            'created_by' => optional($request->user())->id,
        ]);

        return back()->with('success', 'Template created');
    }

    public function update(Request $request, ClinicalNoteTemplate $template)
    {
        Gate::authorize('manageClinicalTemplates');
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $template->update($data);
        return back()->with('success', 'Template updated');
    }

    public function toggle(ClinicalNoteTemplate $template)
    {
        Gate::authorize('manageClinicalTemplates');
        $template->active = !$template->active;
        $template->save();
        return back();
    }

    public function destroy(ClinicalNoteTemplate $template)
    {
        Gate::authorize('manageClinicalTemplates');
        $template->delete();
        return back()->with('success', 'Template deleted');
    }
}
