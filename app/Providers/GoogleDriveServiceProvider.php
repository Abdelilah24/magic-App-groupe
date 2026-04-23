<?php

namespace App\Providers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToReadFile;
use Masbug\Flysystem\GoogleDriveAdapter;
use Google\Client as GoogleClient;
use Google\Service\Drive;

/**
 * Wrapper autour de GoogleDriveAdapter pour corriger le bug :
 * listContents() jette UnableToReadFile si le dossier n'existe pas encore,
 * au lieu de retourner une liste vide — ce qui bloque isReachable() de spatie.
 */
class SafeGoogleDriveAdapter extends GoogleDriveAdapter
{
    public function listContents(string $path, bool $deep): iterable
    {
        try {
            yield from parent::listContents($path, $deep);
        } catch (UnableToReadFile $e) {
            // Dossier inexistant → liste vide (comportement attendu par flysystem)
        }
    }
}

class GoogleDriveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Storage::extend('google', function ($app, $config) {

            $client = new GoogleClient();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);

            $service = new Drive($client);

            $adapter = new SafeGoogleDriveAdapter(
                $service,
                $config['folderId'] ?? 'root',
            );

            return new FilesystemAdapter(
                new Filesystem($adapter),
                $adapter,
                $config,
            );
        });
    }
}
