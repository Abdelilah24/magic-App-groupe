# ✦ Magic Hotels — Plateforme de Réservations Groupes

Application Laravel complète pour la gestion des réservations de groupes envoyées par des agences de voyage.

---

## 🗺 Architecture

```
magic-hotels/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # DashboardController, HotelController,
│   │   │   │                     RoomTypeController, RoomPriceController,
│   │   │   │                     ReservationController, SecureLinkController
│   │   │   └── Client/         # ReservationController, PaymentController
│   │   └── Middleware/
│   │       └── AdminMiddleware.php
│   ├── Mail/                   # 9 classes Mailable (ShouldQueue)
│   ├── Models/                 # Hotel, RoomType, RoomPrice, Reservation,
│   │                             ReservationRoom, Payment, StatusHistory,
│   │                             SecureLink, User
│   ├── Policies/
│   │   └── ReservationPolicy.php
│   └── Services/
│       ├── PricingService.php      # Calcul nuit par nuit (logique Booking.com)
│       ├── ReservationService.php  # Workflow complet des réservations
│       ├── SecureLinkService.php   # Génération/validation des tokens
│       └── NotificationService.php # Envoi centralisé de tous les emails
│
├── database/
│   ├── migrations/             # 8 migrations ordonnées
│   └── seeders/
│       ├── AdminUserSeeder.php
│       └── HotelSeeder.php     # 2 hôtels + types chambres + tarifs réalistes
│
├── resources/views/
│   ├── admin/                  # Dashboard, hôtels, room-types, prices,
│   │   layouts/                  réservations, secure-links
│   ├── client/                 # Formulaire, suivi, page paiement
│   ├── emails/                 # 9 templates email (layout partagé)
│   └── layouts/                # Layout Blade client
│
├── routes/web.php              # Routes admin + client + AJAX
└── config/magic.php            # Configuration du groupe hôtelier
```

---

## ⚙️ Stack

| Composant | Technologie |
|---|---|
| Framework | Laravel 11 / PHP 8.3+ |
| Base de données | MySQL 8+ |
| Frontend | Blade + Tailwind CSS (CDN) + Alpine.js |
| Emails | Laravel Mail + Queue (ShouldQueue) |
| Auth admin | Laravel Breeze / Fortify |
| Sécurité | Laravel Policies, tokens sécurisés, SoftDeletes |

---

## 🚀 Installation

### 1. Cloner / placer le projet

```bash
composer create-project laravel/laravel magic-hotels
# Remplacer les fichiers par ceux du livrable
```

### 2. Installer les dépendances

```bash
composer install
npm install && npm run dev   # optionnel si vous utilisez le CDN Tailwind
```

### 3. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Éditer `.env` :

```env
APP_NAME="Magic Hotels"
APP_URL=http://localhost:8000

DB_DATABASE=magic_hotels
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@magichotels.ma
MAIL_FROM_NAME="Magic Hotels"

QUEUE_CONNECTION=database   # ou redis en production

# Config Magic Hotels
MAGIC_ADMIN_EMAIL=admin@magichotels.ma
MAGIC_CONTACT_EMAIL=reservations@magichotels.ma
MAGIC_BANK_BENEFICIARY="Magic Hotels SARL"
MAGIC_BANK_NAME="CIH Bank"
MAGIC_BANK_RIB="230 780 1234567890123456 78"
```

### 4. Créer la base de données

```bash
mysql -u root -e "CREATE DATABASE magic_hotels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Migrations & Seeders

```bash
php artisan migrate
php artisan db:seed
```

Comptes créés :
- `admin@magichotels.ma` / `password` (Admin)
- `staff@magichotels.ma` / `password` (Staff)

### 6. Auth (Laravel Breeze)

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
php artisan migrate
npm run dev
```

### 7. Queue Worker

```bash
php artisan queue:table && php artisan migrate
php artisan queue:work
```

En production, utiliser Supervisor :

```ini
[program:magic-queue]
command=php /var/www/magic-hotels/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
```

### 8. Enregistrer le Middleware

Dans `bootstrap/app.php` (Laravel 11) :

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
    ]);
})
```

### 9. Lancer l'application

```bash
php artisan serve
```

→ http://localhost:8000/admin

---

## 🔄 Workflow de réservation

```
[Admin génère lien] ──email──> [Agence reçoit lien]
                                      │
                               [Remplit formulaire]
                                      │
                                 status: pending
                                      │
              ┌───────────────────────┤
              │                       │
           REFUS                    ACCEPTER
              │                       │
         status: refused      calcul prix automatique
         email envoyé         status: waiting_payment
                              email avec lien paiement
                                      │
                              [Client paie virement]
                                      │
                          [Admin marque comme payé]
                                      │
                              status: confirmed
                              email confirmation
                                      │
                     ┌────────────────┤
                     │                │
             MODIFICATION         ANNULATION
                     │                │
            status: modification_pending
                     │
            [Admin accepte/refuse]
                     │
            recalcul prix + nouveau paiement
```

---

## 💰 Calcul des prix (PricingService)

Le `PricingService::calculate()` parcourt **chaque nuit** individuellement :

```php
// Pour une réservation 15/03 → 20/03 (5 nuits) :
// Nuit 15/03 : tarif "Mars-Avril" = 1200 MAD × 3 chambres = 3600 MAD
// Nuit 16/03 : tarif "Mars-Avril" = 1200 MAD × 3 chambres = 3600 MAD
// ... etc.
// TOTAL = 18 000 MAD
```

En cas de **chevauchement de périodes**, le tarif avec la `date_from` la plus récente est appliqué.

---

## 🔗 URLs principales

| URL | Description |
|---|---|
| `/admin` | Dashboard admin |
| `/admin/reservations` | Liste des réservations |
| `/admin/secure-links/create` | Créer un lien agence |
| `/r/{token}` | Formulaire client (lien sécurisé) |
| `/r/{token}/reservations/{id}` | Suivi réservation client |
| `/pay/{paymentToken}` | Page de paiement |

---

## 📧 Emails automatiques

| Trigger | Destinataire | Classe |
|---|---|---|
| Lien créé + envoi | Agence | `InvitationMail` |
| Demande soumise | Client | `ClientReservationReceivedMail` |
| Demande soumise | Admin | `AdminNewReservationMail` |
| Réservation acceptée | Client | `PaymentRequestMail` |
| Réservation refusée | Client | `ReservationRefusedMail` |
| Paiement reçu | Client | `PaymentConfirmedMail` |
| Modification demandée | Admin | `AdminModificationRequestMail` |
| Modification acceptée | Client | `ModificationAcceptedMail` |
| Modification refusée | Client | `ModificationRefusedMail` |

---

## 🔐 Sécurité

- **Tokens sécurisés** : 64 caractères aléatoires (`Str::random(64)`)
- **Expiration configurable** des liens
- **Policies Laravel** pour les actions admin sensibles
- **SoftDeletes** sur Hotels, RoomTypes, Reservations
- **Validation stricte** sur tous les formulaires
- **CSRF** sur toutes les mutations
- Middleware `admin` sur toutes les routes `/admin`

---

## 🧩 Ajouter une intégration de paiement

Pour intégrer un vrai système de paiement (Stripe, CMI, PayDunya...) :

1. Modifier `Client\PaymentController` pour rediriger vers la gateway
2. Créer un webhook controller pour recevoir la confirmation
3. Appeler `$reservationService->markAsPaid(...)` dans le webhook
4. Les emails de confirmation sont déjà en place

---

## 📞 Support

Pour toute question : `reservations@magichotels.ma`
# sdfd
# magic-App-groupe
