@extends('admin.layouts.app')
@section('title', 'Modifier  ' . $template->name)
@section('page-title', 'Template PDF')
@section('page-subtitle', $template->name)

@section('header-actions')
    <a href="{{ route('admin.pdf-templates.index') }}"
       class="text-sm text-gray-500 hover:text-gray-700"> Retour aux templates PDF</a>
@endsection

@section('content')

{{-- TinyMCE 6 via tiny.cloud --}}
<script src="https://cdn.tiny.cloud/1/xgu7d1w33conjur2p5j8ymj04t6a0p1141j4o7fhgwiga62j/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<form method="POST" action="{{ route('admin.pdf-templates.update', $template) }}" id="template-form">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Colonne éditeur (2/3) --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Éditeur TinyMCE — corps HTML uniquement --}}
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Corps HTML du document PDF</h2>
                    <button type="button" id="btn-preview"
                            class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:border-gray-300 transition">
                        Aperçu
                    </button>
                </div>
                <p class="px-5 py-2 text-xs text-gray-400 border-b border-gray-100">
                    Le corps du document (contenu entre &lt;body&gt;…&lt;/body&gt;). N'inclut pas le CSS — éditez le CSS dans la section ci-dessous.
                    Utilisez le bouton <strong>Code</strong> dans la barre d'outils pour éditer le HTML brut.
                </p>
                <div class="p-3">
                    <textarea name="html_body" id="html-body-input">{!! old('html_body', $template->html_body) !!}</textarea>
                </div>
            </div>

            {{-- Éditeur CSS — textarea monospace, jamais touché par TinyMCE --}}
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">CSS du document PDF</h2>
                </div>
                <p class="px-5 py-2 text-xs text-gray-400 border-b border-gray-100">
                    Feuille de styles injectée dans <code class="bg-gray-100 px-1 rounded">&lt;head&gt;&lt;style&gt;…&lt;/style&gt;&lt;/head&gt;</code>
                    au moment du rendu PDF. Non modifié par TinyMCE.
                </p>
                <div class="p-3">
                    <textarea name="css" id="css-input"
                              rows="20"
                              class="w-full font-mono text-xs text-gray-800 bg-gray-50 border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-amber-400 resize-y"
                              spellcheck="false">{{ old('css', $template->css) }}</textarea>
                </div>
            </div>

            {{-- Aperçu inline (iframe, caché par défaut) --}}
            <div id="preview-panel" class="hidden bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Aperçu (données fictives)</h2>
                    <button type="button" id="btn-close-preview"
                            class="text-xs text-gray-400 hover:text-gray-600">Fermer</button>
                </div>
                <iframe id="preview-iframe" class="w-full border-0" style="height:800px;"></iframe>
            </div>

        </div>

        {{-- Colonne latérale (1/3) --}}
        <div class="space-y-5">

            {{-- Statut --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Statut</h3>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                           {{ $template->is_active ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
                    <span class="text-sm text-gray-700">Template actif</span>
                </label>
                <p class="text-xs text-gray-400 mt-2">
                    Si désactivé, le système utilise le template Blade par défaut.
                </p>
            </div>

            {{-- Placeholders --}}
            @if($template->placeholders)
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                    Variables disponibles
                    <span class="ml-1 text-gray-300 font-normal normal-case">(cliquer pour insérer)</span>
                </h3>
                <div class="space-y-1.5">
                    @foreach($template->placeholders as $ph)
                    <button type="button"
                            onclick="insertPlaceholder('{{ $ph['key'] }}')"
                            class="w-full flex items-center justify-between gap-2 text-left px-3 py-2 rounded-lg border border-gray-100 hover:border-amber-300 hover:bg-amber-50 transition group">
                        <span class="text-xs font-mono text-amber-700 group-hover:text-amber-800">
                            &#123;&#123; {{ $ph['key'] }} &#125;&#125;
                        </span>
                        <span class="text-xs text-gray-400 truncate text-right">{{ $ph['label'] }}</span>
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Infos --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5 text-xs text-gray-500 space-y-1.5">
                <p><span class="font-medium text-gray-700">Clé :</span>
                    <code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">{{ $template->key }}</code></p>
                <p><span class="font-medium text-gray-700">Dernière modif :</span>
                    {{ $template->updated_at->format('d/m/Y H:i') }}</p>
            </div>

            {{-- Actions --}}
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 space-y-3">
                <button type="submit"
                        class="w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    Enregistrer le template
                </button>
                <a href="{{ route('admin.pdf-templates.preview', $template) }}" target="_blank"
                   class="block w-full text-center border border-amber-300 text-amber-800 hover:bg-amber-100 font-medium py-2 rounded-lg text-sm transition">
                    Aperçu dans un nouvel onglet
                </a>
            </div>

        </div>
    </div>
</form>

<script>
const PREVIEW_URL = '{{ route('admin.pdf-templates.preview', $template) }}';
</script>

<script>
// ── Helpers variables ─────────────────────────────────────────────────────

function wrapVars(html) {
    return html.replace(/(<[^>]*>)|(\{\{\s*[\w.]+\s*\}\})/g, function (match, tag, variable) {
        if (tag) return tag;
        return '<span class="tpl-var" data-var="' + variable + '">' + variable + '</span>';
    });
}

function unwrapVars(html) {
    return html.replace(/<span\b[^>]*\btpl-var\b[^>]*>([\s\S]*?)<\/span>/gi, '$1');
}

// ── TinyMCE ────────────────────────────────────────────────────────────────────
tinymce.init({
    selector: '#html-body-input',
    height: 620,
    menubar: 'edit view insert format tools table',
    language: 'fr_FR',

    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
        'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'table', 'wordcount',
    ],

    toolbar:
        'undo redo | blocks | ' +
        'bold italic underline forecolor backcolor | ' +
        'alignleft aligncenter alignright | ' +
        'bullist numlist | link | table | ' +
        'removeformat | code | fullscreen',

    valid_elements          : '*[*]',
    extended_valid_elements : '*[*]',
    verify_html             : false,
    cleanup                 : false,
    cleanup_on_startup      : false,
    convert_urls            : false,
    relative_urls           : false,
    remove_script_host      : false,
    entity_encoding         : 'raw',
    encoding                : 'xml',

    urlconverter_callback: function(url) { return url; },

    content_style: `
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            color: #1e293b;
            line-height: 1.6;
            padding: 16px 20px;
            margin: 0 auto;
        }
        h1 { font-size: 22px; color: #1e293b; margin-bottom: 16px; }
        h2 { font-size: 18px; color: #374151; }
        table { border-collapse: collapse; }
        a { color: #f59e0b; }
        span.tpl-var {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 0 5px;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.6;
            white-space: nowrap;
        }
    `,

    setup: function (editor) {
        editor.on('change', function () { editor.save(); });
        editor.on('focus',  function () { window._lastFocused = 'editor'; });
    },

    init_instance_callback: function (editor) {
        const raw = editor.getContent();
        editor.setContent(wrapVars(unwrapVars(raw)));
    },
});

// ── Soumission : enlever les spans avant envoi ─────────────────────────────────
document.getElementById('template-form').addEventListener('submit', function () {
    tinymce.triggerSave();
    const ta = document.getElementById('html-body-input');
    ta.value = unwrapVars(ta.value);
});

// ── Aperçu inline ──────────────────────────────────────────────────────────────
document.getElementById('btn-preview').addEventListener('click', function () {
    tinymce.triggerSave();
    loadPreview();
    document.getElementById('preview-panel').classList.remove('hidden');
    document.getElementById('preview-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
});

document.getElementById('btn-close-preview').addEventListener('click', function () {
    document.getElementById('preview-panel').classList.add('hidden');
});

function loadPreview() {
    const iframe = document.getElementById('preview-iframe');
    iframe.src = PREVIEW_URL + '?t=' + Date.now();
}

// ── Suivi du dernier champ focalisé ───────────────────────────────────────────
window._lastFocused = 'editor';

// ── Insertion d'une variable ───────────────────────────────────────────────────
function insertPlaceholder(key) {
    const raw    = '\u007B\u007B ' + key + ' \u007D\u007D';
    const styled = '<span class="tpl-var" data-var="' + raw + '">' + raw + '</span>';

    const editor = tinymce.get('html-body-input');
    if (!editor) return;
    editor.focus();
    editor.insertContent(styled);
    editor.save();
}
</script>
@endsection
