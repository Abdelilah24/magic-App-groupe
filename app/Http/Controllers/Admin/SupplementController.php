<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Supplement;
use Illuminate\Http\Request;

class SupplementController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplement::with('hotel')->orderBy('date_from');

        if ($hotelId = $request->query('hotel_id')) {
            $query->where('hotel_id', $hotelId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $supplements = $query->paginate(30)->withQueryString();
        $hotels      = Hotel::active()->orderBy('name')->get();
        return view('admin.supplements.index', compact('supplements', 'hotels'));
    }

    public function create()
    {
        $hotels = Hotel::active()->orderBy('name')->get();
        return view('admin.supplements.create', compact('hotels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_id'    => 'required|exists:hotels,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date_from'   => 'required|date',
            'date_to'     => 'required|date|after_or_equal:date_from',
            'status'      => 'required|in:mandatory,optional',
            'price_adult' => 'required|numeric|min:0',
            'price_child' => 'required|numeric|min:0',
            'price_baby'  => 'required|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        Supplement::create($data);

        return redirect()->route('admin.supplements.index')
            ->with('success', 'Supplément créé avec succès.');
    }

    public function edit(Supplement $supplement)
    {
        $hotels = Hotel::active()->orderBy('name')->get();
        return view('admin.supplements.edit', compact('supplement', 'hotels'));
    }

    public function update(Request $request, Supplement $supplement)
    {
        $data = $request->validate([
            'hotel_id'    => 'required|exists:hotels,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date_from'   => 'required|date',
            'date_to'     => 'required|date|after_or_equal:date_from',
            'status'      => 'required|in:mandatory,optional',
            'price_adult' => 'required|numeric|min:0',
            'price_child' => 'required|numeric|min:0',
            'price_baby'  => 'required|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $supplement->update($data);

        return redirect()->route('admin.supplements.index')
            ->with('success', 'Supplément mis à jour.');
    }

    public function destroy(Supplement $supplement)
    {
        $supplement->delete();
        return back()->with('success', 'Supplément supprimé.');
    }
}
