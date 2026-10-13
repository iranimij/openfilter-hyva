#!/usr/bin/env bash
# Runs inside the ExtDN containers after the local-source repository is configured and before Composer installs
# the Magento project. Keep it idempotent: the same script serves the unit and PHPStan jobs.
set -e

# Authenticate Composer against the GitHub API (VCS repositories and dist downloads) to avoid the anonymous rate limit.
if [ -n "${GITHUB_TOKEN:-}" ]; then
    composer config -g github-oauth.github.com "$GITHUB_TOKEN"
fi

# Until iranimij/openfilter is on Packagist, CI resolves it from GitHub, at the branch named in
# .github/ci/openfilter-ref ("main" while nothing is stacked), aliased 1.0.x-dev so it satisfies "^1.0".
composer config repositories.iranimij-openfilter vcs https://github.com/iranimij/openfilter
# Hyvä's theme module (required: \$hyvaCsp, view models, theme detection) is OSL but not on packagist.org: its public
# GitHub repositories serve it and the bundle it requires.
for repo in theme-module mollie-theme-bundle; do
    # git clones, not the GitHub API: many parallel jobs would exhaust the token's API rate limit ("Could not authenticate").
    composer config "repositories.hyva-$repo" '{"type":"vcs","url":"https://github.com/hyva-themes/magento2-'"$repo"'","no-api":true}'
done
composer config minimum-stability dev
composer config prefer-stable true
OPENFILTER_REF="main"
for ref_file in "$(dirname "$0")/openfilter-ref" "${GITHUB_WORKSPACE:-/github/workspace}/.github/ci/openfilter-ref"; do
    if [ -f "$ref_file" ]; then
        OPENFILTER_REF="$(tr -d '[:space:]' < "$ref_file")"
        break
    fi
done
composer require --no-update --no-interaction "iranimij/openfilter:dev-${OPENFILTER_REF:-main} as 1.0.x-dev"

# PHPStan job only: the Magento-aware PHPStan extension (factories, proxies, generated classes).
if [ -n "${INPUT_PHPSTAN_LEVEL:-}" ]; then
    composer require --dev --no-interaction --no-progress --with-all-dependencies bitexpert/phpstan-magento
fi
