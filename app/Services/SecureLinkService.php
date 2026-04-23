<?php

namespace App\Services;

use App\Models\SecureLink;
use App\Models\User;
use Illuminate\Support\Str;

class SecureLinkService
{
    /**
     * Génère un nouveau lien sécurisé pour une agence.
     */
    public function generate(array $data, User $creator): SecureLink
    {
        $link = SecureLink::create([
            'token'         => Str::random(64),
            'agency_name'   => $data['agency_name'],
            'agency_email'  => $data['agency_email'],
            'contact_name'  => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'hotel_id'      => $data['hotel_id'] ?? null,
            'created_by'    => $creator->id,
            'expires_at'    => isset($data['expires_in_days'])
                ? now()->addDays((int) $data['expires_in_days'])
                : null,
            'max_uses'      => $data['max_uses'] ?? 1,
            'notes'         => $data['notes'] ?? null,
        ]);

        return $link;
    }

    /**
     * Valide un token et retourne le lien ou null.
     */
    public function validate(string $token): ?SecureLink
    {
        $link = SecureLink::where('token', $token)->first();

        if (! $link || ! $link->isValid()) {
            return null;
        }

        return $link;
    }

    /**
     * Révoque un lien.
     */
    public function revoke(SecureLink $link): void
    {
        $link->update(['is_active' => false]);
    }

    /**
     * Régénère le token d'un lien existant.
     */
    public function regenerate(SecureLink $link): SecureLink
    {
        $link->update(['token' => Str::random(64), 'uses_count' => 0]);
        return $link->fresh();
    }
}
