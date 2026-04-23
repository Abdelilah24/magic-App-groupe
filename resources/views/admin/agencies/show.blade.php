@extends('admin.layouts.app')
@section('title', $agency->name)
@section('page-title', $agency->name)
@section('page-subtitle', 'Agence partenaire')

@section('header-actions')
    <a href="{{ route('admin.agencies.index') }}" class="text-sm text-gray-500 hover:text-gray-700"> Retour</a>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"> {{-- Infos agence --}}
    <div class="lg:col-span-2 space-y-5"> {{--  Mot de passe temporaire (affiché une seule fois après approbation)  --}}
        @if(session('new_password'))
        <div class="bg-emerald-50 border-2 border-emerald-400 rounded-xl p-4"> <p class="text-sm font-bold text-emerald-800 mb-2"> Mot de passe temporaire  à noter maintenant !</p> <div class="flex items-center gap-3"> <code class="flex-1 bg-white border border-emerald-300 rounded-lg px-4 py-2 text-lg font-mono font-bold text-emerald-900 tracking-widest">{{ session('new_password') }}</code> <button onclick="navigator.clipboard.writeText('{{ session('new_password') }}');this.textContent=' Copié !'"
                        class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium shrink-0">Copier</button> </div> <p class="text-xs text-emerald-700 mt-2"> Email : <strong>{{ $agency->email }}</strong> ·
 Portail : <a href="{{ route('agency.login') }}" class="underline" target="_blank">{{ route('agency.login') }}</a> </p> </div> @endif

        {{--  Accès portail + reset mdp  --}}
        @if($agency->isApproved())
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4"> <div class="flex items-center gap-3 mb-3"> <p class="text-sm font-semibold text-amber-900 flex-1">Accès Espace Agence</p> <a href="{{ route('agency.login') }}" target="_blank"
                   class="text-xs text-amber-700 border border-amber-300 px-3 py-1.5 rounded-lg hover:bg-amber-100"> Voir le portail 
                </a> <form action="{{ route('admin.agencies.reset-password', $agency) }}" method="POST"> @csrf
                    <button type="submit"
                            onclick="return confirm('Regénérer le mot de passe de cette agence ?')"
                            class="text-xs text-orange-700 border border-orange-300 px-3 py-1.5 rounded-lg hover:bg-orange-50"> Nouveau mot de passe
                    </button> </form> </div> <p class="text-xs text-amber-700"> URL de connexion : <strong>{{ route('agency.login') }}</strong> · Email : <strong>{{ $agency->email }}</strong> @if($agency->password) · Mot de passe : <em>défini</em> @else · <span class="text-red-600">Mot de passe non défini</span> @endif
            </p> </div> @endif

        {{--  Lien Portail Agence (token)  --}}
        @if($agency->isApproved())
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center gap-3"> <div class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center shrink-0"> <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/> </svg> </div> <div class="flex-1 min-w-0"> <p class="text-xs font-semibold text-amber-900 mb-1">Espace Agence</p> <div class="flex items-center gap-2"> <input id="portal-url" type="text" readonly
                           value="{{ $agency->portal_url }}"
                           class="text-xs text-amber-800 bg-amber-100 border border-amber-300 rounded-lg px-2 py-1 flex-1 min-w-0 truncate"> <button onclick="copyPortalUrl()" class="text-xs bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded-lg font-medium shrink-0"> Copier
                    </button> <a href="{{ $agency->portal_url }}" target="_blank"
                       class="text-xs text-amber-700 hover:text-amber-900 border border-amber-300 px-3 py-1 rounded-lg shrink-0"> Voir 
                    </a> </div> </div> </div> <script> function copyPortalUrl() {
            const el = document.getElementById('portal-url');
            navigator.clipboard.writeText(el.value).then(() => {
                const btn = el.nextElementSibling;
                btn.textContent = ' Copié !';
                btn.classList.replace('bg-amber-500','bg-green-500');
                setTimeout(() => { btn.textContent = 'Copier'; btn.classList.replace('bg-green-500','bg-amber-500'); }, 2000);
            });
        }
        </script> @endif

        {{--  Type de client (statut tarifaire)  --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5"> <div class="flex items-center justify-between mb-3"> <div> <h2 class="text-base font-semibold">Type de client</h2> <p class="text-xs text-gray-400 mt-0.5">Détermine la remise appliquée sur les tarifs</p> </div> @if($agency->agencyStatus)
                <div class="text-right"> <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-amber-100 text-amber-800"> {{ $agency->agencyStatus->name }}
                    </span> @if($agency->agencyStatus->discount_percent > 0)
                    <p class="text-xs text-emerald-600 font-medium mt-1"> Remise : {{ number_format($agency->agencyStatus->discount_percent, 0) }} %
                        &nbsp;·&nbsp; Facteur : × {{ number_format($agency->agencyStatus->price_multiplier, 2) }}
                    </p> @else
                    <p class="text-xs text-gray-400 mt-1">Tarif plein (aucune remise)</p> @endif
                </div> @else
                <span class="text-xs text-red-500">Non défini</span> @endif
            </div> <form action="{{ route('admin.agencies.update-status', $agency) }}" method="POST"
                  class="flex items-end gap-3 mt-3 pt-3 border-t border-gray-100"> @csrf @method('PATCH')
                <div class="flex-1"> <label class="block text-xs font-medium text-gray-600 mb-1">Changer le type de client</label> <select name="agency_status_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> <option value=""> Choisir </option> @foreach($agencyStatuses as $st)
                        <option value="{{ $st->id }}" {{ $agency->agency_status_id == $st->id ? 'selected' : '' }}> {{ $st->name }}
                            @if($st->discount_percent > 0)  {{ number_format($st->discount_percent, 0) }} %@endif
                        </option> @endforeach
                    </select> </div> <button class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium px-4 py-2 rounded-lg shrink-0"> Enregistrer
                </button> </form> </div> <div class="bg-white border border-gray-200 rounded-xl p-6"> <div class="flex items-center justify-between mb-4"> <h2 class="text-base font-semibold">Informations</h2> @php $colors = ['pending' => 'bg-yellow-100 text-yellow-800', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700']; @endphp
                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $colors[$agency->status] ?? '' }}"> {{ $agency->status_label }}
                </span> </div> <div class="grid grid-cols-2 gap-3 text-sm"> <div><span class="text-gray-500">Agence :</span> <strong>{{ $agency->name }}</strong></div> <div><span class="text-gray-500">Email :</span> <a href="mailto:{{ $agency->email }}" class="text-amber-600">{{ $agency->email }}</a></div> <div><span class="text-gray-500">Contact :</span> {{ $agency->contact_name }}</div> <div><span class="text-gray-500">Téléphone :</span> {{ $agency->phone ?? '' }}</div> <div><span class="text-gray-500">Ville :</span> {{ $agency->city ?? '' }}</div> <div><span class="text-gray-500">Pays :</span> {{ $agency->country }}</div> @if($agency->address)
                <div class="col-span-2"><span class="text-gray-500">Adresse :</span> {{ $agency->address }}</div> @endif
                @if($agency->website)
                <div class="col-span-2"><span class="text-gray-500">Site web :</span> <a href="{{ $agency->website }}" target="_blank" class="text-amber-600 hover:underline">{{ $agency->website }}</a></div> @endif
                @if($agency->licence_number)
                <div><span class="text-gray-500">N° licence :</span> <strong class="font-mono text-gray-800">{{ $agency->licence_number }}</strong></div> @endif
                @if($agency->licence_file)
                <div><span class="text-gray-500">Fiche licence :</span> <a href="{{ asset('storage/' . $agency->licence_file) }}" target="_blank"
                       class="inline-flex items-center gap-1 text-amber-600 hover:text-amber-800 font-medium text-sm"> Voir le document
                    </a> </div> @endif
                <div><span class="text-gray-500">Inscrite le :</span> {{ $agency->created_at->format('d/m/Y à H:i') }}</div> @if($agency->approved_at)
                <div><span class="text-gray-500">Approuvée le :</span> {{ $agency->approved_at->format('d/m/Y') }} par {{ $agency->approver?->name }}</div> @endif
            </div> @if($agency->notes)
            <div class="mt-4 p-3 bg-gray-50 rounded-lg"> <p class="text-xs font-medium text-gray-500 mb-1">Message de l'agence :</p> <p class="text-sm text-gray-700">{{ $agency->notes }}</p> </div> @endif
        </div> {{-- Réservations --}}
        @if($agency->reservations->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-xl p-6"> <h2 class="text-base font-semibold mb-4">Réservations</h2> @foreach($agency->reservations->take(10) as $res)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 text-sm"> <div> <p class="font-mono font-medium text-amber-600">{{ $res->reference }}</p> <p class="text-xs text-gray-400">{{ $res->check_in->format('d/m/Y') }}  {{ $res->check_out->format('d/m/Y') }}  {{ $res->hotel->name }}</p> </div> <div class="flex items-center gap-3"> @include('admin.partials.status-badge', ['status' => $res->status, 'label' => $res->status_label])
                    <a href="{{ route('admin.reservations.show', $res) }}" class="text-amber-600 text-xs hover:underline">Voir</a> </div> </div> @endforeach
        </div> @endif


    </div> {{-- Colonne actions --}}
    <div class="space-y-4"> {{-- Approbation / Rejet --}}
        @if($agency->status === 'pending')
        <div class="bg-white border border-gray-200 rounded-xl p-5"> <h3 class="font-semibold text-gray-900 mb-4">Décision</h3> <form action="{{ route('admin.agencies.approve', $agency) }}" method="POST" class="mb-3"> @csrf @method('PATCH')
                <textarea name="admin_notes" rows="2" placeholder="Note interne (optionnel)..."
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mb-2 focus:outline-none focus:ring-2 focus:ring-amber-400"></textarea> <button class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded-lg text-sm"> Approuver l'agence
                </button> </form> <form action="{{ route('admin.agencies.reject', $agency) }}" method="POST"> @csrf @method('PATCH')
                <textarea name="reason" required rows="2" placeholder="Motif du rejet..."
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mb-2 focus:outline-none focus:ring-2 focus:ring-red-300"></textarea> <button class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 rounded-lg text-sm"> Rejeter
                </button> </form> </div> @endif


        {{-- Demande de modification de profil --}}
        @if(! empty($agency->pending_changes))
        <div class="bg-white border-2 border-amber-400 rounded-xl p-5">
            <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse inline-block"></span>
                Demande de modification
            </h3>
            <p class="text-xs text-gray-500 mb-4">L'agence demande à modifier les champs suivants :</p>
            <div class="space-y-2 mb-4">
                @php
                    $labels = [
                        'contact_name' => 'Nom du contact',
                        'phone'        => 'Téléphone',
                        'address'      => 'Adresse',
                        'city'         => 'Ville',
                        'country'      => 'Pays',
                        'website'      => 'Site web',
                    ];
                @endphp
                @foreach($agency->pending_changes as $field => $change)
                <div class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2 text-xs">
                    <p class="font-semibold text-gray-700 mb-1">{{ $labels[$field] ?? $field }}</p>
                    <p class="text-red-500 line-through">{{ $change['old'] ?: '(vide)' }}</p>
                    <p class="text-green-700 font-medium">{{ $change['new'] ?: '(vide)' }}</p>
                </div>
                @endforeach
            </div>
            <form action="{{ route('admin.agencies.approve-profile-change', $agency) }}" method="POST" class="mb-2">
                @csrf
                <button class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded-lg text-sm">
                    ✓ Approuver les modifications
                </button>
            </form>
            <form action="{{ route('admin.agencies.reject-profile-change', $agency) }}" method="POST"
                  onsubmit="return confirm('Rejeter la demande de modification ?')">
                @csrf
                <button class="w-full bg-red-50 hover:bg-red-100 text-red-700 font-medium py-2 rounded-lg text-sm border border-red-200">
                    ✕ Rejeter la demande
                </button>
            </form>
        </div>
        @endif

        {{-- Supprimer --}}
        <form action="{{ route('admin.agencies.destroy', $agency) }}" method="POST"
              onsubmit="return confirm('Supprimer cette agence ?')"> @csrf @method('DELETE')
            <button class="w-full bg-gray-100 hover:bg-red-50 text-red-600 text-sm font-medium py-2 rounded-xl border border-red-200"> Supprimer l'agence
            </button> </form> </div>
</div>
@endsection
