<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content-Security-Policy
    |--------------------------------------------------------------------------
    |
    | If set, this value overrides the conservative default policy baked
    | into App\Http\Middleware\SecureHeaders. Set it via the
    | CSP_POLICY env if you need an environment-specific value.
    */

    'csp' => env('CSP_POLICY'),

];
