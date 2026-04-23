<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié – Magic Hotels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-full flex items-center justify-center px-4">
<div class="w-full max-w-md">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <span class="text-amber-400 text-3xl font-bold"> Magic Hotels</span>
        <p class="text-slate-400 text-sm mt-2">Espace Partenaires Agences</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h1 class="text-xl font-bold text-gray-900 mb-1">Mot de passe oublié</h1>
        <p class="text-sm text-gray-500 mb-6">Saisissez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>

        {{-- Message de statut --}}
        @if(session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('status') }}
        </div>
        @endif

        <form action="{{ route('agency.password.forgot.send') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full border @error('email') border-red-400 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                       placeholder="votre@agence.com">
                @error('email')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                Envoyer le lien de réinitialisation
            </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-6">
            <a href="{{ route('agency.login') }}" class="text-amber-600 hover:underline">← Retour à la connexion</a>
        </p>
    </div>

    <p class="text-center text-xs text-slate-500 mt-6">
        © {{ date('Y') }} Magic Hotels &nbsp;·&nbsp; Pour toute assistance :
        <a href="mailto:{{ config('magic.contact_email') }}" class="text-amber-400 hover:underline">{{ config('magic.contact_email') }}</a>
    </p>
</div>
</body>
</html>
