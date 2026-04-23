<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe – Magic Hotels</title>
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
        <h1 class="text-xl font-bold text-gray-900 mb-1">Nouveau mot de passe</h1>
        <p class="text-sm text-gray-500 mb-6">Choisissez un nouveau mot de passe pour votre compte.</p>

        {{-- Erreurs globales --}}
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-4 flex items-start gap-2">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <div>
                @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
        @endif

        <form action="{{ route('agency.password.reset') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required
                       class="w-full border @error('email') border-red-400 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                       placeholder="votre@agence.com">
                @error('email')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full border @error('password') border-red-400 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                       placeholder="Minimum 8 caractères">
                @error('password')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                       placeholder="Répétez le mot de passe">
            </div>

            <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                Réinitialiser mon mot de passe
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
