<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalendarEventService
{
    // ─── API endpoint ─────────────────────────────────────────────────────────

    /**
     * Nager.Date — jours fériés publics.
     * Le code pays est passé en minuscules dans l'URL (ex: /gb, /fr, /ma).
     */
    private const NAGER_URL = 'https://date.nager.at/api/v3/PublicHolidays/{year}/{country}';

    // ─── Traductions FR — jours fériés Maroc ──────────────────────────────────

    /**
     * Traduction anglais → français des jours fériés marocains.
     * Source : champ "name" (EN) retourné par Nager.Date pour MA.
     * Le champ "localName" est en arabe, inutilisable directement.
     */
    private const MA_HOLIDAY_FR = [
        "New Year's Day"                        => "Nouvel An",
        "Proclamation of Independence"           => "Manifeste de l'Indépendance",
        "Labour Day"                             => "Fête du Travail",
        "Throne Day"                             => "Fête du Trône",
        "Recovery of Oued Ed-Dahab"             => "Récupération d'Oued Ed-Dahab",
        "Oued Ed-Dahab Day"                     => "Récupération d'Oued Ed-Dahab",
        "Revolution of the King and the People" => "Révolution du Roi et du Peuple",
        "Youth Day"                              => "Fête de la Jeunesse",
        "Green March"                            => "Marche Verte",
        "Independence Day"                       => "Fête de l'Indépendance",
        // Fêtes religieuses (dates variables, Nager.Date fournit les calculs hégiriens)
        "Eid al-Adha"                           => "Aïd Al-Adha",
        "Eid al-Adha Holiday"                   => "Aïd Al-Adha (2ème jour)",
        "Feast of the Sacrifice"                => "Aïd Al-Adha",
        "Eid ul-Fitr"                           => "Aïd Al-Fitr",
        "Eid al-Fitr"                           => "Aïd Al-Fitr",
        "Eid al-Fitr Holiday"                   => "Aïd Al-Fitr (2ème jour)",
        "Breaking Fast (Eid al-Fitr)"           => "Aïd Al-Fitr",
        "Islamic New Year"                      => "Nouvel An Hégirien",
        "Islamic New Year Holiday"              => "Nouvel An Hégirien (2ème jour)",
        "Prophet's Birthday"                    => "Aïd Al-Mawlid",
        "Mawlid al-Nabi"                        => "Aïd Al-Mawlid",
        "Prophet Muhammad's Birthday"           => "Aïd Al-Mawlid",
        "Prophet Muhammad's Birthday Holiday"   => "Aïd Al-Mawlid (2ème jour)",
    ];

    // ─── Synchronisation principale ───────────────────────────────────────────

    /**
     * Synchronise les jours fériés MA + FR + GB pour une année donnée.
     * Les vacances scolaires (MA, FR, GB) sont gérées manuellement via CRUD.
     *
     * @return array{synced:int, errors:string[]}
     */
    public function syncYear(int $year): array
    {
        $synced = 0;
        $errors = [];

        foreach (['MA', 'FR', 'GB'] as $country) {
            [$n, $err] = $this->syncHolidays($year, $country);
            $synced += $n;
            $errors  = array_merge($errors, $err);
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    /**
     * Vérifie si l'année est déjà synchronisée (≥ 5 jours fériés API toutes sources).
     */
    public function isYearSynced(int $year): bool
    {
        return CalendarEvent::where('year', $year)
            ->where('type', 'holiday')
            ->where('source', 'api')
            ->count() >= 5;
    }

    // ─── Synchronisation jours fériés (Nager.Date) ───────────────────────────

    /**
     * @return array{0:int, 1:string[]}
     */
    private function syncHolidays(int $year, string $country): array
    {
        // Nager.Date accepte les codes pays en minuscules (/gb, /fr, /ma)
        $url = str_replace(
            ['{year}', '{country}'],
            [$year, strtolower($country)],
            self::NAGER_URL
        );

        try {
            $response = Http::timeout(15)->get($url);

            if ($response->failed()) {
                return [0, ["Nager.Date [{$country}/{$year}] : HTTP {$response->status()}"]];
            }

            $data = $response->json();

            if (! is_array($data)) {
                return [0, ["Nager.Date [{$country}/{$year}] : réponse invalide"]];
            }

            // Supprime les anciens jours fériés API pour ce pays/année avant de re-insérer
            CalendarEvent::where('country', $country)
                ->where('type', 'holiday')
                ->where('year', $year)
                ->where('source', 'api')
                ->delete();

            $count = 0;
            foreach ($data as $item) {
                if (empty($item['date'])) continue;

                CalendarEvent::create([
                    'country'    => $country,
                    'type'       => 'holiday',
                    'name'       => $this->resolveHolidayName($item, $country),
                    'start_date' => $item['date'],
                    'end_date'   => $item['date'],
                    'year'       => $year,
                    'source'     => 'api',
                    'zone'       => null,
                ]);
                $count++;
            }

            return [$count, []];

        } catch (\Throwable $e) {
            Log::warning('CalendarEventService::syncHolidays error', [
                'country' => $country,
                'year'    => $year,
                'error'   => $e->getMessage(),
            ]);
            return [0, ["Nager.Date [{$country}/{$year}] : " . $e->getMessage()]];
        }
    }

    /**
     * Détermine le nom à stocker pour un jour férié selon le pays.
     *
     * - MA : traduit depuis l'anglais (champ "name") vers le français.
     *        Le champ "localName" est en arabe, non exploitable directement.
     * - FR : utilise "localName" (déjà en français).
     * - GB : utilise "localName" (anglais, langue officielle).
     * - Autres : "localName" sinon "name".
     */
    private function resolveHolidayName(array $item, string $country): string
    {
        $localName = trim($item['localName'] ?? '');
        $enName    = trim($item['name'] ?? '');

        if ($country === 'MA') {
            // Cherche d'abord une correspondance exacte, puis insensible à la casse
            return self::MA_HOLIDAY_FR[$enName]
                ?? self::MA_HOLIDAY_FR[ucfirst(strtolower($enName))]
                ?? $enName          // fallback : anglais si pas de traduction connue
                ?: 'Jour férié';
        }

        // Pour FR/GB et autres : le localName est dans la langue du pays
        return $localName ?: $enName ?: 'Jour férié';
    }

    // ─── Construction des événements FullCalendar ─────────────────────────────

    /**
     * Retourne les événements formatés pour FullCalendar entre deux dates.
     *
     * @param  string   $start     Date ISO (ex : 2026-01-01)
     * @param  string   $end       Date ISO exclusive (ex : 2026-02-01)
     * @param  string[] $countries Liste de codes pays ['MA','FR','GB']
     * @param  string   $zone      Zone scolaire ('A','B','C') — filtre les vacances FR
     * @return array<int,array>
     */
    public function getFullCalendarEvents(
        string $start,
        string $end,
        array  $countries = ['MA', 'FR', 'GB'],
        string $zone = 'B'
    ): array {
        $events = CalendarEvent::whereIn('country', $countries)
            ->where('start_date', '<',  $end)
            ->where('end_date',   '>=', $start)
            ->get();

        return $events
            ->filter(fn(CalendarEvent $e) => $this->filterByZone($e, $zone))
            ->map(fn(CalendarEvent $e)    => $this->toFullCalendarEvent($e))
            ->values()
            ->all();
    }

    private function filterByZone(CalendarEvent $event, string $zone): bool
    {
        // Jours fériés : jamais de filtre zone
        if ($event->type === 'holiday') return true;
        // Vacances MA et GB : pas de système de zones
        if (in_array($event->country, ['MA', 'GB'])) return true;
        // Vacances FR sans zone renseignée : toutes zones
        if ($event->zone === null) return true;

        return $event->matchesZone($zone);
    }

    private function toFullCalendarEvent(CalendarEvent $event): array
    {
        // FullCalendar : end est EXCLUSIF pour les événements all-day
        $endExclusive = Carbon::parse($event->end_date)->addDay()->format('Y-m-d');

        $countryLabel = match($event->country) {
            'MA'    => 'Maroc',
            'FR'    => 'France',
            'GB'    => 'Royaume-Uni',
            default => $event->country,
        };
        $typeLabel = $event->type === 'holiday' ? 'Jour férié' : 'Vacances scolaires';
        $zoneLabel = $event->zone ? " ({$event->zone})" : '';

        // Pour les couleurs très claires (ex: jaune #ffff00), forcer le texte en noir
        $color     = $event->getFullCalendarColor();
        $textColor = $event->needsDarkText() ? '#1a1a1a' : '#ffffff';

        return [
            'id'        => $event->id,
            'title'     => $event->name,
            'start'     => $event->start_date->format('Y-m-d'),
            'end'       => $endExclusive,
            'allDay'    => true,
            'color'     => $color,
            'textColor' => $textColor,
            'extendedProps' => [
                'country'      => $event->country,
                'countryLabel' => $countryLabel,
                'type'         => $event->type,
                'typeLabel'    => $typeLabel . $zoneLabel,
                'zone'         => $event->zone,
                'source'       => $event->source,
            ],
        ];
    }
}
