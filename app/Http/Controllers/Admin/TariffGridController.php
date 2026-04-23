<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\TariffGrid;
use Database\Seeders\TariffGridSeeder;
use Illuminate\Http\Request;

class TariffGridController extends Controller
{
    /**
     * Liste et gestion des grilles tarifaires d'un hôtel.
     */
    public function index(Request $request)
    {
        $hotels  = Hotel::active()->get();
        $hotelId = (int) $request->query('hotel_id', $hotels->first()?->id ?? 0);

        $grids = collect();
        if ($hotelId) {
            $grids = TariffGrid::where('hotel_id', $hotelId)
                ->with('baseGrid')
                ->orderBy('sort_order')
                ->get();

            // Si aucune grille, initialiser avec les défauts
            if ($grids->isEmpty()) {
                TariffGridSeeder::seedForHotel($hotelId);
                $grids = TariffGrid::where('hotel_id', $hotelId)
                    ->with('baseGrid')
                    ->orderBy('sort_order')
                    ->get();
            }
        }

        return view('admin.tariff-grids.index', compact('hotels', 'hotelId', 'grids'));
    }

    /**
     * Mise à jour d'une grille (formule, nom, arrondi).
     */
    public function update(Request $request, TariffGrid $tariffGrid)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:80',
            'operator'       => 'nullable|in:divide,multiply,subtract_percent',
            'operator_value' => 'nullable|numeric|min:0.0001|max:9999',
            'base_grid_id'   => 'nullable|exists:tariff_grids,id',
            'rounding'       => 'required|in:round,ceil,floor,none',
            'is_active'      => 'boolean',
        ]);

        // Ne pas modifier les champs de base si c'est la grille NRF
        if ($tariffGrid->is_base) {
            $tariffGrid->update(['name' => $data['name'], 'rounding' => $data['rounding']]);
        } else {
            $tariffGrid->update($data);
        }

        return back()->with('success', "Grille « {$tariffGrid->name} » mise à jour.");
    }

    /**
     * Initialiser les grilles par défaut pour un hôtel (si elles n'existent pas).
     */
    public function initDefaults(Request $request)
    {
        $hotelId = (int) $request->input('hotel_id');
        if (! $hotelId) return back()->with('error', 'Hôtel requis.');

        TariffGridSeeder::seedForHotel($hotelId);

        return back()->with('success', 'Grilles tarifaires initialisées.');
    }

    /**
     * Ajouter une grille personnalisée.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_id'       => 'required|exists:hotels,id',
            'name'           => 'required|string|max:80',
            'code'           => 'required|string|max:30|alpha_dash',
            'base_grid_id'   => 'required|exists:tariff_grids,id',
            'operator'       => 'required|in:divide,multiply,subtract_percent',
            'operator_value' => 'required|numeric|min:0.0001|max:9999',
            'rounding'       => 'required|in:round,ceil,floor,none',
        ]);

        $data['code']      = strtoupper($data['code']);
        $data['is_base']   = false;
        $data['is_active'] = true;
        $data['sort_order'] = TariffGrid::where('hotel_id', $data['hotel_id'])->max('sort_order') + 1;

        TariffGrid::create($data);

        return back()->with('success', "Grille « {$data['name']} » créée.");
    }

    /**
     * Supprimer une grille (non-base uniquement).
     */
    public function destroy(TariffGrid $tariffGrid)
    {
        if ($tariffGrid->is_base) {
            return back()->with('error', 'La grille de base NRF ne peut pas être supprimée.');
        }
        $name = $tariffGrid->name;
        $tariffGrid->delete();

        return back()->with('success', "Grille « {$name} » supprimée.");
    }
}
