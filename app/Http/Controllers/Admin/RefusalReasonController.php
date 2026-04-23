<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefusalReason;
use Illuminate\Http\Request;

class RefusalReasonController extends Controller
{
    public function index()
    {
        $reasons = RefusalReason::ordered()->get();
        return view('admin.refusal-reasons.index', compact('reasons'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'      => 'required|string|max:200|unique:refusal_reasons,label',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $reason = RefusalReason::create($data);

        return redirect()
            ->route('admin.refusal-reasons.index')
            ->with('success', "Motif « {$reason->label} » créé.");
    }

    public function update(Request $request, RefusalReason $refusalReason)
    {
        $data = $request->validate([
            'label'      => 'required|string|max:200|unique:refusal_reasons,label,' . $refusalReason->id,
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $refusalReason->update($data);

        return redirect()
            ->route('admin.refusal-reasons.index')
            ->with('success', "Motif mis à jour.");
    }

    public function destroy(RefusalReason $refusalReason)
    {
        $refusalReason->delete();

        return redirect()
            ->route('admin.refusal-reasons.index')
            ->with('success', "Motif supprimé.");
    }
}
