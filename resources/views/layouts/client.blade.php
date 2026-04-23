<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Réservation') — Magic Hotels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .flatpickr-calendar {
            font-family: 'Inter', sans-serif;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px -5px rgb(0 0 0 / .1), 0 4px 6px -2px rgb(0 0 0 / .05);
            border: 1px solid #e5e7eb;
        }
        .flatpickr-day.selected, .flatpickr-day.selected:hover {
            background: #f59e0b; border-color: #f59e0b;
        }
        .flatpickr-day.today { border-color: #f59e0b; }
        .flatpickr-months .flatpickr-month,
        .flatpickr-weekdays, span.flatpickr-weekday { background: #1e293b; color: white; }
        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month { fill: white; }
        .flatpickr-months .flatpickr-prev-month:hover svg,
        .flatpickr-months .flatpickr-next-month:hover svg { fill: #f59e0b; }
    </style>
</head>
<body class="min-h-full bg-gradient-to-br from-slate-50 to-amber-50">

    <header class="bg-white border-b border-gray-100 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-2xl">✦</span>
                <div>
                    <p class="font-bold text-gray-900 leading-none">Magic Hotels</p>
                    <p class="text-xs text-gray-400">Portail Agences</p>
                </div>
            </div>
            @isset($reservation)
            <div class="text-right">
                <p class="text-xs text-gray-400">Référence</p>
                <p class="text-sm font-mono font-bold text-amber-600">{{ $reservation->reference }}</p>
            </div>
            @endisset
        </div>
    </header>

    <main class="@yield('main-class', 'max-w-4xl mx-auto') px-4 py-8">
        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-4 text-sm flex items-center gap-3 mb-6">
            <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-4 text-sm flex items-center gap-3 mb-6">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
        </div>
        @endif

        @yield('content')
    </main>

    <footer class="mt-12 border-t border-gray-200 bg-white">
        <div class="max-w-4xl mx-auto px-4 py-4 text-center text-xs text-gray-400">
            © {{ date('Y') }} Magic Hotels — Plateforme de réservation groupes
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/fr.js"></script>
    <script>
    (function () {
        flatpickr.localize(flatpickr.l10ns.fr);

        function makeCfg(el) {
            const cfg = {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: false,
                disableMobile: true,
                onReady: function (dates, dateStr, instance) {
                    if (instance.altInput)
                        instance.altInput.placeholder = instance.element.placeholder || 'jj/mm/aaaa';
                },
                onChange: function (dates, dateStr, instance) {
                    instance.element.dispatchEvent(new Event('input',  { bubbles: true }));
                    instance.element.dispatchEvent(new Event('change', { bubbles: true }));
                },
            };
            if (el.min) cfg.minDate = el.min;
            if (el.max) cfg.maxDate = el.max;
            return cfg;
        }

        window.initDatePickers = function (root) {
            (root || document).querySelectorAll("input[type='date']").forEach(function (el) {
                if (!el._flatpickr) flatpickr(el, makeCfg(el));
            });
        };

        // Observer pour champs ajoutés dynamiquement
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches("input[type='date']") && !node._flatpickr) {
                        flatpickr(node, makeCfg(node));
                    } else {
                        window.initDatePickers(node);
                    }
                });
            });
        });

        // Démarrer l'observation dès que possible
        document.addEventListener('DOMContentLoaded', function () {
            observer.observe(document.body, { childList: true, subtree: true });
            window.initDatePickers();
        });

        // Après Alpine : polling jusqu'à ce que tous les champs soient initialisés
        document.addEventListener('alpine:initialized', function () {
            var attempts = 0;
            var interval = setInterval(function () {
                window.initDatePickers();
                attempts++;
                if (attempts >= 20) clearInterval(interval); // 20 × 150ms = 3 sec
            }, 150);
        });
    })();
    </script>

    @stack('scripts')
</body>
</html>
