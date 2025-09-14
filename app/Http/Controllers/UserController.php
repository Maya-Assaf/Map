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
    public function updatePosition(Request $request, $userid)
    {
        $request->validate([
            'NewPosition' => 'required|string|in:Head,CoHead,Senior leader,Junior leader,Volunteer',
        ]);

        $updated_by_id = Auth::id();
        $user = User::findOrFail($userid);
        $updated_by = User::findOrFail($updated_by_id);


        $this->authorize('updatePosition', $updated_by);

        Log::create([
            'old_position' => $user->position,
            'new_position' => $request->NewPosition,
            'affected_user_id' => $userid,
            'updated_by_user_id' => $updated_by_id,
        ]);

        $user->position = $request->NewPosition;
        $user->save();

        return response()->json([
            'message' => 'Position updated successfully.',
            'user' => $user
        ]);

    }

    //عرض معلومات المستخدم ضمن بروفايله
    public function getProfile()
    {
        $user = Auth::user();
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->position,
                'department' => $user->department,
                'layer' => $user->layer,
                'profile_image' => $user->profile_image
                    ? asset($user->profile_image)
                    : null,
            ]
        ]);
    }

    //التعديل على ال بروفايل بس الاسم والايميل

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        // منع تعديل أي حقول غير الاسم والبريد الإلكتروني وكلمة المرور وصورة الملف الشخصي
        $forbiddenFields = ["position", "department", "layer"];

        foreach ($forbiddenFields as $field) {
            if ($request->has($field)) {
                return response()->json(["message" => "You are not allowed to change the field: $field. Please contact the admin for this update."], 403);
            }
        }

        $rules = [
            "name" => "nullable|string|max:255",
            "email" => "nullable|string|email|max:255|unique:users,email," . $user->id,
            "profile_image" => "nullable|image|mimes:jpeg,png,jpg,gif|max:2048",
        ];

        // قواعد التحقق لكلمة المرور الجديدة
        if ($request->filled("password")) {
            $rules["current_password"] = "required|string";
            $rules["password"] = "required|string|min:8|confirmed";
        } elseif ($user->force_password_change) {
            // إذا كان المستخدم مجبرًا على تغيير كلمة المرور ولم يقدم كلمة مرور جديدة
            $rules["password"] = "required|string|min:8|confirmed";
            $rules["current_password"] = "required|string"; // يجب أن يقدم كلمة المرور الحالية للتحقق
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        // تحديث الاسم إذا تم إرساله
        if ($request->filled("name")) {
            $user->name = $request->name;
        }


        // معالجة تغيير كلمة المرور
        if ($request->filled("password")) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(["message" => "Your current password is incorrect."], 403);
            }

            $user->password = Hash::make($request->password);
            $user->force_password_change = false; // إعادة تعيين هذا الحقل بعد تغيير كلمة المرور
        } elseif ($user->force_password_change) {
            // إذا كان المستخدم مجبرًا على تغيير كلمة المرور ولم يقدم كلمة مرور جديدة في الطلب الحالي
            // هذا السيناريو يجب أن يتم التقاطه بواسطة قواعد التحقق أعلاه، ولكن هذا كإجراء احتياطي
            return response()->json(["message" => "You must change your password."], status: 422);
        }

        // رفع صورة الملف الشخصي
        if ($request->hasFile("profile_image")) {
            if ($user->profile_image && file_exists(public_path($user->profile_image))) {
                unlink(public_path($user->profile_image));
            }

            $image = $request->file("profile_image");
            $imageName = time() . "_" . $image->getClientOriginalName();
            $image->move(public_path("profile_images"), $imageName);
            $user->profile_image = "profile_images/" . $imageName;
        }

        $user->save();

        return response()->json([
            "message" => "Profile updated successfully.",
            "user" => $user
        ]);
    }
}
