<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\RoomOccupancyConfig;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OccupancyConfigController extends Controller
{
    /**
     * AJAX — liste tous les types de chambres d'un hôtel avec leurs configs.
     * Utilisé par la page de gestion des configs d'occupation.
     */
    public function byHotel(Hotel $hotel): JsonResponse
    {
        $roomTypes = $hotel->roomTypes()
            ->orderBy('name')
            ->with(['occupancyConfigs' => fn($q) => $q->orderBy('sort_order')])
            ->get()
            ->map(fn($rt) => [
                'id'      => $rt->id,
                'name'    => $rt->name,
                'configs' => $rt->occupancyConfigs->map(fn($c) => [
                    'id'           => $c->id,
                    'code'         => $c->code,
                    'label'        => $c->label,
                    'min_adults'   => $c->min_adults,
                    'max_adults'   => $c->max_adults,
                    'min_children' => $c->min_children,
                    'max_children' => $c->max_children,
                    'min_babies'   => $c->min_babies ?? 0,
                    'max_babies'   => $c->max_babies ?? 0,
                    'sort_order'   => $c->sort_order,
                    'is_active'    => (bool) $c->is_active,
                    'coefficient'  => (float) ($c->coefficient ?? 1.0),
                ])->values(),
            ]);

        return response()->json($roomTypes);
    }

    /**
     * AJAX — liste les configs d'occupation d'un type de chambre.
     */
    public function byRoomType(RoomType $roomType): JsonResponse
    {
        $configs = $roomType->activeOccupancyConfigs()
            ->get(['id', 'code', 'label', 'min_adults', 'max_adults',
                   'min_children', 'max_children', 'min_babies', 'max_babies', 'sort_order']);

        return response()->json($configs);
    }

    /**
     * Page de gestion des configs pour un type de chambre.
     */
    public function index(Request $request)
    {
        $hotels   = Hotel::active()->with('roomTypes')->get();
        $hotelId  = $request->query('hotel_id', $hotels->first()?->id);
        $hotel    = $hotels->find($hotelId);
        $roomTypes = $hotel ? $hotel->roomTypes()->with('occupancyConfigs')->get() : collect();

        return view('admin.occupancy-configs.index', compact('hotels', 'hotel', 'roomTypes'));
    }

    /**
     * Enregistrer une nouvelle config (ou remplacer par batch si POST avec tableau).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_type_id'  => 'required|exists:room_types,id',
            'code'          => 'required|string|max:30',
            'label'         => 'required|string|max:255',
            'min_adults'    => 'required|integer|min:0|max:20',
            'max_adults'    => 'required|integer|min:0|max:20',
            'min_children'  => 'required|integer|min:0|max:20',
            'max_children'  => 'required|integer|min:0|max:20',
            'min_babies'    => 'nullable|integer|min:0|max:10',
            'max_babies'    => 'nullable|integer|min:0|max:10',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
            'coefficient'   => 'nullable|numeric|min:0.0001|max:99',
        ]);

        $config = RoomOccupancyConfig::updateOrCreate(
            ['room_type_id' => $data['room_type_id'], 'code' => $data['code']],
            array_merge($data, [
                'min_babies'  => $data['min_babies'] ?? 0,
                'max_babies'  => $data['max_babies'] ?? 0,
                'sort_order'  => $data['sort_order'] ?? 0,
                'is_active'   => $request->boolean('is_active', true),
                'coefficient' => $data['coefficient'] ?? 1.0,
            ])
        );

        return response()->json(['success' => true, 'config' => $config]);
    }

    /**
     * Mise à jour d'une config existante.
     */
    public function update(Request $request, RoomOccupancyConfig $occupancyConfig): JsonResponse
    {
        $data = $request->validate([
            'label'         => 'required|string|max:255',
            'min_adults'    => 'required|integer|min:0|max:20',
            'max_adults'    => 'required|integer|min:0|max:20',
            'min_children'  => 'required|integer|min:0|max:20',
            'max_children'  => 'required|integer|min:0|max:20',
            'min_babies'    => 'nullable|integer|min:0|max:10',
            'max_babies'    => 'nullable|integer|min:0|max:10',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
            'coefficient'   => 'nullable|numeric|min:0.0001|max:99',
        ]);

        $occupancyConfig->update(array_merge($data, [
            'min_babies'  => $data['min_babies'] ?? 0,
            'max_babies'  => $data['max_babies'] ?? 0,
            'is_active'   => $request->boolean('is_active', true),
            'coefficient' => $data['coefficient'] ?? $occupancyConfig->coefficient ?? 1.0,
        ]));

        return response()->json(['success' => true, 'config' => $occupancyConfig->fresh()]);
    }

    /**
     * Suppression d'une config.
     */
    public function destroy(RoomOccupancyConfig $occupancyConfig): JsonResponse
    {
        $occupancyConfig->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Réinitialiser toutes les configs d'un room_type depuis un preset.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'configs'      => 'required|array|min:1',
            'configs.*.code'         => 'required|string|max:30',
            'configs.*.label'        => 'required|string|max:255',
            'configs.*.min_adults'   => 'required|integer|min:0',
            'configs.*.max_adults'   => 'required|integer|min:0',
            'configs.*.min_children' => 'required|integer|min:0',
            'configs.*.max_children' => 'required|integer|min:0',
            'configs.*.min_babies'   => 'nullable|integer|min:0',
            'configs.*.max_babies'   => 'nullable|integer|min:0',
            'configs.*.sort_order'   => 'nullable|integer',
        ]);

        // Désactiver les anciennes
        RoomOccupancyConfig::where('room_type_id', $data['room_type_id'])->delete();

        $created = [];
        foreach ($data['configs'] as $i => $cfg) {
            $created[] = RoomOccupancyConfig::create([
                'room_type_id' => $data['room_type_id'],
                'code'         => $cfg['code'],
                'label'        => $cfg['label'],
                'min_adults'   => $cfg['min_adults'],
                'max_adults'   => $cfg['max_adults'],
                'min_children' => $cfg['min_children'],
                'max_children' => $cfg['max_children'],
                'min_babies'   => $cfg['min_babies'] ?? 0,
                'max_babies'   => $cfg['max_babies'] ?? 0,
                'sort_order'   => $cfg['sort_order'] ?? $i,
                'is_active'    => true,
            ]);
        }

        return response()->json(['success' => true, 'count' => count($created)]);
    }
}
