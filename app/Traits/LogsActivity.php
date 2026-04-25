<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function (self $model) use ($event) {
                ActivityLog::write($event, $model);
            });
        }
    }

    /**
     * Libellé lisible de l'enregistrement (nom, référence, titre…).
     * Les modèles peuvent surcharger cette méthode.
     */
    public function getActivityLabel(): string
    {
        return $this->name
            ?? $this->reference
            ?? $this->title
            ?? $this->subject
            ?? $this->label
            ?? $this->email
            ?? "#{$this->getKey()}";
    }

    /**
     * Nom de la section affichée dans le journal.
     * Défini par la propriété $activitySection sur chaque modèle.
     */
    public function getActivitySection(): string
    {
        return property_exists($this, 'activitySection')
            ? $this->activitySection
            : class_basename(static::class);
    }
}
