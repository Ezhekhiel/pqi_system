<?php

namespace App\Policies;

use App\Models\User;
use App\Models\area___db_pqi;
use Illuminate\Auth\Access\Response;

class AreaDbPqiPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, area___db_pqi $areaDbPqi): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, area___db_pqi $areaDbPqi): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, area___db_pqi $areaDbPqi): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, area___db_pqi $areaDbPqi): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, area___db_pqi $areaDbPqi): bool
    {
        //
    }
}
