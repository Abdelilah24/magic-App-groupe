<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function index(Request $request)
    {
        $hotels    = Hotel::active()->get();
        $hotelId   = $request->query('hotel_id');

        $query = RoomType::with('hotel');
        if ($hotelId) $query->where('hotel_id', $hotelId);

        $roomTypes = $query->paginate(15);

        return view('admin.room-types.index', compact('roomTypes', 'hotels', 'hotelId'));
    }

    public function create()
    {
        $hotels = Hotel::active()->get();
        return view('admin.room-types.create', compact('hotels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_id'           => 'required|exists:hotels,id',
            'name'               => 'required|string|max:255',
            'capacity'           => 'required|integer|min:1',
            'min_persons'        => 'required|integer|min:1',
            'max_persons'        => 'required|integer|min:1|gte:min_persons',
            'total_rooms'        => 'required|integer|min:0',
            'max_adults'         => 'nullable|integer|min:0',
            'max_children'       => 'nullable|integer|min:0',
            'baby_bed_available' => 'boolean',
            'description'        => 'nullable|string',
            'is_active'          => 'boolean',
        ]);

        $data['capacity']           = $data['max_persons'];
        $data['baby_bed_available'] = $request->boolean('baby_bed_available');
        $data['is_active']          = $request->boolean('is_active');

        $roomType = RoomType::create($data);

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', "Type « {$roomType->name} » créé.");
    }

    public function edit(RoomType $roomType)
    {
        $hotels = Hotel::active()->get();
        return view('admin.room-types.edit', compact('roomType', 'hotels'));
    }

    public function update(Request $request, RoomType $roomType)
    {
        $data = $request->validate([
            'hotel_id'           => 'required|exists:hotels,id',
            'name'               => 'required|string|max:255',
            'capacity'           => 'required|integer|min:1',
            'min_persons'        => 'required|integer|min:1',
            'max_persons'        => 'required|integer|min:1|gte:min_persons',
            'total_rooms'        => 'required|integer|min:0',
            'max_adults'         => 'nullable|integer|min:0',
            'max_children'       => 'nullable|integer|min:0',
            'baby_bed_available' => 'boolean',
            'description'        => 'nullable|string',
            'is_active'          => 'boolean',
        ]);

        $data['capacity']           = $data['max_persons'];
        $data['baby_bed_available'] = $request->boolean('baby_bed_available');
        $data['is_active']          = $request->boolean('is_active');

        $roomType->update($data);

        return redirect()
            ->route('admin.room-types.index')
            ->with('success', 'Type de chambre mis à jour.');
    }

    public function destroy(RoomType $roomType)
    {
        $roomType->delete();
        return redirect()->route('admin.room-types.index')->with('success', 'Supprimé.');
    }
}
