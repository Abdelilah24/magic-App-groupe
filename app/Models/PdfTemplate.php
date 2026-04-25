<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PdfTemplate extends Model
{
    use LogsActivity;

    protected string $activitySection = 'Modèles PDF';
    protected $fillable = [
        'key', 'name', 'description', 'html_body', 'css', 'placeholders', 'is_active',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'is_active'    => 'boolean',
    ];

    /**
     * Récupérer un template actif par sa clé.
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
        $body = $this->replacePlaceholders($this->cleanStoredHtml($this->html_body), $data);
        $css  = $this->css ?? '';

        return '<!DOCTYPE html>' . "\n"
            . '<html lang="fr"><head>' . "\n"
            . '<meta charset="UTF-8">' . "\n"
            . '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>' . "\n"
            . '<style>' . "\n" . $css . "\n" . '</style>' . "\n"
            . '</head><body>' . "\n"
            . $body . "\n"
            . '</body></html>';
    }

    /**
     * Nettoie le contenu stocké en DB :
     * 1. Restaure les {{ variable }} encodés par TinyMCE
     * 2. Décode les entités HTML résiduelles
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
        if (str_contains($text, '&lt;') || str_contains($text, '&gt;')) {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Étape 4 : supprimer les <span class="tpl-var"> parasites
        $text = preg_replace('/<span\b[^>]*\btpl-var\b[^>]*>(.*?)<\/span>/is', '$1', $text);

        // Étape 5 : décoder les variables URL-encodées dans les attributs href/src/action
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

    /**
     * Remplacer les placeholders dans le texte.
     */
    public function replacePlaceholders(string $text, array $data): string
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
