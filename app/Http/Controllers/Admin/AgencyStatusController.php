<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgencyStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgencyStatusController extends Controller
{
    public function index()
    {
        $statuses = AgencyStatus::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.agency-statuses.index', compact('statuses'));
    }

    public function create()
    {
        return view('admin.agency-statuses.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'description'      => 'nullable|string|max:500',
            'is_default'       => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active']  = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        // Un seul statut par défaut
        if ($data['is_default']) {
            AgencyStatus::where('is_default', true)->update(['is_default' => false]);
        }

        $status = AgencyStatus::create($data);

        return redirect()->route('admin.agency-statuses.index')
            ->with('success', "Statut « {$status->name} » créé.");
    }

    public function edit(AgencyStatus $agencyStatus)
    {
        return view('admin.agency-statuses.edit', compact('agencyStatus'));
    }

    public function update(Request $request, AgencyStatus $agencyStatus)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'description'      => 'nullable|string|max:500',
            'is_default'       => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active']  = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($data['is_default']) {
            AgencyStatus::where('id', '!=', $agencyStatus->id)
                        ->where('is_default', true)
                        ->update(['is_default' => false]);
        }

        $agencyStatus->update($data);

        return redirect()->route('admin.agency-statuses.index')
            ->with('success', "Statut « {$agencyStatus->name} » mis à jour.");
    }

    public function destroy(AgencyStatus $agencyStatus)
    {
        if ($agencyStatus->agencies()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer : des agences utilisent ce statut.');
        }
        $agencyStatus->delete();
        return redirect()->route('admin.agency-statuses.index')
            ->with('success', 'Statut supprimé.');
    }
}
