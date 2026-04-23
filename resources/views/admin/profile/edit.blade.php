@extends('admin.layouts.app')
@section('title', 'Mon profil')
@section('page-title', 'Mon profil')
@section('page-subtitle', 'Gérer mon mot de passe, l\'e-mail administratif et le logo')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- ══════════════════════════════════════════════════════════
         SECTION 1 : Mot de passe
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900">Mot de passe</h2>
                <p class="text-xs text-gray-400 mt-0.5">Modifier le mot de passe du compte administrateur</p>
            </div>
        </div>

        @if(session('success_password'))
        <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success_password') }}
        </div>
        @endif

        <form action="{{ route('admin.profile.password') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe actuel</label>
                <input type="password" name="current_password" autocomplete="current-password"
                       class="w-full border @error('current_password') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 transition">
                @error('current_password')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nouveau mot de passe</label>
                    <input type="password" name="password" autocomplete="new-password"
                           class="w-full border @error('password') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 transition">
                    @error('password')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-400 mt-1">8 caractères minimum, lettres et chiffres.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
            </div>

            <div class="flex justify-end pt-1">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Mettre à jour le mot de passe
                </button>
            </div>
        </form>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         SECTION 2 : E-mail du compte admin (login)
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900">E-mail du compte</h2>
                <p class="text-xs text-gray-400 mt-0.5">Adresse utilisée pour la connexion — actuellement <strong class="text-gray-600">{{ auth()->user()->email }}</strong></p>
            </div>
        </div>

        @if(session('success_email'))
        <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success_email') }}
        </div>
        @endif

        <form action="{{ route('admin.profile.email') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nouvelle adresse e-mail</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                    </span>
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                           placeholder="nouvelle@adresse.com"
                           class="w-full border @error('email') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition">
                </div>
                @error('email')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe actuel <span class="text-gray-400 font-normal">(confirmation requise)</span></label>
                <input type="password" name="current_password" autocomplete="current-password"
                       class="w-full border @error('current_password') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition">
                @error('current_password')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end pt-1">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Mettre à jour l'e-mail
                </button>
            </div>
        </form>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         SECTION 3 : E-mail administratif (notifications)
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900">E-mail administratif</h2>
                <p class="text-xs text-gray-400 mt-0.5">Adresse destinataire des notifications (nouvelles réservations, alertes…)</p>
            </div>
        </div>

        @if(session('success_settings'))
        <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success_settings') }}
        </div>
        @endif

        <form action="{{ route('admin.profile.settings') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Adresse e-mail</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                    </span>
                    <input type="email" name="admin_email" value="{{ old('admin_email', $adminEmail) }}"
                           placeholder="admin@example.com"
                           class="w-full border @error('admin_email') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 transition">
                </div>
                @error('admin_email')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-400 mt-1.5">
                    Cette adresse reçoit toutes les notifications administratives de l'application.
                </p>
            </div>

            <div class="flex justify-end pt-1">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Enregistrer l'adresse
                </button>
            </div>
        </form>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         SECTION 3 : Logo de l'application
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden"
         x-data="logoUploader()">

        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900">Logo principal</h2>
                <p class="text-xs text-gray-400 mt-0.5">Affiché dans la sidebar, les e-mails et en pied de page des PDF. PNG, JPG ou SVG, max 2 Mo.</p>
            </div>
        </div>

        @if(session('success_logo'))
        <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success_logo') }}
        </div>
        @endif

        <div class="px-6 py-5">
            {{-- Aperçu logo actuel --}}
            <div class="mb-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Logo actuel</p>
                <div class="flex items-center gap-4">
                    <div class="w-32 h-16 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center bg-gray-50 overflow-hidden">
                        @if($appLogo)
                        <img src="{{ Storage::url($appLogo) }}" alt="Logo actuel"
                             class="max-w-full max-h-full object-contain p-2">
                        @else
                        <span class="text-xs font-bold text-amber-500 px-3 text-center leading-tight">Magic Hotels</span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500">
                        @if($appLogo)
                        <p class="font-medium text-gray-700">Logo personnalisé</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ basename($appLogo) }}</p>
                        <form action="{{ route('admin.profile.logo.delete') }}" method="POST" class="mt-2"
                              onsubmit="return confirm('Supprimer le logo et revenir au logo par défaut ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-xs text-red-500 hover:text-red-700 underline transition-colors">
                                Supprimer le logo
                            </button>
                        </form>
                        @else
                        <p class="font-medium text-gray-700">Logo par défaut</p>
                        <p class="text-xs text-gray-400 mt-0.5">Texte « Magic Hotels » (aucun fichier)</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Upload nouveau logo --}}
            <form action="{{ route('admin.profile.logo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Nouveau logo</p>

                {{-- Zone de drop --}}
                <div class="relative"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="handleDrop($event)">

                    <label for="logo-input"
                           class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed rounded-xl cursor-pointer transition-all"
                           :class="dragging
                               ? 'border-amber-400 bg-amber-50'
                               : preview
                                   ? 'border-green-400 bg-green-50'
                                   : 'border-gray-300 bg-gray-50 hover:border-amber-400 hover:bg-amber-50/40'">

                        <template x-if="!preview">
                            <div class="flex flex-col items-center gap-2 text-center px-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="text-sm text-gray-500">
                                    <span class="font-semibold text-amber-600">Cliquez pour sélectionner</span>
                                    ou glissez-déposez
                                </p>
                                <p class="text-xs text-gray-400">PNG, JPG, SVG, WebP — max 2 Mo</p>
                            </div>
                        </template>

                        <template x-if="preview">
                            <div class="flex flex-col items-center gap-2">
                                <img :src="preview" alt="Aperçu" class="max-h-20 max-w-48 object-contain rounded-lg shadow-sm">
                                <p class="text-xs text-green-700 font-medium" x-text="fileName"></p>
                            </div>
                        </template>
                    </label>

                    <input id="logo-input" type="file" name="logo" accept="image/*" class="hidden"
                           @change="handleFile($event.target.files[0])">
                </div>

                @error('logo')
                <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                @enderror

                <div class="flex items-center justify-between mt-4">
                    <button type="button" x-show="preview" @click="clearPreview()"
                            class="text-xs text-gray-400 hover:text-gray-600 underline transition-colors">
                        Annuler la sélection
                    </button>
                    <div class="ml-auto">
                        <button type="submit" x-bind:disabled="!preview"
                                class="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors shadow-sm"
                                :class="preview
                                    ? 'bg-amber-500 hover:bg-amber-600 text-white cursor-pointer'
                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Téléverser le logo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         INFO : Compte connecté
    ══════════════════════════════════════════════════════════ --}}
    <div class="bg-slate-50 border border-slate-200 rounded-2xl px-6 py-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-amber-400 flex items-center justify-center text-slate-900 font-bold shrink-0">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-500">{{ auth()->user()->email }}
                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                    {{ ucfirst(auth()->user()->role) }}
                </span>
            </p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function logoUploader() {
    return {
        dragging: false,
        preview: null,
        fileName: '',

        handleFile(file) {
            if (!file) return;
            this.fileName = file.name;
            const reader = new FileReader();
            reader.onload = (e) => { this.preview = e.target.result; };
            reader.readAsDataURL(file);
        },

        handleDrop(e) {
            this.dragging = false;
            const file = e.dataTransfer.files[0];
            if (!file) return;
            // Injecter dans l'input file
            const dt = new DataTransfer();
            dt.items.add(file);
            document.getElementById('logo-input').files = dt.files;
            this.handleFile(file);
        },

        clearPreview() {
            this.preview = null;
            this.fileName = '';
            document.getElementById('logo-input').value = '';
        },
    };
}
</script>
@endpush
