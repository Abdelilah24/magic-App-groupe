<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Services\CalendarEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(private readonly CalendarEventService $calendarService) {}

    // ─── Page principale ──────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $currentYear = now()->year;

        // Auto-sync si aucune donnée pour l'année courante
        if (! $this->calendarService->isYearSynced($currentYear)) {
            $this->calendarService->syncYear($currentYear);
        }

        // Tous les événements manuels (tous pays, tous types)
        $manualEvents = CalendarEvent::where('source', 'manual')
            ->orderBy('country')
            ->orderBy('type')
            ->orderBy('start_date')
            ->get();

        // Années disponibles en base (pour le sélecteur)
        $availableYears = CalendarEvent::selectRaw('DISTINCT year')
            ->orderBy('year')
            ->pluck('year');

        return view('admin.calendar.index', compact('manualEvents', 'availableYears', 'currentYear'));
    }

    // ─── Page liste avec filtres ──────────────────────────────────────────────

    public function list(Request $request): \Illuminate\View\View
    {
        $query = CalendarEvent::query();

        // Recherche par nom
        if ($search = trim($request->get('search', ''))) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Filtre pays (tableau ou chaîne)
        $countries = array_filter((array) $request->get('countries', []));
        if (! empty($countries)) {
            $query->whereIn('country', $countries);
        }

        // Filtre type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Filtre année
        if ($year = $request->get('year')) {
            $query->where('year', (int) $year);
        }

        // Filtre source
        if ($source = $request->get('source')) {
            $query->where('source', $source);
        }

        // Filtre zone
        if ($zone = $request->get('zone')) {
            $query->where('zone', 'like', "%{$zone}%");
        }

        // Tri
        $sortBy  = in_array($request->get('sort'), ['start_date', 'name', 'country', 'type', 'year'])
                    ? $request->get('sort') : 'start_date';
        $sortDir = $request->get('dir') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDir)
              ->orderBy('country')
              ->orderBy('start_date');

        $events         = $query->paginate(30)->withQueryString();
        $availableYears = CalendarEvent::selectRaw('DISTINCT year')->orderBy('year')->pluck('year');
        $totalCount     = CalendarEvent::count();

        return view('admin.calendar.list', compact('events', 'availableYears', 'totalCount'));
    }

    // ─── Endpoint JSON pour FullCalendar ─────────────────────────────────────

    /**
     * FullCalendar appelle cette URL avec :
     *   ?start=2026-01-01&end=2026-02-01&countries=MA,FR&zone=B
     */
    public function events(Request $request): JsonResponse
    {
        $start     = $request->query('start', now()->startOfYear()->toDateString());
        $end       = $request->query('end',   now()->endOfYear()->toDateString());
        $countries = array_filter(explode(',', $request->query('countries', 'MA,FR,GB')));
        $zone      = $request->query('zone', 'B');

        // Auto-sync si l'année demandée n'est pas encore en base
        $startYear = (int) substr($start, 0, 4);
        $endYear   = (int) substr($end,   0, 4);
        foreach (array_unique([$startYear, $endYear]) as $year) {
            if (! $this->calendarService->isYearSynced($year)) {
                $this->calendarService->syncYear($year);
            }
        }

        $events = $this->calendarService->getFullCalendarEvents($start, $end, $countries, $zone);

        return response()->json($events);
    }

    // ─── Synchronisation manuelle ─────────────────────────────────────────────

    public function sync(Request $request): \Illuminate\Http\RedirectResponse
    {
        $year = (int) $request->input('year', now()->year);

        ['synced' => $synced, 'errors' => $errors] = $this->calendarService->syncYear($year);

        if (! empty($errors)) {
            $msg = "Synchronisation {$year} : {$synced} événement(s). " . implode(' | ', $errors);
            return back()->with('warning', $msg);
        }

        return back()->with('success', "Synchronisation {$year} terminée : {$synced} événement(s) importé(s).");
    }

    // ─── CRUD Événements manuels (tous pays, tous types) ─────────────────────

    public function storeManualEvent(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'country'    => 'required|in:MA,FR,GB',
            'type'       => 'required|in:holiday,school_vacation',
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'zone'       => 'nullable|string|max:100',
        ]);

        // La zone n'est pertinente que pour les vacances scolaires françaises
        $zone = ($data['type'] === 'school_vacation' && $data['country'] === 'FR')
            ? ($data['zone'] ?: null)
            : null;

        CalendarEvent::create([
            'country'    => $data['country'],
            'type'       => $data['type'],
            'name'       => $data['name'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'year'       => (int) date('Y', strtotime($data['start_date'])),
            'source'     => 'manual',
            'zone'       => $zone,
        ]);

        $typeLabel    = $data['type'] === 'holiday' ? 'Jour férié' : 'Vacances scolaires';
        $countryLabel = match($data['country']) {
            'MA'    => 'Maroc',
            'FR'    => 'France',
            'GB'    => 'Royaume-Uni',
            default => $data['country'],
        };

        return back()->with('success', "{$typeLabel} « {$data['name']} » ({$countryLabel}) ajouté(e).");
    }

    public function updateManualEvent(Request $request, CalendarEvent $calendarEvent): \Illuminate\Http\RedirectResponse
    {
        abort_if($calendarEvent->source !== 'manual', 403);

        $data = $request->validate([
            'country'    => 'required|in:MA,FR,GB',
            'type'       => 'required|in:holiday,school_vacation',
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'zone'       => 'nullable|string|max:100',
        ]);

        $zone = ($data['type'] === 'school_vacation' && $data['country'] === 'FR')
            ? ($data['zone'] ?: null)
            : null;

        $calendarEvent->update([
            'country'    => $data['country'],
            'type'       => $data['type'],
            'name'       => $data['name'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'year'       => (int) date('Y', strtotime($data['start_date'])),
            'zone'       => $zone,
        ]);

        return back()->with('success', "Événement « {$data['name']} » mis à jour.");
    }

    public function destroyManualEvent(CalendarEvent $calendarEvent): \Illuminate\Http\RedirectResponse
    {
        abort_if($calendarEvent->source !== 'manual', 403);

        $name = $calendarEvent->name;
        $calendarEvent->delete();

        return back()->with('success', "Événement « {$name} » supprimé.");
    }

    // ─── CRUD Vacances scolaires Maroc — conservé pour compatibilité ──────────

    public function storeMaVacation(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->merge(['country' => 'MA', 'type' => 'school_vacation']);
        return $this->storeManualEvent($request);
    }

    public function updateMaVacation(Request $request, CalendarEvent $calendarEvent): \Illuminate\Http\RedirectResponse
    {
        $request->merge(['country' => 'MA', 'type' => 'school_vacation']);
        return $this->updateManualEvent($request, $calendarEvent);
    }

    public function destroyMaVacation(CalendarEvent $calendarEvent): \Illuminate\Http\RedirectResponse
    {
        return $this->destroyManualEvent($calendarEvent);
    }
}
