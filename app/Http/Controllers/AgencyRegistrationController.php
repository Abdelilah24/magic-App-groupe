<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AgencyStatus;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AgencyRegistrationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Formulaire d'inscription public.
     */
    public function create()
    {
        $agencyStatuses = AgencyStatus::active()->get();
        return view('agencies.register', compact('agencyStatuses'));
    }

    /**
     * Traitement de l'inscription.
     */
    public function store(Request $request)
    {
        // Détecter si le statut sélectionné est "Agence de voyages"
        $selectedStatus  = \App\Models\AgencyStatus::find($request->agency_status_id);
        $isAgenceVoyages = $selectedStatus && $selectedStatus->slug === 'agence-de-voyages';

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:agencies,email',
            'phone'            => 'required|string|max:30',
            'contact_name'     => 'required|string|max:255',
            'agency_status_id' => 'required|exists:agency_statuses,id',
            'address'          => 'required|string|max:500',
            'city'             => 'required|string|max:100',
            'country'          => 'required|string|max:100',
            'website'          => 'nullable|url|max:255',
            'notes'            => 'nullable|string|max:1000',
            'licence_number'   => $isAgenceVoyages ? 'required|string|max:100' : 'nullable|string|max:100',
            'licence_file'     => $isAgenceVoyages ? 'required|file|mimes:pdf,jpg,jpeg,png|max:5120' : 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'name.required'             => 'Le nom de l\'agence est obligatoire.',
            'email.required'            => 'L\'email est obligatoire.',
            'email.unique'              => 'Cette adresse email est déjà enregistrée.',
            'phone.required'            => 'Le téléphone est obligatoire.',
            'contact_name.required'     => 'Le nom du contact est obligatoire.',
            'agency_status_id.required' => 'Veuillez sélectionner un type de client.',
            'agency_status_id.exists'   => 'Statut invalide.',
            'address.required'          => 'L\'adresse est obligatoire.',
            'city.required'             => 'La ville est obligatoire.',
            'country.required'          => 'Le pays est obligatoire.',
            'licence_number.required'   => 'Le numéro de licence est obligatoire pour une agence de voyages.',
            'licence_file.required'     => 'La fiche de licence est obligatoire pour une agence de voyages.',
            'licence_file.mimes'        => 'La fiche de licence doit être un fichier PDF, JPG ou PNG.',
            'licence_file.max'          => 'La fiche de licence ne doit pas dépasser 5 Mo.',
        ]);

        // Upload de la fiche de licence si présente
        if ($request->hasFile('licence_file')) {
            $data['licence_file'] = $request->file('licence_file')->store('licences', 'public');
        }

        $agency = Agency::create(array_merge($data, ['status' => Agency::STATUS_PENDING]));

        // Notifier l'admin
        $this->notificationService->notifyAdminNewAgency($agency);

        // Email de confirmation à l'agence
        $this->notificationService->sendAgencyRegistrationConfirmed($agency);

        return redirect()
            ->route('agency.register.success')
            ->with('agency_name', $agency->name);
    }

    /**
     * Page de succès.
     */
    public function success()
    {
        return view('agencies.register-success');
    }
}
