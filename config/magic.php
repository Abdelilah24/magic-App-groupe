<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nom du groupe hôtelier
    |--------------------------------------------------------------------------
    */
    'name' => env('MAGIC_HOTEL_NAME', 'Magic Hotels'),

    /*
    |--------------------------------------------------------------------------
    | Email de notification admin (nouvelles réservations, modifications, etc.)
    |--------------------------------------------------------------------------
    */
    'admin_notification_email' => env('MAGIC_ADMIN_EMAIL', 'admin@magichotels.ma'),

    /*
    |--------------------------------------------------------------------------
    | Email de contact affiché aux clients
    |--------------------------------------------------------------------------
    */
    'contact_email' => env('MAGIC_CONTACT_EMAIL', 'reservations@magichotels.ma'),

    /*
    |--------------------------------------------------------------------------
    | Coordonnées bancaires pour le virement
    |--------------------------------------------------------------------------
    */
    'bank_details' => [
        'beneficiary' => env('MAGIC_BANK_BENEFICIARY', 'Magic Hotels SARL'),
        'bank'        => env('MAGIC_BANK_NAME', 'CIH Bank'),
        'rib'         => env('MAGIC_BANK_RIB', '230 780 1234567890123456 78'),
        'swift'       => env('MAGIC_BANK_SWIFT', 'CIHMMAMC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Durée de validité du token de paiement (en jours)
    |--------------------------------------------------------------------------
    */
    'payment_token_ttl_days' => (int) env('MAGIC_PAYMENT_TOKEN_TTL', 7),

    /*
    |--------------------------------------------------------------------------
    | Commercial — signature email devis
    |--------------------------------------------------------------------------
    */
    'commercial_nom'   => env('MAGIC_COMMERCIAL_NOM',   'L\'équipe commerciale Magic Hotels'),
    'commercial_titre' => env('MAGIC_COMMERCIAL_TITRE', 'Direction des Ventes & Marketing'),
    'commercial_tel'   => env('MAGIC_COMMERCIAL_TEL',   ''),
    'site_web'         => env('MAGIC_SITE_WEB',         'www.magichotels.ma'),

    /*
    |--------------------------------------------------------------------------
    | Délai (jours) pour la date limite du premier acompte (50%) dans le devis
    |--------------------------------------------------------------------------
    */
    'quote_deposit_days' => (int) env('MAGIC_QUOTE_DEPOSIT_DAYS', 7),
];
