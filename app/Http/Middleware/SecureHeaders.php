<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add a strict set of security headers to every HTTP response.
 *
 * CSP is configurable via the `security-headers.csp` config so that
 * apps embedding their own scripts/styles can tweak it without
 * touching middleware code.
 */
class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $this->buildHeaders($request);

        foreach ($headers as $name => $value) {
            if ($value === null) {
                continue;
            }
            $response->headers->set($name, $value, false);
        }

        return $response;
    }

    /**
     * @return array<string, string|null>
     */
    protected function buildHeaders(Request $request): array
    {
        $isProd = app()->environment('production');

        $csp = config('security-headers.csp', $this->defaultCsp());

        return [
            'X-Frame-Options'           => 'DENY',
            'X-Content-Type-Options'    => 'nosniff',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'Permissions-Policy'        => 'geolocation=(), microphone=(), camera=(), payment=()',
            'Content-Security-Policy'   => $csp,
            'Cross-Origin-Opener-Policy'    => 'same-origin',
            'Cross-Origin-Resource-Policy'  => 'same-origin',
            // HSTS only when actually served over HTTPS to avoid breaking dev.
            'Strict-Transport-Security' => $isProd && $request->isSecure()
                ? 'max-age=31536000; includeSubDomains; preload'
                : null,
        ];
    }

    /**
     * Conservative default policy. Pages currently inline a lot of <style>
     * so 'unsafe-inline' stays for `style-src` until a Vite refactor lands.
     */
    protected function defaultCsp(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' wss: https:",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
