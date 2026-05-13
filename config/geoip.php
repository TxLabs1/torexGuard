<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MaxMind license key
    |--------------------------------------------------------------------------
    |
    | Sign up for a free GeoLite2 account at
    | https://www.maxmind.com/en/geolite2/signup and paste the license
    | key here so `php artisan geoip:update` can download the database.
    */
    'license_key' => env('GEOIP_LICENSE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Database path
    |--------------------------------------------------------------------------
    |
    | Path (relative to the project root or absolute) where the
    | GeoLite2-City.mmdb file is stored.
    */
    'database_path' => env('GEOIP_DATABASE_PATH', database_path('geoip/GeoLite2-City.mmdb')),

    /*
    |--------------------------------------------------------------------------
    | Download URL
    |--------------------------------------------------------------------------
    */
    'download_url' => env(
        'GEOIP_DOWNLOAD_URL',
        'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz',
    ),
];
