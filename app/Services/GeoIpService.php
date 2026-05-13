<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the MaxMind GeoLite2-City database.
 *
 * The DB lives at config('geoip.database_path'). If the file is missing,
 * lookups gracefully return null fields rather than throwing — the
 * `geoip:update` command exists to bootstrap/refresh the database.
 */
class GeoIpService
{
    protected ?Reader $reader = null;

    public function __construct()
    {
        $path = config('geoip.database_path');
        if ($path && is_file($path)) {
            try {
                $this->reader = new Reader($path);
            } catch (\Throwable $e) {
                Log::warning('geoip: failed to open db', ['err' => $e->getMessage()]);
            }
        }
    }

    /**
     * Look up an IP. Returns an array with:
     *   - country_code (string|null), country_name (string|null)
     *   - city (string|null)
     *   - latitude, longitude (float|null)
     *
     * Results are cached for 24h.
     */
    public function lookup(?string $ip): array
    {
        $empty = ['country_code' => null, 'country_name' => null, 'city' => null, 'latitude' => null, 'longitude' => null];

        if (! $ip || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $empty;
        }
        if (! $this->reader) {
            return $empty;
        }

        return Cache::remember("geoip:{$ip}", now()->addDay(), function () use ($ip, $empty) {
            try {
                $record = $this->reader->city($ip);

                return [
                    'country_code' => $record->country->isoCode,
                    'country_name' => $record->country->name,
                    'city' => $record->city->name,
                    'latitude' => $record->location->latitude,
                    'longitude' => $record->location->longitude,
                ];
            } catch (AddressNotFoundException) {
                return $empty;
            } catch (\Throwable $e) {
                Log::warning('geoip lookup failed', ['ip' => $ip, 'err' => $e->getMessage()]);

                return $empty;
            }
        });
    }
}
