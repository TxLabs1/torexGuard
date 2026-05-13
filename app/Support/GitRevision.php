<?php

namespace App\Support;

class GitRevision
{
    /**
     * Best-effort current commit SHA. Used for Sentry release tagging.
     *
     * Looks at, in order:
     *   1) SENTRY_RELEASE env (set by CI / deploy script)
     *   2) APP_RELEASE env
     *   3) /var/torex/RELEASE file (written by docker entrypoint)
     *   4) .git/HEAD on disk (development)
     */
    public static function current(): ?string
    {
        foreach (['SENTRY_RELEASE', 'APP_RELEASE'] as $env) {
            $value = getenv($env);
            if ($value !== false && $value !== '') {
                return trim($value);
            }
        }

        if (is_readable('/var/torex/RELEASE')) {
            $r = trim((string) @file_get_contents('/var/torex/RELEASE'));
            if ($r !== '') {
                return $r;
            }
        }

        $head = base_path('.git/HEAD');
        if (! is_readable($head)) {
            return null;
        }

        $ref = trim((string) @file_get_contents($head));
        if (str_starts_with($ref, 'ref: ')) {
            $refPath = base_path('.git/'.substr($ref, 5));
            if (is_readable($refPath)) {
                return substr(trim((string) @file_get_contents($refPath)), 0, 12);
            }

            return null;
        }

        return substr($ref, 0, 12) ?: null;
    }
}
