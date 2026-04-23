<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-900">
<head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Connexion Agence  Magic Hotels</title> <script src="https://cdn.tailwindcss.com"></script> <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"> <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-full flex items-center justify-center px-4"> <div class="w-full max-w-md"> {{-- Logo --}}
    <div class="text-center mb-8"> <span class="text-amber-400 text-3xl font-bold"> Magic Hotels</span> <p class="text-slate-400 text-sm mt-2">Espace Partenaires Agences</p> </div> {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-2xl p-8"> <h1 class="text-xl font-bold text-gray-900 mb-1">Connexion</h1> <p class="text-sm text-gray-500 mb-6">Accédez à vos réservations et à votre profil.</p> {{-- Erreurs --}}
        @if(session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm mb-4 flex items-center gap-2"> <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> {{ session('status') }}
        </div> @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-4 flex items-center gap-2"> <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg> {{ session('error') }}
        </div> @endif

        <form action="{{ route('agency.login.post') }}" method="POST" class="space-y-4"> @csrf

            <div> <label class="block text-sm font-medium text-gray-700 mb-1">Email</label> <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full border @error('email') border-red-400 @else border-gray-300 @enderror rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                       placeholder="votre@agence.com"> @error('email')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div> <div> <div class="flex items-center justify-between mb-1"> <label class="block text-sm font-medium text-gray-700">Mot de passe</label> <a href="{{ route('agency.password.forgot') }}" class="text-xs text-amber-600 hover:underline">Mot de passe oublié ?</a> </div> <input type="password" name="password" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none"
                       placeholder=""> </div> <div class="flex items-center gap-2"> <input type="checkbox" name="remember" id="remember" value="1"
                       class="w-4 h-4 text-amber-500 border-gray-300 rounded"> <label for="remember" class="text-sm text-gray-600">Se souvenir de moi</label> </div> <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-lg text-sm transition-colors"> Se connecter
            </button> </form> <p class="text-center text-xs text-gray-400 mt-6"> Vous n'avez pas encore de compte ?
            <a href="{{ route('agency.register') }}" class="text-amber-600 hover:underline">Devenir partenaire</a> </p> </div> <p class="text-center text-xs text-slate-500 mt-6"> © {{ date('Y') }} Magic Hotels  Pour toute assistance : <a href="mailto:{{ config('magic.contact_email') }}" class="text-amber-400 hover:underline">{{ config('magic.contact_email') }}</a> </p>
</div> </body>
</html>
