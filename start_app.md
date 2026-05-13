php artisan serve            # web at :8000
php artisan reverb:start     # websockets at :8080
php artisan horizon          # queue dashboard at /horizon
php artisan queue:work redis # threat-event worker



FOR MY CI/CD

cd /Applications/XAMPP/xamppfiles/htdocs/torexGuard

# Push the workflow fix + go.sum commit to main
git push origin main

# (Local tag may still exist from earlier — drop it if so, ignore the error otherwise)
git tag -d v1.0.0 2>/dev/null || true

# Re-tag on the new HEAD and push
git tag -a v1.0.0 -m "Torex Guard v1.0.0"
git push origin v1.0.0







git add composer.json composer.lock .github/workflows/ci.yml .github/workflows/release.yml agent/go.mod agent/go.sum
git commit -m "ci: pin PHP 8.3 platform, multi-platform go.sum, enforce tidy"
git push origin main
# Delete the failed v1.0.0 release in the GitHub UI first
#   https://github.com/TxLabs1/torexGuard/releases
# then drop the remote + local tag and re-tag on the fixed HEAD:
git push origin :refs/tags/v1.0.0   # only needed if it was re-pushed
git tag -d v1.0.0 2>/dev/null || true
git tag -a v1.0.0 -m "Torex Guard v1.0.0"
git push origin v1.0.0