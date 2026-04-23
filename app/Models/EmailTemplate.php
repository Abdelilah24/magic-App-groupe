<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'description', 'subject', 'html_body', 'placeholders', 'is_active',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'is_active'    => 'boolean',
    ];

    /**
     * Récupérer un template actif par sa clé (avec cache mémoire pour la requête).
     */
    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->where('is_active', true)->first();
    }

    /**
     * Remplacer les placeholders {{ variable }} dans le corps HTML.
     */
    public function renderBody(array $data): string
    {
        $html = $this->replacePlaceholders($this->cleanStoredHtml($this->html_body), $data);
        return $this->inlineEmailStyles($html);
    }

    /**
     * Convertit les classes CSS email en styles inline pour la compatibilité Gmail/Outlook.
     * Gmail strip les <head><style> ; les styles inline sont la seule garantie.
     */
    private function inlineEmailStyles(string $html): string
    {
        $map = [
            'btn' => 'display:inline-block;background:#f59e0b;color:#ffffff !important;font-weight:600;padding:12px 28px;border-radius:8px;text-decoration:none;font-size:15px;margin:8px 0;',
            'alert-success' => 'background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:8px;padding:14px 18px;margin:16px 0;font-size:14px;',
            'alert-warning' => 'background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:8px;padding:14px 18px;margin:16px 0;font-size:14px;',
            'alert-danger'  => 'background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:14px 18px;margin:16px 0;font-size:14px;',
            'info-box'      => 'background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px 20px;margin:16px 0;',
        ];

        foreach ($map as $class => $inlineStyle) {
            // Ajoute ou complète l'attribut style sur les éléments portant cette classe
            $html = preg_replace_callback(
                '/(<\w+\b[^>]*)\bclass="([^"]*\b' . preg_quote($class, '/') . '\b[^"]*)"([^>]*>)/i',
                function ($m) use ($inlineStyle) {
                    // Si un style existe déjà, on le complète ; sinon on l'ajoute
                    if (preg_match('/\bstyle="([^"]*)"/i', $m[0])) {
                        return preg_replace('/\bstyle="([^"]*)"/i', 'style="$1 ' . $inlineStyle . '"', $m[0]);
                    }
                    return $m[1] . ' class="' . $m[2] . '" style="' . $inlineStyle . '"' . $m[3];
                },
                $html
            );
        }

        return $html;
    }

    /**
     * Remplacer les placeholders dans le sujet.
     */
    public function renderSubject(array $data): string
    {
        return $this->replacePlaceholders($this->cleanStoredHtml($this->subject), $data);
    }

    /**
     * Nettoie le contenu stocké en DB :
     * 1. Restaure les {{ variable }} encodés par TinyMCE sous deux formes possibles :
     *    - <!--mce:protected URL-encoded-->     (forme normale)
     *    - &lt;!--mce:protected URL-encoded--&gt; (forme entité-encodée dans un attribut)
     * 2. Décode les entités HTML résiduelles si le corps a été stocké échappé
     *    (&lt;h1&gt; → <h1>)
     */
    private function cleanStoredHtml(string $text): string
    {
        // Étape 1 : mce:protected entité-encodé (dans href="..." etc.)
        $text = preg_replace_callback(
            '/&lt;!--mce:protected (.*?)--&gt;/',
            fn($m) => urldecode($m[1]),
            $text
        );

        // Étape 2 : mce:protected forme normale
        $text = preg_replace_callback(
            '/<!--mce:protected (.*?)-->/',
            fn($m) => urldecode($m[1]),
            $text
        );

        // Étape 3 : si le contenu a été stocké HTML-échappé (&lt;h1&gt; etc.)
        // on décode toutes les entités résiduelles
        if (str_contains($text, '&lt;') || str_contains($text, '&gt;')) {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Étape 4 : supprimer les <span class="tpl-var"> parasites qui ont pu
        // s'insérer à l'intérieur des attributs href/src lors d'anciennes sauvegardes
        $text = preg_replace('/<span\b[^>]*\btpl-var\b[^>]*>(.*?)<\/span>/is', '$1', $text);

        // Étape 5 : décoder les variables URL-encodées dans les attributs href/src/action
        // TinyMCE encode parfois {{ login_url }} → %7B%7B%20login_url%20%7D%7D
        $text = preg_replace_callback(
            '/\b(href|src|action)="([^"]*)"/i',
            function ($m) {
                $decoded = rawurldecode($m[2]);
                return $m[1] . '="' . $decoded . '"';
            },
            $text
        );

        return $text;
    }

    private function decodeMceProtected(string $text): string
    {
        return $this->cleanStoredHtml($text);
    }

    private function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            if (!is_string($value) && !is_numeric($value) && !($value instanceof \Stringable)) {
                continue;
            }
            $text = str_replace('{{ ' . $key . ' }}', (string) $value, $text);
            $text = str_replace('{{' . $key . '}}',   (string) $value, $text);
        }
        return $text;
    }
}
