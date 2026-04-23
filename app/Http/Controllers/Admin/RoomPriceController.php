<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\RoomPrice;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RoomPriceController extends Controller
{
    public function index(Request $request)
    {
        $hotels  = Hotel::active()->get();
        $hotelId = $request->query('hotel_id', $hotels->first()?->id);
        $month   = $request->query('month', now()->format('Y-m'));

        return view('admin.room-prices.index', compact('hotels', 'hotelId', 'month'));
    }

    /**
     * AJAX — données calendrier : prix nuit par nuit pour un hôtel/mois.
     * Les tarifs sont uniquement basés sur les configs d'occupation (pas sur les types de chambre directement).
     */
    public function calendarData(Request $request): JsonResponse
    {
        $hotelId = $request->integer('hotel_id');

        $start = $request->filled('start_date')
            ? Carbon::parse($request->string('start_date'))->startOfDay()
            : now()->startOfDay();

        $end = $request->filled('end_date')
            ? Carbon::parse($request->string('end_date'))->endOfDay()
            : $start->copy()->addDays(29)->endOfDay();

        // Tous les types de chambre de l'hôtel, sans aucun filtre is_active
        $roomTypes = RoomType::where('hotel_id', $hotelId)
            ->orderBy('name')
            ->with('occupancyConfigs')
            ->get();

        $result = [];

        foreach ($roomTypes as $rt) {
            $configs = $rt->occupancyConfigs; // Toutes configs, actives ou non

            if ($configs->isEmpty()) {
                // Pas de config : on affiche quand même la ligne pour que l'admin voie le type
                $result[] = [
                    'id'                  => (string) $rt->id,
                    'name'                => $rt->name,
                    'room_type_id'        => $rt->id,
                    'occupancy_config_id' => null,
                    'config_code'         => null,
                    'prices'              => [],
                    'is_base'             => false,
                    'is_auto'             => false,
                    'has_configs'         => false,
                ];
                continue;
            }

            foreach ($configs as $cfg) {
                $pricePeriods = RoomPrice::where('room_type_id', $rt->id)
                    ->where('occupancy_config_id', $cfg->id)
                    ->where('is_active', true)
                    ->where('date_from', '<=', $end->toDateString())
                    ->where('date_to',   '>=', $start->toDateString())
                    ->orderByDesc('created_at')
                    ->get();

                $dayMap  = [];
                $current = $start->copy();
                while ($current->lte($end)) {
                    $date = $current->toDateString();
                    foreach ($pricePeriods as $p) {
                        if ($current->between($p->date_from, $p->date_to)) {
                            $dayMap[$date] = (float) $p->price_per_night;
                            break;
                        }
                    }
                    $current->addDay();
                }

                $result[] = [
                    'id'                  => $rt->id . '_' . $cfg->id,
                    'name'                => $rt->name . ' — ' . $cfg->label,
                    'room_type_id'        => $rt->id,
                    'occupancy_config_id' => $cfg->id,
                    'config_code'         => $cfg->code,
                    'prices'              => $dayMap,
                    'is_base'             => false,
                    'is_auto'             => false,
                    'has_configs'         => true,
                ];
            }
        }

        // Debug : retourné dans la réponse JSON pour faciliter le diagnostic
        $debug = [
            'hotel_id'        => $hotelId,
            'room_types_found'=> $roomTypes->count(),
            'rows_generated'  => count($result),
        ];

        return response()->json(['rows' => $result, 'debug' => $debug]);
    }

    /**
     * AJAX — sauvegarde d'une plage tarifaire.
     * Le tarif est toujours lié à une config d'occupation spécifique.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hotel_id'            => 'required|exists:hotels,id',
            'room_type_id'        => 'required|exists:room_types,id',
            'occupancy_config_id' => 'required|exists:room_occupancy_configs,id',
            'date_from'           => 'required|date',
            'date_to'             => 'required|date|after_or_equal:date_from',
            'price_per_night'     => 'required|numeric|min:0',
            'label'               => 'nullable|string|max:100',
        ]);

        RoomPrice::create(array_merge($data, [
            'currency'  => 'MAD',
            'is_active' => true,
        ]));

        return response()->json(['success' => true]);
    }

    public function create(Request $request)
    {
        $hotels    = Hotel::active()->get();
        $roomTypes = collect();

        if ($hotelId = $request->query('hotel_id')) {
            $roomTypes = RoomType::where('hotel_id', $hotelId)->active()->get();
        }

        return view('admin.room-prices.create', compact('hotels', 'roomTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_id'            => 'required|exists:hotels,id',
            'room_type_id'        => 'required|exists:room_types,id',
            'occupancy_config_id' => 'required|exists:room_occupancy_configs,id',
            'date_from'           => 'required|date',
            'date_to'             => 'required|date|after_or_equal:date_from',
            'price_per_night'     => 'required|numeric|min:0',
            'currency'            => 'nullable|string|size:3',
            'label'               => 'nullable|string|max:100',
            'is_active'           => 'boolean',
        ]);

        $data['currency']  = $data['currency']  ?? 'MAD';
        $data['is_active'] = $data['is_active'] ?? true;

        RoomPrice::create($data);

        return redirect()
            ->route('admin.room-prices.index', ['hotel_id' => $data['hotel_id']])
            ->with('success', 'Tarif créé avec succès.');
    }

    public function edit(RoomPrice $roomPrice)
    {
        $hotels    = Hotel::active()->get();
        $roomTypes = RoomType::where('hotel_id', $roomPrice->hotel_id)->active()->get();
        return view('admin.room-prices.edit', compact('roomPrice', 'hotels', 'roomTypes'));
    }

    public function update(Request $request, RoomPrice $roomPrice)
    {
        $data = $request->validate([
            'hotel_id'      => 'required|exists:hotels,id',
            'room_type_id'  => 'required|exists:room_types,id',
            'date_from'     => 'required|date',
            'date_to'       => 'required|date|after_or_equal:date_from',
            'price_per_night'=> 'required|numeric|min:0',
            'currency'      => 'nullable|string|size:3',
            'label'         => 'nullable|string|max:100',
            'is_active'     => 'boolean',
        ]);

        $roomPrice->update($data);

        return redirect()
            ->route('admin.room-prices.index')
            ->with('success', 'Tarif mis à jour.');
    }

    public function destroy(RoomPrice $roomPrice)
    {
        $roomPrice->delete();
        return redirect()->route('admin.room-prices.index')->with('success', 'Tarif supprimé.');
    }

    /**
     * Sauvegarde en masse depuis le tableau tarifaire éditable.
     * Reçoit: hotel_id, periods[] (date_from, date_to, label), prices[config_id][period_idx] = price
     */
    public function tableSave(Request $request)
    {
        $data = $request->validate([
            'hotel_id'              => 'required|exists:hotels,id',
            'periods'               => 'required|array|min:1',
            'periods.*.date_from'   => 'required|date',
            'periods.*.date_to'     => 'required|date|after_or_equal:periods.*.date_from',
            'periods.*.label'       => 'nullable|string|max:100',
            'prices'                => 'nullable|array',
            'prices.*.*'            => 'nullable|numeric|min:0|max:99999',
        ]);

        $hotelId = $data['hotel_id'];
        $periods = $data['periods'];
        $prices  = $data['prices'] ?? [];
        $saved   = 0;

        foreach ($prices as $configId => $periodPrices) {
            foreach ($periodPrices as $periodIdx => $price) {
                if ($price === null || $price === '') continue;

                $priceFloat = (float) $price;
                // Ignorer les valeurs hors-range (protection contre overflow DECIMAL(10,2))
                if ($priceFloat < 0 || $priceFloat > 99999) continue;

                $period = $periods[$periodIdx] ?? null;
                if (! $period) continue;

                // Vérifier que la config d'occupation existe (peut avoir été supprimée)
                $config = \App\Models\RoomOccupancyConfig::find($configId);
                if (! $config) continue;  // config introuvable → skip pour éviter room_type_id = null

                $dateFrom = \Carbon\Carbon::parse($period['date_from'])->toDateString();
                $dateTo   = \Carbon\Carbon::parse($period['date_to'])->toDateString();

                $existing = RoomPrice::where([
                    'hotel_id'            => $hotelId,
                    'occupancy_config_id' => $configId,
                    'date_from'           => $dateFrom,
                    'date_to'             => $dateTo,
                ])->first();

                $oldPrice = $existing?->price_per_night;

                // Only record if price actually changed (or is new)
                if ($oldPrice === null || abs((float)$oldPrice - $priceFloat) > 0.001) {
                    \App\Models\RoomPriceHistory::create([
                        'hotel_id'            => $hotelId,
                        'occupancy_config_id' => $configId,
                        'room_type_id'        => $config->room_type_id,
                        'date_from'           => $dateFrom,
                        'date_to'             => $dateTo,
                        'label'               => $period['label'] ?? null,
                        'old_price'           => $oldPrice,
                        'new_price'           => $priceFloat,
                        'delta'               => $oldPrice !== null ? round($priceFloat - (float)$oldPrice, 2) : null,
                        'changed_by_id'       => auth()->id(),
                        'changed_by_name'     => auth()->user()?->name,
                    ]);
                }

                RoomPrice::updateOrCreate(
                    [
                        'hotel_id'            => $hotelId,
                        'occupancy_config_id' => $configId,
                        'date_from'           => $dateFrom,
                        'date_to'             => $dateTo,
                    ],
                    [
                        'room_type_id'    => $config->room_type_id,
                        'price_per_night' => $priceFloat,
                        'label'           => $period['label'] ?? null,
                        'currency'        => 'MAD',
                        'is_active'       => true,
                    ]
                );
                $saved++;
            }
        }

        // Persister les périodes en session pour les retrouver après sauvegarde
        $cleanPeriods = array_values(array_map(fn($p) => [
            'date_from' => \Carbon\Carbon::parse($p['date_from'])->format('Y-m-d'),
            'date_to'   => \Carbon\Carbon::parse($p['date_to'])->format('Y-m-d'),
            'label'     => $p['label'] ?? '',
        ], $periods));

        session(["price_table_periods_{$hotelId}" => $cleanPeriods]);

        return redirect()
            ->route('admin.room-prices.table', ['hotel_id' => $hotelId])
            ->with('success', "{$saved} tarif(s) sauvegardé(s) avec succès.");
    }

    /**
     * Vue tableau des tarifs par période — similaire au document papier.
     */
    public function table(Request $request)
    {
        $hotels  = Hotel::active()->get();
        $hotelId = (int) $request->query('hotel_id', $hotels->first()?->id ?? 0);

        // Year / period filters
        $yearFilter = $request->query('year') ? (int) $request->query('year') : null;
        $periodFrom = $request->query('period_from') ?: null;
        $periodTo   = $request->query('period_to')   ?: null;

        // Périodes distinctes (date_from / date_to) pour cet hôtel, triées par date
        $periods     = collect();
        $roomTypes   = collect();
        $priceMatrix = []; // [config_id][period_key] = price_per_night (tarifs NRF de base)
        $tariffGrids = collect();
        $activeGridId = null;
        $availableYears = collect();

        if ($hotelId) {
            // Années distinctes réellement stockées en DB pour cet hôtel
            $availableYears = RoomPrice::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->whereNotNull('occupancy_config_id')
                ->selectRaw('YEAR(date_from) as year')
                ->groupBy('year')
                ->orderBy('year')
                ->pluck('year');

            // Charger les périodes depuis les tarifs réellement enregistrés en DB
            // (distinct sur date_from + date_to → une ligne par période)
            $periodsQuery = RoomPrice::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->whereNotNull('occupancy_config_id')
                ->select('date_from', 'date_to', 'label')
                ->orderBy('date_from');

            // Apply year filter: date_from falls within the given year
            if ($yearFilter) {
                $periodsQuery->whereYear('date_from', $yearFilter);
            }

            // Apply period_from / period_to range filter: periods that overlap [periodFrom, periodTo]
            if ($periodFrom && $periodTo) {
                $periodsQuery->where('date_from', '<=', $periodTo)
                             ->where('date_to',   '>=', $periodFrom);
            } elseif ($periodFrom) {
                $periodsQuery->where('date_to', '>=', $periodFrom);
            } elseif ($periodTo) {
                $periodsQuery->where('date_from', '<=', $periodTo);
            }

            $periods = $periodsQuery->get()
                ->map(fn($p) => (object)[
                    'date_from' => \Carbon\Carbon::parse($p->date_from)->format('Y-m-d'),
                    'date_to'   => \Carbon\Carbon::parse($p->date_to)->format('Y-m-d'),
                    'label'     => $p->label ?? '',
                ])
                ->unique(fn($p) => $p->date_from . '_' . $p->date_to)
                ->values();

            $roomTypes = RoomType::where('hotel_id', $hotelId)
                ->orderBy('created_at',"asc")
                ->with(['occupancyConfigs' => fn($q) => $q->orderBy('sort_order')])
                ->get();

            // Tarifs de base NRF depuis la DB
            // On joint avec room_occupancy_configs pour exclure les tarifs orphelins
            // (config supprimée → room_type_id serait null → violation SQL à la sauvegarde)
            $validConfigIds = \App\Models\RoomOccupancyConfig::whereHas('roomType', fn($q) => $q->where('hotel_id', $hotelId))
                ->pluck('id')
                ->toArray();

            RoomPrice::where('hotel_id', $hotelId)
                ->where('is_active', true)
                ->whereNotNull('occupancy_config_id')
                ->whereIn('occupancy_config_id', $validConfigIds)
                ->get()
                ->each(function ($p) use (&$priceMatrix) {
                    $key = \Carbon\Carbon::parse($p->date_from)->format('Y-m-d')
                         . '_'
                         . \Carbon\Carbon::parse($p->date_to)->format('Y-m-d');
                    $priceMatrix[$p->occupancy_config_id][$key] = (float) $p->price_per_night;
                });

            // Grilles tarifaires — initialiser si vide
            $tariffGrids = \App\Models\TariffGrid::where('hotel_id', $hotelId)
                ->with('baseGrid')
                ->orderBy('sort_order')
                ->get();

            if ($tariffGrids->isEmpty()) {
                \Database\Seeders\TariffGridSeeder::seedForHotel($hotelId);
                $tariffGrids = \App\Models\TariffGrid::where('hotel_id', $hotelId)
                    ->with('baseGrid')
                    ->orderBy('sort_order')
                    ->get();
            }

            // Grille active : param URL ou grille de base NRF par défaut
            $activeGridId = (int) $request->query('grid_id',
                $tariffGrids->firstWhere('is_base', true)?->id ?? $tariffGrids->first()?->id
            );
        }

        return view('admin.room-prices.table', compact(
            'hotels', 'hotelId', 'periods', 'roomTypes', 'priceMatrix',
            'tariffGrids', 'activeGridId',
            'yearFilter', 'periodFrom', 'periodTo', 'availableYears'
        ));
    }

    /**
     * Historique complet des modifications de tarifs.
     */
    public function history(Request $request)
    {
        $hotels  = Hotel::active()->get();
        $hotelId = (int) $request->query('hotel_id', $hotels->first()?->id ?? 0);

        $history = \App\Models\RoomPriceHistory::where('hotel_id', $hotelId)
            ->with('occupancyConfig.roomType')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.room-prices.history', compact('hotels', 'hotelId', 'history'));
    }

    /**
     * AJAX — supprime tous les tarifs d'une période donnée pour un hôtel.
     * Appelé quand l'admin clique sur × dans le tableau tarifaire.
     */
    public function deletePeriod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hotel_id'  => 'required|exists:hotels,id',
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
        ]);

        $deleted = RoomPrice::where('hotel_id', $data['hotel_id'])
            ->where('date_from', Carbon::parse($data['date_from'])->toDateString())
            ->where('date_to',   Carbon::parse($data['date_to'])->toDateString())
            ->delete();

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }

    /**
     * AJAX : retourne les types de chambres d'un hôtel.
     */
    public function roomTypesByHotel(Hotel $hotel)
    {
        return response()->json(
            $hotel->activeRoomTypes()->get(['id', 'name'])
        );
    }

    /**
     * AJAX : retourne les types de chambres d'un hôtel avec leurs configs d'occupation.
     * Utilisé par la page de gestion des configs et par le formulaire de réservation.
     */
    public function roomTypesWithConfigs(Hotel $hotel)
    {
        $roomTypes = $hotel->roomTypes()
            ->orderBy('name')
            ->with(['occupancyConfigs' => fn($q) => $q->orderBy('sort_order')])
            ->get()
            ->map(fn ($rt) => [
                'id'       => $rt->id,
                'name'     => $rt->name,
                'capacity' => $rt->capacity,
                'max_persons'  => $rt->max_persons,
                'max_adults'   => $rt->max_adults,
                'max_children' => $rt->max_children,
                'baby_bed_available' => $rt->baby_bed_available,
                'configs'  => $rt->occupancyConfigs->map(fn ($cfg) => [
                    'id'           => $cfg->id,
                    'code'         => $cfg->code,
                    'label'        => $cfg->label,
                    'min_adults'   => $cfg->min_adults,
                    'max_adults'   => $cfg->max_adults,
                    'min_children' => $cfg->min_children,
                    'max_children' => $cfg->max_children,
                    'min_babies'   => $cfg->min_babies,
                    'max_babies'   => $cfg->max_babies,
                ]),
            ]);

        return response()->json($roomTypes);
    }

    /**
     * Export Excel du tableau tarifaire — format document papier.
     * Une feuille par grille tarifaire (NRF, FLEX, etc.)
     */
    public function exportExcel(Request $request)
    {
        $hotels  = Hotel::active()->get();
        $hotelId = (int) $request->query('hotel_id', $hotels->first()?->id ?? 0);

        if (! $hotelId) {
            return redirect()->route('admin.room-prices.table')->with('error', 'Hôtel introuvable.');
        }

        $hotel = Hotel::findOrFail($hotelId);

        // ── 1. Périodes ───────────────────────────────────────────────────────
        $periods = RoomPrice::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->whereNotNull('occupancy_config_id')
            ->select('date_from', 'date_to', 'label')
            ->orderBy('date_from')
            ->get()
            ->map(fn($p) => (object)[
                'date_from' => Carbon::parse($p->date_from)->format('Y-m-d'),
                'date_to'   => Carbon::parse($p->date_to)->format('Y-m-d'),
                'label'     => $p->label ?? '',
            ])
            ->unique(fn($p) => $p->date_from . '_' . $p->date_to)
            ->values();

        // ── 2. Types de chambre + configs ─────────────────────────────────────
        $roomTypes = RoomType::where('hotel_id', $hotelId)
            ->orderBy('created_at', 'asc')
            ->with(['occupancyConfigs' => fn($q) => $q->orderBy('sort_order')])
            ->get();

        // ── 3. Matrice prix NRF (base) ────────────────────────────────────────
        $validConfigIds = \App\Models\RoomOccupancyConfig::whereHas('roomType', fn($q) => $q->where('hotel_id', $hotelId))
            ->pluck('id')->toArray();

        $priceMatrix = []; // [config_id][period_key] = price_per_night
        RoomPrice::where('hotel_id', $hotelId)
            ->where('is_active', true)
            ->whereNotNull('occupancy_config_id')
            ->whereIn('occupancy_config_id', $validConfigIds)
            ->get()
            ->each(function ($p) use (&$priceMatrix) {
                $key = Carbon::parse($p->date_from)->format('Y-m-d')
                     . '_'
                     . Carbon::parse($p->date_to)->format('Y-m-d');
                $priceMatrix[$p->occupancy_config_id][$key] = (float) $p->price_per_night;
            });

        // ── 4. Grilles tarifaires ─────────────────────────────────────────────
        $tariffGrids = \App\Models\TariffGrid::where('hotel_id', $hotelId)
            ->orderBy('sort_order')
            ->get();

        $allGridsById = $tariffGrids->keyBy('id')->toArray();
        // Convertir en objets accessibles dans calculatePrice()
        $allGridsById = $tariffGrids->keyBy('id');

        // ── 5. Construire le Spreadsheet ──────────────────────────────────────
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Tarifs ' . $hotel->name)
            ->setCreator('Magic Hotels');

        $sheetIndex = 0;

        foreach ($tariffGrids as $grid) {
            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }
            $sheet->setTitle(mb_substr($grid->code, 0, 31));
            $sheetIndex++;

            $this->fillTariffSheet($sheet, $hotel, $grid, $allGridsById, $periods, $roomTypes, $priceMatrix);
        }

        // ── 6. Streamer le fichier ────────────────────────────────────────────
        $filename = 'tarifs_' . str_replace(' ', '_', $hotel->name) . '_' . now()->format('Ymd') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Remplit une feuille Excel avec les tarifs d'une grille.
     * Compatible PhpSpreadsheet v2.x (pas de méthodes ByColumnAndRow).
     */
    private function fillTariffSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        Hotel $hotel,
        \App\Models\TariffGrid $grid,
        $allGridsById,
        $periods,
        $roomTypes,
        array $priceMatrix
    ): void {
        // Helper : convertit (colonne_int, ligne_int) → coordonnée Excel (ex: "D5")
        $c = fn(int $col, int $row): string => Coordinate::stringFromColumnIndex($col) . $row;

        $periodCount = $periods->count();
        $lastColInt  = 3 + $periodCount;
        $lastColStr  = Coordinate::stringFromColumnIndex($lastColInt);

        $colorHeader  = 'D97706';
        $colorSubHead = 'FEF3C7';
        $colorGroup   = 'FDE68A';
        $colorAlt1    = 'FFFBEB';
        $colorAlt2    = 'FFFFFF';
        $colorBorder  = 'D1D5DB';

        // ── Ligne 1 : Titre ───────────────────────────────────────────────────
        $sheet->setCellValue('A1', strtoupper($hotel->name) . '  —  ' . $grid->name);
        $sheet->mergeCells('A1:' . $lastColStr . '1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorHeader]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // ── Ligne 2 : Sous-titre ──────────────────────────────────────────────
        $subtitle = 'MAJ du ' . now()->format('d/m/Y')
            . '  ·  ' . ($grid->is_base ? 'Tarif de base' : 'Grille dérivée : ' . ($grid->formulaLabel() ?? ''))
            . '  ·  Prix par chambre et par nuit en All Inclusive, Hors Taxes de Séjour';
        $sheet->setCellValue('A2', $subtitle);
        $sheet->mergeCells('A2:' . $lastColStr . '2');
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '6B7280'], 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorSubHead]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // ── Ligne 3 : En-têtes colonnes ───────────────────────────────────────
        $sheet->setCellValue('A3', 'Type de chambre');
        $sheet->mergeCells('A3:B3');
        $sheet->setCellValue('C3', 'Occupation');

        $colIdx = 4;
        foreach ($periods as $p) {
            $from = Carbon::parse($p->date_from)->format('d/m/y');
            $to   = Carbon::parse($p->date_to)->format('d/m/y');
            $sheet->setCellValue($c($colIdx, 3), "Du {$from}\nau {$to}");
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIdx))->setWidth(13);
            $colIdx++;
        }

        $sheet->getStyle('A3:' . $lastColStr . '3')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorHeader]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(32);

        // ── Colonnes fixes ────────────────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(4);
        $sheet->getColumnDimension('C')->setWidth(32);

        // ── Lignes de données ─────────────────────────────────────────────────
        $row              = 4;
        $altToggle        = false;
        $allGridsByIdArr  = $allGridsById->all(); // all() conserve les objets TariffGrid (toArray() les convertirait en tableaux)

        foreach ($roomTypes as $rt) {
            $configs = $rt->occupancyConfigs;
            if ($configs->isEmpty()) continue;

            $startRow = $row;

            foreach ($configs as $cfgIdx => $cfg) {
                $isFirst = ($cfgIdx === 0);
                $bg      = $altToggle ? $colorAlt1 : $colorAlt2;

                // Col A : nom type chambre (1ère ligne du groupe uniquement)
                if ($isFirst) {
                    $sheet->setCellValue($c(1, $row), $rt->name);
                }
                $sheet->getStyle($c(1, $row))->applyFromArray([
                    'font' => ['bold' => $isFirst, 'size' => 9, 'name' => 'Arial', 'color' => ['rgb' => '1F2937']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorGroup]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);

                // Col B : couleur groupe
                $sheet->getStyle($c(2, $row))->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorGroup]],
                ]);

                // Col C : label occupation
                $sheet->setCellValue($c(3, $row), $cfg->label);
                $sheet->getStyle($c(3, $row))->applyFromArray([
                    'font'      => ['size' => 8, 'name' => 'Arial', 'color' => ['rgb' => '374151']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Colonnes prix
                $pColIdx = 4;
                foreach ($periods as $p) {
                    $periodKey = $p->date_from . '_' . $p->date_to;
                    $basePrice = $priceMatrix[$cfg->id][$periodKey] ?? null;
                    $coord     = $c($pColIdx, $row);

                    if ($basePrice !== null) {
                        $price = $grid->is_base
                            ? $basePrice
                            : $grid->calculatePrice($basePrice, $allGridsByIdArr);
                        $sheet->setCellValue($coord, round($price, 2));
                        $sheet->getStyle($coord)->getNumberFormat()->setFormatCode('#,##0.00');
                    }

                    $sheet->getStyle($coord)->applyFromArray([
                        'font'      => ['size' => 9, 'name' => 'Arial', 'color' => ['rgb' => '111827']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $pColIdx++;
                }

                $sheet->getRowDimension($row)->setRowHeight(16);
                $row++;
            }

            // Fusionner col A sur tout le groupe du type de chambre
            if ($row - $startRow > 1) {
                $sheet->mergeCells('A' . $startRow . ':A' . ($row - 1));
                $sheet->getStyle('A' . $startRow . ':A' . ($row - 1))->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setWrapText(true);
            }

            $altToggle = ! $altToggle;
        }

        // ── Bordures ──────────────────────────────────────────────────────────
        $sheet->getStyle('A3:' . $lastColStr . ($row - 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => $colorBorder],
                ],
            ],
        ]);

        // ── Freeze + mise en page ─────────────────────────────────────────────
        $sheet->freezePane('D4');
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A3);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.3)->setRight(0.3);
        $sheet->getHeaderFooter()
            ->setOddHeader('&C&B' . $hotel->name . ' — ' . $grid->name)
            ->setOddFooter('&LMagic Hotels&R&P / &N');
    }
}