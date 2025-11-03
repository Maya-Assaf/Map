<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LocationPolicy
{
    use HandlesAuthorization;

  

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    // public function create(User $user)
    // {
    //     //
    //      // Head and CoHead can create locations in any layer
    //      if (in_array($user->position, ['Head', 'CoHead'])) {
    //         return true;
    //     }

    //     // Senior Leader, Junior Leader, and Volunteer can create locations only in their layer
    //     if (in_array($user->position, ['Senior leader', 'Junior leader', 'Volunteer'])) {
    //         // Ensure the location being created belongs to the user's layer
    //         return $user->layer === $user->layer; 
    //     }
    //      // Default deny for any other roles
    //      return false;
    // }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Location $location)
    {
        //
         // Head and CoHead can update any location
        if (in_array($user->position, ['Head', 'CoHead'])) {
            return true;
        }

        // Senior Leader and Junior Leader can update locations in their layer
        if (in_array($user->position, ['Senior leader', 'Junior leader'])) {
            return $user->layer === $location->user->layer
            ? Response::allow()
            : Response::deny('You are not authorized to update locations outside your layer.');
        }

        // Volunteers can update only their own zones
        return $user->id === $location->user_id
        ? Response::allow()
        : Response::deny('You can only update locations that you created.');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Location $location)
    {
        //
        // Head and CoHead can delete any location
        if (in_array($user->position, ['Head', 'CoHead'])) {
            return true;
        }

        // Senior Leader and Junior Leader can delete locations in their layer
        if (in_array($user->position, ['Senior leader', 'Junior leader'])) {
            return $user->layer === $location->user->layer
            ? Response::allow()
            : Response::deny('You are not authorized to delete locations outside your layer.');
        }

        // Volunteers can delete only their own zones
        return $user->id === $location->user_id
        ? Response::allow()
        : Response::deny('You can only delete locations that you created.');
    }

  
}
