<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ZonePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can create a zone.
     */
    public function create(User $user)
    {
        // Head and CoHead can create zones in any layer
        if (in_array($user->position, ['Head', 'CoHead'])) {
            return true;
        }

        // Senior Leader, Junior Leader, and Volunteer can create zones only in their layer
        if (in_array($user->position, ['Senior leader', 'Junior leader', 'Volunteer'])) {
            // Ensure the zone being created belongs to the user's layer
            return $user->layer === request()->input('layer');
        }
         // Default deny for any other roles
         return false;
    }

 

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Zone  $zone
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Zone $zone)
    {
        //
            // Head and CoHead can update any zone
            if (in_array($user->position, ['Head', 'CoHead'])) {
                return true;
            }

            // Senior Leader and Junior Leader can update zones in their layer
            if (in_array($user->position, ['Senior leader', 'Junior leader'])) {
                return $user->layer === $zone->user->layer
                ? Response::allow()
                : Response::deny('You are not authorized to update zones outside your layer.');
            }

            // Volunteers can update only their own zones
            return $user->id === $zone->user_id
            ? Response::allow()
            : Response::deny('You can only update zones that you created.');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Zone  $zone
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Zone $zone)
    {
        //
            // Head and CoHead can delete any zone
            if (in_array($user->position, ['Head', 'CoHead'])) {
                return true;
            }

            // Senior Leader and Junior Leader can delete zones in their layer
            if (in_array($user->position, ['Senior leader', 'Junior leader'])) {
                return $user->layer === $zone->user->layer
                ? Response::allow()
                : Response::deny('You are not authorized to delete zones outside your layer.');
            }

            // Volunteers can delete only their own zones
            return $user->id === $zone->user_id
            ? Response::allow()
            : Response::deny('You can only delete zones that you created.');
        
    }




   
}
