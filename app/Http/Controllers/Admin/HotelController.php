<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HotelController extends Controller
{
    public function index()
    {
        $hotels = Hotel::withCount(['reservations', 'roomTypes'])->paginate(15);
        return view('admin.hotels.index', compact('hotels'));
    }

    public function create()
    {
        return view('admin.hotels.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'address'     => 'nullable|string',
            'city'        => 'nullable|string|max:100',
            'country'     => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:30',
            'email'       => 'nullable|email',
            'description' => 'nullable|string',
            'stars'       => 'nullable|integer|min:1|max:5',
            'is_active'   => 'boolean',
            'logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'bank_name'   => 'nullable|string|max:255',
            'bank_rib'    => 'nullable|string|max:100',
            'bank_iban'   => 'nullable|string|max:50',
            'bank_swift'  => 'nullable|string|max:20',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('hotels/logos', 'public');
        }

        $hotel = Hotel::create($data);

        return redirect()
            ->route('admin.hotels.show', $hotel)
            ->with('success', "Hôtel « {$hotel->name} » créé avec succès.");
    }

    public function show(Hotel $hotel)
    {
        $hotel->load(['roomTypes', 'reservations' => fn($q) => $q->latest()->take(5)]);
        return view('admin.hotels.show', compact('hotel'));
    }

    public function edit(Hotel $hotel)
    {
        $hotel->load(['activeRoomTypes' => fn($q) => $q->orderBy('name')]);
        return view('admin.hotels.edit', compact('hotel'));
    }

    public function update(Request $request, Hotel $hotel)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'address'     => 'nullable|string',
            'city'        => 'nullable|string|max:100',
            'country'     => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:30',
            'email'       => 'nullable|email',
            'description' => 'nullable|string',
            'stars'       => 'nullable|integer|min:1|max:5',
            'is_active'   => 'boolean',
            'logo'        => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'bank_name'   => 'nullable|string|max:255',
            'bank_rib'    => 'nullable|string|max:100',
            'bank_iban'   => 'nullable|string|max:50',
            'bank_swift'  => 'nullable|string|max:20',
            // Promos long séjour
            'promo_long_stay_enabled' => 'boolean',
            'promo_tier1_nights'      => 'nullable|integer|min:1|max:30',
            'promo_tier1_rate'        => 'nullable|numeric|min:0|max:100',
            'promo_tier2_nights'      => 'nullable|integer|min:1|max:60',
            'promo_tier2_rate'        => 'nullable|numeric|min:0|max:100',
            // Tarification relative
            'pricing_base_room_type_id' => 'nullable|exists:room_types,id',
            'price_offsets'             => 'nullable|array',
            'price_offsets.*'           => 'nullable|numeric|min:-100|max:500',
            // Taxe de séjour
            'taxe_sejour'               => 'nullable|numeric|min:0',
            // Régime de pension
            'meal_plan'                 => 'nullable|in:all_inclusive,bed_and_breakfast,half_board,full_board',
        ]);

        // Construire le JSON des offsets relatifs
        $offsets = [];
        if ($request->filled('pricing_base_room_type_id') && $request->has('price_offsets')) {
            $baseId = (int) $request->input('pricing_base_room_type_id');
            foreach ($request->input('price_offsets', []) as $roomTypeId => $pct) {
                if ((int) $roomTypeId !== $baseId && $pct !== null && $pct !== '') {
                    $offsets[(int) $roomTypeId] = (float) $pct;
                }
            }
        }

        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo si existant
            if ($hotel->logo) {
                Storage::disk('public')->delete($hotel->logo);
            }
            $data['logo'] = $request->file('logo')->store('hotels/logos', 'public');
        }

        $hotel->update(array_merge($data, [
            'promo_long_stay_enabled'   => $request->boolean('promo_long_stay_enabled'),
            'is_active'                 => $request->boolean('is_active'),
            'pricing_base_room_type_id' => $request->filled('pricing_base_room_type_id')
                ? (int) $request->input('pricing_base_room_type_id')
                : null,
            'room_type_price_offsets'   => ! empty($offsets) ? $offsets : null,
            'taxe_sejour'               => $request->filled('taxe_sejour')
                ? (float) $request->input('taxe_sejour')
                : 19.80,
            'meal_plan'                 => $request->filled('meal_plan') ? $request->input('meal_plan') : null,
        ]));

        return redirect()
            ->route('admin.hotels.show', $hotel)
            ->with('success', "Hôtel mis à jour.");
    }

    public function destroy(Hotel $hotel)
    {
        $hotel->delete();
        return redirect()->route('admin.hotels.index')->with('success', 'Hôtel supprimé.');
    }
}
