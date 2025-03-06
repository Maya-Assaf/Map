<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    // تسجيل مستخدم جديد
    public function register(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'position' => 'required|in:Head,CoHead,Senior leader,Junior leader,Volunteer',
            'department' => 'required|in:IT&AI,Research,Design,Admin,Education,Media,Fundrising',
            'layer' => 'required|in:public health,resources management,economic factor,urban planning,ecological factor,social factor,building code,Culture and heritage,technology and infrastructure,data collection and analysis'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'position' => $request->position,
            'department' => $request->department,
            'layer' => $request->layer,
        ]);

        Log::info('User Registered Successfully:', ['user' => $user]);
        return response()->json(['message' => 'User registered successfully'], 201);
    }

    // تسجيل الدخول
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'sometimes|boolean' // التأكد من صحة "تذكرني"
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember'); // الحصول على قيمة تذكرني

        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'user' => $user
        ]);
    }


    public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email'
    ]);

    $status = Password::sendResetLink($request->only('email'));

    return $status === Password::RESET_LINK_SENT
        ? response()->json(['message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.'])
        : response()->json(['message' => 'حدث خطأ أثناء إرسال الرابط.'], 500);
}


// use Illuminate\Support\Facades\Hash;

public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'token' => 'required|string',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();
        }
    );

    return $status === Password::PASSWORD_RESET
        ? response()->json(['message' => 'تمت إعادة تعيين كلمة المرور بنجاح.'])
        : response()->json(['message' => 'رمز إعادة التعيين غير صالح أو منتهي الصلاحية.'], 500);
}

    // تسجيل الخروج
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout successful']);
    }
 public function updateProfile(Request $request, $id)
{
    // Log the incoming request data
    Log::info('Request Data (form-data):', $request->all());

    // Find the user by ID
    $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    // Check if the user is trying to change their position
    if ($request->has('position')) {
        return response()->json(['message' => 'You are not allowed to change your position'], 403);
    }

    // Validation rules
    $rules = [
        'name'       => 'sometimes|string|max:255',
        'email'      => 'sometimes|email|unique:users,email,' . $user->id,
        'department' => 'sometimes|in:IT&AI,Research,Design,Admin,Education,Media,Fundrising',
        'layer'      => 'sometimes|in:public health,resources management,economic factor,urban planning,ecological factor,social factor,building code,Culture and heritage,technology and infrastructure,data collection and analysis',
        'position'   => 'prohibited', // Add this rule to explicitly prohibit the position field
    ];

    // Add password validation rules if password is being updated
    if ($request->filled('password')) {
        $rules['old_password'] = 'required|string';
        $rules['password']     = 'required|string|min:6|confirmed';
    }

    // Validate the request data
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        // Log validation errors
        Log::error('Validation Errors:', $validator->errors()->toArray());
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Get the validated data
    $validatedData = $validator->validated();

    // Log the validated data
    Log::info('Validated Data:', $validatedData);

    // Update password if provided
    if ($request->filled('password')) {
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Old password does not match'], 403);
        }
        $user->password = Hash::make($validatedData['password']);
        unset($validatedData['password']); // Remove password from validated data to avoid double update
        unset($validatedData['old_password']); // Remove old_password from validated data
    }

    // Log the user data before updating
    Log::info('User Before Update:', $user->toArray());

    // Update the user with the validated data
    $user->update($validatedData);

    // Log the user data after updating
    Log::info('User After Update:', $user->toArray());

    return response()->json([
        'message' => 'Profile updated successfully',
        'user'    => $user
    ], 200);
    }
     


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

}
