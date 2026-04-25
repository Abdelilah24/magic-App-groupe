<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use LogsActivity;

    protected string $activitySection = 'Calendrier';
    protected $fillable = [
        'country', 'type', 'name',
        'start_date', 'end_date', 'year',
        'source', 'zone',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'year'       => 'integer',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeHolidays($query)
    {
        return $query->where('type', 'holiday');
    }

    public function scopeVacations($query)
    {
        return $query->where('type', 'school_vacation');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Couleur FullCalendar selon pays + type.
     *
     * Jours fériés  : FR=rouge · MA=jaune · GB=orange
     * Vacances      : FR=bleu  · MA=violet · GB=vert
     */
    public function getFullCalendarColor(): string
    {
        return match(true) {
            $this->country === 'FR' && $this->type === 'holiday'         => '#dc2626', // red-600
            $this->country === 'MA' && $this->type === 'holiday'         => '#ffff00', // jaune vif
            $this->country === 'GB' && $this->type === 'holiday'         => '#ea580c', // orange-600
            $this->country === 'FR' && $this->type === 'school_vacation' => '#2563eb', // blue-600
            $this->country === 'MA' && $this->type === 'school_vacation' => '#7c3aed', // violet-600
            $this->country === 'GB' && $this->type === 'school_vacation' => '#16a34a', // green-600
            default                                                       => '#6b7280', // gray-500
        };
    }

    /**
     * Retourne true si la couleur de fond est assez claire pour nécessiter du texte foncé.
     */
    public function needsDarkText(): bool
    {
        $color = ltrim($this->getFullCalendarColor(), '#');
        [$r, $g, $b] = [hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2))];
        // Luminosité relative (formule W3C)
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luminance > 0.55;
    }

    /**
     * Retourne true si l'événement est dans la zone scolaire donnée
     * (ou si la zone n'est pas renseignée — cas Maroc / jours fériés).
     */
    public function matchesZone(?string $zone): bool
    {
        if ($this->zone === null) return true;
        if ($zone === null)       return true;

        $dbZone = strtolower($this->zone); // ex: "zone b", "zone a et zone b", "toutes zones"

        // Mots-clés qui indiquent toutes les zones
        if (str_contains($dbZone, 'toutes') || str_contains($dbZone, 'métropole')) {
            return true;
        }

        // Cherche la lettre de zone précédée de "zone " ou en début/fin de chaîne,
        // pour éviter de confondre "Zone A" et "Zone B" avec la simple lettre "a"/"b"
        $letter = strtolower($zone); // "a", "b" ou "c"
        return (bool) preg_match('/\bzone\s+' . preg_quote($letter, '/') . '\b/i', $dbZone)
            || (bool) preg_match('/\bzone\s+[a-c,\s]*' . preg_quote($letter, '/') . '\b/i', $dbZone);
    }
}
