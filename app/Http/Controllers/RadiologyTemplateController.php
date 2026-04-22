<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RadiologyTemplateController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $templates = \App\Models\RadiologyTemplate::when($search, function ($query) use ($search) {
            $query->where('template_number', 'like', "%{$search}%")
                ->orWhere('examination_name', 'like', "%{$search}%");
        })->paginate(15);

        return view('master.templates.index', compact('templates', 'search'));
    }

    public function create()
    {
        return view('master.templates.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_number' => 'required|string|unique:radiology_templates,template_number',
            'examination_name' => 'required|string',
            'expertise' => 'required|string',
        ]);

        \App\Models\RadiologyTemplate::create($validated);
        return redirect()->route('master.templates.index')->with('success', 'Template berhasil dibuat.');
    }

    public function edit(\App\Models\RadiologyTemplate $template)
    {
        return view('master.templates.form', compact('template'));
    }

    public function update(Request $request, \App\Models\RadiologyTemplate $template)
    {
        $validated = $request->validate([
            'template_number' => 'required|string|unique:radiology_templates,template_number,' . $template->id,
            'examination_name' => 'required|string',
            'expertise' => 'required|string',
        ]);

        $template->update($validated);
        return redirect()->route('master.templates.index')->with('success', 'Template berhasil diperbarui.');
    }

    public function destroy(\App\Models\RadiologyTemplate $template)
    {
        $template->delete();
        return redirect()->route('master.templates.index')->with('success', 'Template berhasil dihapus.');
    }

    // API for fetching template data via AJAX
    public function apiSearch(Request $request)
    {
        $term = $request->query('term', '');
        $templates = \App\Models\RadiologyTemplate::where('template_number', 'like', "%{$term}%")
            ->orWhere('examination_name', 'like', "%{$term}%")
            ->get(['id', 'template_number', 'examination_name']);

        $formatted = $templates->map(function ($tpl) {
            return [
                'id' => $tpl->id,
                'text' => $tpl->template_number . ' - ' . $tpl->examination_name
            ];
        });

        return response()->json(['results' => $formatted]);
    }

    public function apiGetTemplate(\App\Models\RadiologyTemplate $template)
    {
        return response()->json(['expertise' => $template->expertise]);
    }
}
