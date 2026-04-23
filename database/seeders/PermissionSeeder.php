<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Toutes les permissions de l'application, groupées par catégorie.
     */
    private function permissions(): array
    {
        return [
            // ── Réservations ────────────────────────────────────────────
            ['group' => 'Réservations', 'name' => 'reservations.view',              'label' => 'Voir les réservations',                     'sort_order' => 10],
            ['group' => 'Réservations', 'name' => 'reservations.edit',              'label' => 'Modifier une réservation',                  'sort_order' => 11],
            ['group' => 'Réservations', 'name' => 'reservations.accept',            'label' => 'Accepter une réservation',                  'sort_order' => 12],
            ['group' => 'Réservations', 'name' => 'reservations.refuse',            'label' => 'Refuser une réservation',                   'sort_order' => 13],
            ['group' => 'Réservations', 'name' => 'reservations.handle_modification','label' => 'Gérer les demandes de modification',       'sort_order' => 14],
            ['group' => 'Réservations', 'name' => 'reservations.proforma',          'label' => 'Générer / envoyer la proforma',             'sort_order' => 15],
            ['group' => 'Réservations', 'name' => 'reservations.extras',            'label' => 'Gérer les services extras d\'une réservation','sort_order' => 16],
            ['group' => 'Réservations', 'name' => 'reservations.payments',          'label' => 'Gérer les paiements et l\'échéancier',      'sort_order' => 17],

            // ── Agences ─────────────────────────────────────────────────
            ['group' => 'Agences', 'name' => 'agencies.view',    'label' => 'Voir les agences',                    'sort_order' => 20],
            ['group' => 'Agences', 'name' => 'agencies.manage',  'label' => 'Créer / modifier les agences',        'sort_order' => 21],
            ['group' => 'Agences', 'name' => 'agencies.approve', 'label' => 'Approuver ou refuser une agence',     'sort_order' => 22],

            // ── Hôtels & Tarification ────────────────────────────────────
            ['group' => 'Hôtels & Tarification', 'name' => 'hotels.view',          'label' => 'Voir les hôtels',                           'sort_order' => 30],
            ['group' => 'Hôtels & Tarification', 'name' => 'hotels.manage',        'label' => 'Créer / modifier les hôtels',               'sort_order' => 31],
            ['group' => 'Hôtels & Tarification', 'name' => 'pricing.manage',       'label' => 'Gérer les tarifs et calendriers',           'sort_order' => 32],
            ['group' => 'Hôtels & Tarification', 'name' => 'supplements.manage',   'label' => 'Gérer les suppléments / événements',        'sort_order' => 33],
            ['group' => 'Hôtels & Tarification', 'name' => 'extra_services.manage','label' => 'Gérer le catalogue services extras',        'sort_order' => 34],
            ['group' => 'Hôtels & Tarification', 'name' => 'occupancy.manage',     'label' => 'Gérer les configurations d\'occupation',    'sort_order' => 35],

            // ── Configuration ────────────────────────────────────────────
            ['group' => 'Configuration', 'name' => 'templates.manage',       'label' => 'Gérer les templates email / PDF',          'sort_order' => 40],
            ['group' => 'Configuration', 'name' => 'refusal_reasons.manage', 'label' => 'Gérer les motifs de refus',                'sort_order' => 41],
            ['group' => 'Configuration', 'name' => 'calendar.manage',        'label' => 'Gérer le calendrier vacances / jours fériés','sort_order' => 42],

            // ── Administration ───────────────────────────────────────────
            ['group' => 'Administration', 'name' => 'users.manage', 'label' => 'Gérer les utilisateurs (admin / staff)', 'sort_order' => 50],
            ['group' => 'Administration', 'name' => 'roles.manage', 'label' => 'Gérer les rôles et permissions',         'sort_order' => 51],
        ];
    }

    /**
     * Permissions accordées à chaque rôle par défaut.
     * Le super_admin possède TOUT automatiquement (pas besoin de liste).
     */
    private function defaultRolePermissions(): array
    {
        return [
            User::ROLE_ADMIN => [
                'reservations.view',
                'reservations.edit',
                'reservations.accept',
                'reservations.refuse',
                'reservations.handle_modification',
                'reservations.proforma',
                'reservations.extras',
                'reservations.payments',
                'agencies.view',
                'agencies.manage',
                'agencies.approve',
                'hotels.view',
                'hotels.manage',
                'pricing.manage',
                'supplements.manage',
                'extra_services.manage',
                'occupancy.manage',
                'templates.manage',
                'refusal_reasons.manage',
                'calendar.manage',
                'users.manage',
                // 'roles.manage' → réservé au super_admin uniquement
            ],
            User::ROLE_STAFF => [
                'reservations.view',
                'reservations.accept',
                'reservations.refuse',
                'reservations.handle_modification',
                'reservations.proforma',
                'reservations.extras',
                'reservations.payments',
                'agencies.view',
                'hotels.view',
            ],
        ];
    }

    public function run(): void
    {
        // ── 0. Insérer les rôles système ─────────────────────────────────
        $systemRoles = [
            ['name' => User::ROLE_SUPER_ADMIN, 'label' => 'Super Administrateur', 'description' => 'Accès total — ne peut pas être restreint.', 'is_system' => true, 'sort_order' => 0],
            ['name' => User::ROLE_ADMIN,       'label' => 'Administrateur',        'description' => 'Gestion complète de l\'application.',         'is_system' => true, 'sort_order' => 1],
            ['name' => User::ROLE_STAFF,       'label' => 'Staff',                 'description' => 'Accès opérationnel aux réservations.',          'is_system' => true, 'sort_order' => 2],
        ];
        foreach ($systemRoles as $r) {
            Role::updateOrCreate(['name' => $r['name']], $r);
        }

        // ── 1. Insérer / mettre à jour les permissions ───────────────────
        foreach ($this->permissions() as $perm) {
            Permission::updateOrCreate(
                ['name' => $perm['name']],
                [
                    'label'       => $perm['label'],
                    'group'       => $perm['group'],
                    'sort_order'  => $perm['sort_order'],
                ]
            );
        }

        $permissionIds = Permission::pluck('id', 'name');

        // ── 2. Assigner les permissions par défaut à chaque rôle ────────
        foreach ($this->defaultRolePermissions() as $role => $permNames) {
            // Supprimer les anciens enregistrements pour ce rôle
            DB::table('role_permissions')->where('role', $role)->delete();

            foreach ($permNames as $permName) {
                if (! isset($permissionIds[$permName])) {
                    continue;
                }
                DB::table('role_permissions')->insertOrIgnore([
                    'role'          => $role,
                    'permission_id' => $permissionIds[$permName],
                ]);
            }

            // Invalider le cache
            Permission::clearCache($role);
        }

        // ── 3. Mettre à jour les admins existants en super_admin ─────────
        // Le premier utilisateur de type 'admin' devient super_admin.
        // Les autres restent 'admin' (permissions configurables).
        \App\Models\User::where('role', User::ROLE_ADMIN)
            ->orderBy('id')
            ->first()
            ?->update(['role' => User::ROLE_SUPER_ADMIN]);

        $this->command->info('✅ Permissions créées et assignées par défaut.');
        $this->command->info('   Le premier utilisateur admin a été promu super_admin.');
    }
}
