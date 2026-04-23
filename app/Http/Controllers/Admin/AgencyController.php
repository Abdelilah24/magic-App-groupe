<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyStatus;
use App\Services\NotificationService;
use App\Services\SecureLinkService;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function __construct(
        private readonly SecureLinkService   $secureLinkService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $query = Agency::withCount(['reservations'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        $agencies = $query->paginate(20)->withQueryString();

        $counts = [
            'all'      => Agency::count(),
            'pending'  => Agency::pending()->count(),
            'approved' => Agency::approved()->count(),
        ];

        return view('admin.agencies.index', compact('agencies', 'counts'));
    }

    public function show(Agency $agency)
    {
        $agency->load(['approver', 'reservations.hotel', 'agencyStatus']);
        $agencyStatuses = AgencyStatus::active()->get();
        return view('admin.agencies.show', compact('agency', 'agencyStatuses'));
    }

    /**
     * Modifier le type/statut tarifaire de l'agence depuis l'admin.
     */
    public function updateAgencyStatus(Request $request, Agency $agency)
    {
        $request->validate([
            'agency_status_id' => 'required|exists:agency_statuses,id',
        ]);

        $agency->update(['agency_status_id' => $request->agency_status_id]);

        $statusName = AgencyStatus::find($request->agency_status_id)?->name ?? '';
        return redirect()
            ->route('admin.agencies.show', $agency)
            ->with('success', "Type client mis à jour : « {$statusName} ».");
    }

    /**
     * Approuver une agence.
     */
    public function approve(Request $request, Agency $agency)
    {
        // Générer le mot de passe temporaire si l'agence n'en a pas
        $plainPassword = null;
        if (! $agency->password) {
            $plainPassword = $agency->generatePassword();
        }

        $agency->update([
            'status'      => Agency::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'admin_notes' => $request->input('admin_notes'),
        ]);

        $this->notificationService->sendAgencyApproved($agency, $plainPassword, null);

        $msg = "Agence « {$agency->name} » approuvée.";
        if ($plainPassword) {
            $msg .= " Mot de passe généré : {$plainPassword} — notez-le maintenant.";
        }

        return redirect()
            ->route('admin.agencies.show', $agency)
            ->with('success', $msg)
            ->with('new_password', $plainPassword);
    }

    /**
     * Regénérer le mot de passe d'une agence.
     */
    public function resetPassword(Agency $agency)
    {
        $plain = $agency->generatePassword();

        return redirect()
            ->route('admin.agencies.show', $agency)
            ->with('success', "Nouveau mot de passe : {$plain} — notez-le maintenant.")
            ->with('new_password', $plain);
    }

    /**
     * Regénérer le lien portail d'une agence.
     */
    public function regeneratePortalLink(Agency $agency)
    {
        $agency->generateAccessToken();

        return redirect()
            ->route('admin.agencies.show', $agency)
            ->with('success', 'Nouveau lien portail généré.');
    }

    /**
     * Rejeter une agence.
     */
    public function reject(Request $request, Agency $agency)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $agency->update([
            'status'      => Agency::STATUS_REJECTED,
            'admin_notes' => $request->reason,
        ]);

        $this->notificationService->sendAgencyRejected($agency, $request->reason);

        return redirect()
            ->route('admin.agencies.show', $agency)
            ->with('success', "Agence rejetée.");
    }

    /**
     * Créer directement un lien sécurisé pour l'agence.
     */
    public function createLink(Request $request, Agency $agency)
    {
        $data = $request->validate([
            'hotel_id'        => 'nullable|exists:hotels,id',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'max_uses'        => 'nullable|integer|min:1',
            'send_email'      => 'boolean',
        ]);

        $link = $this->secureLinkService->generate([
            'agency_name'     => $agency->name,
            'agency_email'    => $agency->email,
            'contact_name'    => $agency->contact_name,
            'contact_phone'   => $agency->phone,
            'hotel_id'        => $data['hotel_id'] ?? null,
            'expires_in_days' => $data['expires_in_days'] ?? 30,
            'max_uses'        => $data['max_uses'] ?? 3,
            'agency_id'       => $agency->id,
        ], $request->user());

        // Rattacher l'agence au lien
        $link->update(['agency_id' => $agency->id]);

        if ($request->boolean('send_email')) {
            $this->notificationService->sendInvitation($link);
        }

        return redirect()
            ->route('admin.secure-links.show', $link)
            ->with('success', 'Lien sécurisé créé' . ($request->boolean('send_email') ? ' et envoyé.' : '.'));
    }

    /**
     * Approuver la demande de modification de profil.
     */
    public function approveProfileChange(Agency $agency)
    {
        if (empty($agency->pending_changes)) {
            return back()->with('error', 'Aucune modification en attente pour cette agence.');
        }

        $updates = collect($agency->pending_changes)->mapWithKeys(
            fn ($v, $k) => [$k => $v['new']]
        )->all();

        $agency->update(array_merge($updates, ['pending_changes' => null]));

        return redirect()
            ->route('admin.agencies.show', $agency)
            ->with('success', 'Modifications du profil approuvées et appliquées.');
    }

    /**
     * Rejeter la demande de modification de profil.
     */
    public function rejectProfileChange(Agency $agency)
    {
        $agency->update(['pending_changes' => null]);

        return redirect()
            ->route('admin.agencies.show', $agency)
            ->with('success', 'Demande de modification rejetée.');
    }

    /**
     * Suppression.
     */
    public function destroy(Agency $agency)
    {
        $agency->delete();
        return redirect()->route('admin.agencies.index')->with('success', 'Agence supprimée.');
    }
}
