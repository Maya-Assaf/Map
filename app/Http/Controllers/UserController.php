<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

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
    //عرض معلومات المستخدم ضمن بروفايله
    public function getProfile()
    {
        $user = Auth::user();
        return response()->json([
            'user' => [
                'name'       => $user->name,
                'email'      => $user->email,
                'position'   => $user->position,
                'department' => $user->department,
                'layer'      => $user->layer,
            ]
        ]);
    }
    
    //التعديل على ال بروفايل بس الاسم والايميل

    public function updateProfile(Request $request, $id)
{
  

    // Find the user by ID
    $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

   // منع تعديل أي حقول غير الاسم
    $forbiddenFields = ['email', 'position', 'department', 'layer'];

    foreach ($forbiddenFields as $field) {
        if ($request->has($field)) {
            return response()->json(['message' => "You are not allowed to change the field: $field ! You must ask request from the admin!"], 403);
        }
    }

    // Validation rules
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
    ]);

     if ($request->filled('password')) {
        $rules['current_password'] = 'required|string';
        $rules['password'] = 'required|string|min:8|confirmed';
    }

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }




    $user->name = $request->name;
    if ($request->filled('password')) {
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة.'], 403);
        }

        $user->password = Hash::make($request->password);
    }

    $user->save();

    

    return response()->json([
        'message' => 'Profile updated successfully',
        'user'    => $user
    ], 200);
    }
}
