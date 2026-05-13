<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class UpdateGeoIp extends Command
{
    protected $signature = 'geoip:update {--force : redownload even if the DB is fresh}';

    protected $description = 'Download the latest GeoLite2-City.mmdb from MaxMind';

    public function handle(): int
    {
        $key = config('geoip.license_key');
        if (! $key) {
            $this->error('GEOIP_LICENSE_KEY is not set in .env. Get a free key at https://www.maxmind.com/en/geolite2/signup');

            return self::FAILURE;
        }

        $target = config('geoip.database_path');
        if (! $this->option('force') && is_file($target) && filemtime($target) > now()->subDays(7)->getTimestamp()) {
            $this->info("Database at {$target} is fresh (<7 days old). Skipping. Use --force to override.");

            return self::SUCCESS;
        }

        $url = sprintf(config('geoip.download_url'), $key);
        $this->info('Downloading GeoLite2-City...');

        $tmpTar = storage_path('app/geoip-tmp.tar.gz');
        File::ensureDirectoryExists(dirname($tmpTar));

        $response = Http::withOptions(['sink' => $tmpTar, 'timeout' => 180])->get($url);
        if (! $response->successful()) {
            $this->error("download failed: HTTP {$response->status()}");

            return self::FAILURE;
        }

        $this->info('Extracting...');
        $extractDir = storage_path('app/geoip-extract');
        File::ensureDirectoryExists($extractDir);

        $phar = new \PharData($tmpTar);
        $phar->decompress();
        $tarPath = preg_replace('/\.gz$/', '', $tmpTar);
        $tar = new \PharData($tarPath);
        $tar->extractTo($extractDir, null, true);

        // Find the .mmdb inside the extracted directory.
        $mmdb = collect(File::allFiles($extractDir))
            ->first(fn ($f) => str_ends_with($f->getFilename(), '.mmdb'));

        if (! $mmdb) {
            $this->error('extracted archive contained no .mmdb file');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($target));
        File::move($mmdb->getRealPath(), $target);

        File::deleteDirectory($extractDir);
        @unlink($tmpTar);
        @unlink($tarPath);

        $this->info("✅ GeoIP database updated at {$target}");

        return self::SUCCESS;
    }
}
