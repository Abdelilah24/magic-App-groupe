<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'event',
        'section',
        'description',
        'properties',
        'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helpers statiques ────────────────────────────────────────────────────

    /**
     * Enregistre un événement de modèle (created / updated / deleted).
     */
    public static function write(string $event, Model $model): void
    {
        // Ne pas logger pendant les seeds / migrations console
        if (app()->runningInConsole()) return;

        $properties = null;

        if ($event === 'updated') {
            $dirty = $model->getDirty();
            unset($dirty['updated_at'], $dirty['is_read']);
            if ($dirty) {
                // Exclure les champs sensibles
                $exclude = ['password', 'remember_token', 'two_factor_secret',
                            'two_factor_recovery_codes', 'refresh_token', 'access_token'];
                foreach ($exclude as $k) unset($dirty[$k]);

                if ($dirty) {
                    $properties = [
                        'changed' => array_keys($dirty),
                        'old'     => collect($dirty)->mapWithKeys(
                            fn ($v, $k) => [$k => $model->getOriginal($k)]
                        )->toArray(),
                        'new'     => $dirty,
                    ];
                }
            }
        }

        static::create([
            'user_id'      => auth()->id(),
            'subject_type' => get_class($model),
            'subject_id'   => $model->getKey(),
            'event'        => $event,
            'section'      => method_exists($model, 'getActivitySection')
                                ? $model->getActivitySection()
                                : class_basename($model),
            'description'  => method_exists($model, 'getActivityLabel')
                                ? $model->getActivityLabel()
                                : "#{$model->getKey()}",
            'properties'   => $properties,
            'ip_address'   => request()->ip(),
        ]);
    }

    /**
     * Enregistre un événement personnalisé (sans modèle Eloquent).
     */
    public static function record(
        string  $event,
        string  $section,
        string  $description,
        array   $properties = [],
    ): void {
        if (app()->runningInConsole()) return;

        static::create([
            'user_id'     => auth()->id(),
            'event'       => $event,
            'section'     => $section,
            'description' => $description,
            'properties'  => $properties ?: null,
            'ip_address'  => request()->ip(),
        ]);
    }

    // ─── Accesseurs ───────────────────────────────────────────────────────────

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            default   => ucfirst($this->event),
        };
    }

    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            default   => 'gray',
        };
    }
}
