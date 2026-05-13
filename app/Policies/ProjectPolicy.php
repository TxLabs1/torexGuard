<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy extends OrgScopedPolicy
{
    public function rotateKey(User $user, Project $project): bool
    {
        return $this->sameOrg($user, $project) && $user->isAdmin();
    }
}
