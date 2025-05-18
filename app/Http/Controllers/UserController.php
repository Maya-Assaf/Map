<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function updatePosition(Request $request,$userid)
    {
        $request->validate([
         'NewPosition' => 'required|string|in:Head,CoHead,Senior leader,Junior leader,Volunteer',
        ]);

        $updated_by_id=Auth::id();
        $user=User::findOrFail($userid);
        $updated_by=User::findOrFail($updated_by_id);


        $this->authorize('updatePosition', $updated_by);

        Log::create([
            'old_position' =>$user->position,
            'new_position' =>$request->NewPosition,
            'affected_user_id'=> $userid,
            'updated_by_user_id'=>$updated_by_id,
        ]);

        $user->position = $request->NewPosition;
        $user->save();

        return response()->json([
            'message' => 'Position updated successfully.',
            'user' => $user
        ], 200);

    }
}
