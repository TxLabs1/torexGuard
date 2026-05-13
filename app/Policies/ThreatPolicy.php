<?php

namespace App\Policies;

use App\Models\Threat;
use App\Models\User;

class ThreatPolicy extends OrgScopedPolicy
{
    public function quarantine(User $user, Threat $threat): bool
    {
        return $this->sameOrg($user, $threat);
    }

    public function resolve(User $user, Threat $threat): bool
    {
        return $this->sameOrg($user, $threat);
    }

    public function investigate(User $user, Threat $threat): bool
    {
        return $this->sameOrg($user, $threat);
    }

    public function markFalsePositive(User $user, Threat $threat): bool
    {
        return $this->sameOrg($user, $threat);
    }
}
