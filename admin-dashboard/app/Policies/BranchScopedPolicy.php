<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BranchScopedPolicy
{
    public function manage(User $user, Model $model): bool
    {
        return $user->canAccessBranch($model->getAttribute('branch_id'));
    }
}
