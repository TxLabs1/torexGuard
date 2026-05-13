<?php

namespace App\Models\Scopes;

use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that restricts org-scoped models to the
 * currently-authenticated user's organisation.
 *
 * Bypass with ::withoutGlobalScope(OrganizationScope::class) or by setting
 * OrganizationContext::asSystem() (e.g. queue workers, agent ingestion).
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $orgId = OrganizationContext::currentId();

        if ($orgId === null) {
            // No active organization (CLI, system, agent) → do not restrict.
            return;
        }

        $builder->where($model->qualifyColumn('organization_id'), $orgId);
    }
}
