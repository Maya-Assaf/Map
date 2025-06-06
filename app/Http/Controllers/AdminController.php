<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PreRegisteredUser;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash; 

class AdminController extends Controller
{
    //
    public function index(Request $request)
    {
        $query = PreRegisteredUser::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        //Akram Add user_id here for Delete process
        $perPage = $request->get('per_page', 10);
        $users = $query->select('id', 'name', 'email', 'position', 'department', 'layer', 'status')
         ->paginate($perPage);
        return response()->json($users);

    }

    public function update(Request $request, $id)
{
     $preUser = PreRegisteredUser::findOrFail($id);

    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'email' => 'sometimes|required|email|unique:users,email,' . $preUser->id,
        'position' => 'sometimes|required|string|max:255',
        'department' => 'sometimes|required|string|max:255',
        'layer' => 'sometimes|required|string|max:255',
        'status' => 'sometimes|required|in:active,pause,stop',
    ]);

       // تحديث بيانات المستخدم في جدول PreRegisteredUser
    $preUser->update($validated);

    // البحث عن نفس المستخدم في جدول users عن طريق الايميل القديم (قبل التحديث)
    $oldEmail = $preUser->getOriginal('email');

    // اذا تم تحديث الايميل في البيانات الجديدة، نستخدم الايميل الجديد، والا نستخدم القديم
    $newEmail = $validated['email'] ?? $oldEmail;

    // البحث والتحديث في جدول users اذا موجود
    $user = User::where('email', $oldEmail)->first();

    if ($user) {
        // تحديث بيانات المستخدم في جدول users مع الانتباه ان ايميل المستخدم في users ممكن يتغير
        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'email' => $newEmail,
            'position' => $validated['position'] ?? $user->position,
            'department' => $validated['department'] ?? $user->department,
            'layer' => $validated['layer'] ?? $user->layer,
        ]);
    }

    return response()->json([
        'message' => 'User updated successfully in both preregistered and users tables',
        'pre_registered_user' => $preUser,
        'user' => $user ?? null
    ]);
}


   

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'position' => 'required|string|max:255',
        'department' => 'required|string|max:255',
        'layer' => 'required|string|max:255',
        'status' => 'required|in:active,pause,stop',
    ]);

    // توليد كلمة سر عشوائية (مثلاً 10 حروف أرقام)
    $randomPassword = Str::random(10);

    $user =  PreRegisteredUser::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($randomPassword),
        'position' => $validated['position'],
        'department' => $validated['department'],
        'layer' => $validated['layer'],
        'status' => $validated['status'],
    ]);


    return response()->json([
        'message' => 'User created successfully',
        'user' => $user,
        'generated_password' => $randomPassword 
    ], 201);
}

public function destroy($id)
{
     // حذف المستخدم من جدول PreRegisteredUser
    $preUser = PreRegisteredUser::findOrFail($id);
    $email = $preUser->email;
    $preUser->delete();

    // البحث عن نفس المستخدم في جدول users حسب الإيميل
    $user = User::where('email', $email)->first();

    if ($user) {
        $user->delete();
    }

    return response()->json([
        'message' => 'User deleted successfully from preregistered and users tables if existed'
    ]);
}



}
