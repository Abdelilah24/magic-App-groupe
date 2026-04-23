<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExtraService;
use Illuminate\Http\Request;

class ExtraServiceController extends Controller
{
    public function index()
    {
        $extras = ExtraService::orderBy('name')->get();
        return view('admin.extra-services.index', compact('extras'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'is_active'   => ['boolean'],
        ], [
            'name.required'  => 'Le nom est obligatoire.',
            'price.required' => 'Le prix est obligatoire.',
            'price.numeric'  => 'Le prix doit être un nombre.',
            'price.min'      => 'Le prix ne peut pas être négatif.',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        ExtraService::create($data);

        return back()->with('success', 'Service extra créé avec succès.');
    }

    public function update(Request $request, ExtraService $extraService)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'is_active'   => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $extraService->update($data);

        return back()->with('success', 'Service extra mis à jour.');
    }

    public function destroy(ExtraService $extraService)
    {
        $extraService->delete();
        return back()->with('success', 'Service extra supprimé.');
    }
}
