<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy for any org-scoped resource (Threat, Server, Project, Alert).
 *
 * The default authorization rule is: "the user belongs to the same
 * organization as the model". Concrete policies can extend this and
 * override specific actions to add role-based restrictions
 * (e.g. only admins can delete a project).
 */
abstract class OrgScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->organizations()->exists();
    }

    public function view(User $user, Model $model): bool
    {
        return $this->sameOrg($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->organizations()->exists();
    }

    public function update(User $user, Model $model): bool
    {
        return $this->sameOrg($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->sameOrg($user, $model) && $user->isAdmin();
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->delete($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->delete($user, $model);
    }

    protected function sameOrg(User $user, Model $model): bool
    {
        $orgId = $model->getAttribute('organization_id');
        if ($orgId === null) {
            return false;
        }

        return $user->organizations()
            ->where('organizations.id', $orgId)
            ->exists();
    }
}
