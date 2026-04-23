@extends('layouts.client')
@section('title', 'Inscription agence  Magic Hotels')

@section('content')
<div class="max-w-2xl mx-auto"> <div class="text-center mb-8"> <p class="text-5xl mb-3"></p> <h1 class="text-2xl font-bold text-gray-900">Inscription agence partenaire</h1> <p class="text-gray-500 mt-2">Rejoignez le réseau Magic Hotels et accédez à notre portail de réservation groupes.</p> </div> <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm"> <form action="{{ route('agency.register.store') }}" method="POST" enctype="multipart/form-data"
              x-data="{
                  selectedSlug: '{{ old('agency_status_id') ? \App\Models\AgencyStatus::find(old('agency_status_id'))?->slug : '' }}',
                  get isAgenceVoyages() { return this.selectedSlug === 'agence-de-voyages'; }
              }"> @csrf

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6"> <p class="text-sm font-medium text-red-800 mb-2">Veuillez corriger les erreurs suivantes :</p> <ul class="text-sm text-red-700 space-y-1 list-disc list-inside"> @foreach($errors->all() as $error)
                    <li>{{ $error }}</li> @endforeach
                </ul> </div> @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5"> <div class="col-span-2"> <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100"> Informations de l'agence
                    </h2> </div> <div class="col-span-2"> <label class="block text-sm font-medium text-gray-700 mb-1">Type de client *</label> <select name="agency_status_id" required
                        @change="selectedSlug = $event.target.selectedOptions[0]?.dataset.slug ?? ''"
                        class="w-full border @error('agency_status_id') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> <option value=""> Sélectionnez votre statut </option> @foreach($agencyStatuses as $st)
                        <option value="{{ $st->id }}" data-slug="{{ $st->slug }}"
                            {{ old('agency_status_id') == $st->id ? 'selected' : '' }}> {{ $st->name }}
                        </option> @endforeach
                    </select> @error('agency_status_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div> {{-- Champs licence  visibles uniquement pour "Agence de voyages" --}}
                <template x-if="isAgenceVoyages"> <div class="col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-5"> <div class="col-span-2"> <div class="flex items-center gap-2 mb-3 pb-2 border-b border-amber-100"> <span class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Licence agence de voyages</span> <span class="text-xs bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full font-medium">Obligatoire</span> </div> </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de licence *</label> <input type="text" name="licence_number"
                                value="{{ old('licence_number') }}"
                                placeholder="ex: AVT-2024-XXXXXX"
                                class="w-full border @error('licence_number') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"> @error('licence_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Fiche de licence *</label> <input type="file" name="licence_file" accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full border @error('licence_file') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2 text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 focus:outline-none"> <p class="text-xs text-gray-400 mt-1">PDF, JPG ou PNG  max 5 Mo</p> @error('licence_file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div> </div> </template> <div class="col-span-2"> <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'agence / Raison sociale *</label> <input type="text" name="name" required value="{{ old('name') }}"
                        class="w-full border @error('name') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                        placeholder="Agence Voyage Prestige"> @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Email professionnel *</label> <input type="email" name="email" required value="{{ old('email') }}"
                        class="w-full border @error('email') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                        placeholder="contact@agence.ma"> @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div> <div> <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone *</label> <input type="text" name="phone" required value="{{ old('phone') }}"
                        class="w-full border @error('phone') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                        placeholder="+212 6 00 00 00 00"> @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div> <div class="col-span-2 mt-2"> <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100"> Responsable de compte
                    </h2> </div> <div class="col-span-2"> <label class="block text-sm font-medium text-gray-700 mb-1">Nom du responsable *</label> <input type="text" name="contact_name" required value="{{ old('contact_name') }}"
                        class="w-full border @error('contact_name') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                        placeholder="Prénom Nom"> @error('contact_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div> <div class="col-span-2 mt-2"> <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100"> Localisation
                    </h2> </div>

                {{-- Adresse --}}
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse *</label>
                    <input type="text" name="address" required value="{{ old('address') }}"
                           class="w-full border @error('address') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                           placeholder="N° et nom de la rue">
                    @error('address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Ville --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ville *</label>
                    <input type="text" name="city" required value="{{ old('city') }}"
                           class="w-full border @error('city') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                           placeholder="Casablanca">
                    @error('city')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Pays (select) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pays *</label>
                    <select name="country" required
                            class="w-full border @error('country') border-red-300 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none bg-white">
                        <option value="">Sélectionnez un pays</option>
                        @php
                            $countries = [
                                'Maroc'               => 'Maroc',
                                'Algérie'             => 'Algérie',
                                'Tunisie'             => 'Tunisie',
                                'Mauritanie'          => 'Mauritanie',
                                'Libye'               => 'Libye',
                                'Égypte'              => 'Égypte',
                                'Sénégal'             => 'Sénégal',
                                "Côte d'Ivoire"       => "Côte d'Ivoire",
                                'Mali'                => 'Mali',
                                'France'              => 'France',
                                'Espagne'             => 'Espagne',
                                'Italie'              => 'Italie',
                                'Portugal'            => 'Portugal',
                                'Belgique'            => 'Belgique',
                                'Suisse'              => 'Suisse',
                                'Allemagne'           => 'Allemagne',
                                'Pays-Bas'            => 'Pays-Bas',
                                'Royaume-Uni'         => 'Royaume-Uni',
                                'Arabie Saoudite'     => 'Arabie Saoudite',
                                'Émirats Arabes Unis' => 'Émirats Arabes Unis',
                                'Qatar'               => 'Qatar',
                                'Koweït'              => 'Koweït',
                                'Canada'              => 'Canada',
                                'États-Unis'          => 'États-Unis',
                                'Autre'               => 'Autre',
                            ];
                        @endphp
                        @foreach($countries as $value => $label)
                            <option value="{{ $value }}" {{ old('country', 'Maroc') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('country')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div> <div class="col-span-2"> <label class="block text-sm font-medium text-gray-700 mb-1">Site web</label> <input type="url" name="website" value="{{ old('website') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                        placeholder="https://votre-agence.ma"> </div> </div> <div class="mt-6 pt-5 border-t border-gray-100"> <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 rounded-xl text-sm transition shadow-sm"> Envoyer ma demande d'inscription
                </button> <p class="text-center text-xs text-gray-400 mt-3"> Votre demande sera examinée par notre équipe sous 24h.
                </p> </div> </form> </div>
</div>
@endsection
