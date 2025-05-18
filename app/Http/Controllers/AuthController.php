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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;


class AuthController extends Controller
{
    use HasApiTokens;

    // تسجيل مستخدم جديد
    
    
public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|unique:users',
        'password' => 'required|string|min:6|confirmed',
        'position' => 'required|in:Head,CoHead,Senior leader,Junior leader,Volunteer',
        'department' => 'required|in:IT&AI,Research,Design,Admin,Education,Media,Fundrising',
        'layer' => 'required|in:public health,resources management,economic factor,urban planning,ecological factor,social factor,building code,Culture and heritage,technology and infrastructure,data collection and analysis'
    ]);

    $verificationCode = rand(100000, 999999); // 6-digit random code

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'position' => $request->position,
        'department' => $request->department,
        'layer' => $request->layer,
        'is_verified' => false,
    ]);

    // Store verification code in cache with 10 minutes expiration
    Cache::put('verification_code_' . $user->id, $verificationCode, now()->addMinutes(10));
   

    // Send email
    Mail::raw('Your verification code is: ' . $verificationCode, function ($message) use ($user) {
        $message->to($user->email)
                ->subject('Verify Your Email');
    });

    Log::info('User Registered Successfully:', ['user' => $user]);

    return response()->json(['message' => 'User registered successfully. Please check your email for the verification code.'], 201);
}

//v
public function verifyCode(Request $request)
{
    // Custom validation rules
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
        'verification_code' => 'required|string|size:6', // Example: 6 digits code
    ]);

    // If validation fails, return error response
    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422); // 422 Unprocessable Entity
    }

    // Find the user by email
    $user = User::where('email', $request->email)->first();

    // Retrieve the verification code from cache
    $cachedCode = Cache::get('verification_code_' . $user->id);

    // Check if the verification code matches
    if ($cachedCode && $cachedCode == $request->verification_code) {
        // Code is valid, mark the user as verified
        $user->is_verified = true;
        $user->save();

        // Remove the verification code from cache
        Cache::forget('verification_code_' . $user->id);

        return response()->json([
            'message' => 'Verification successful!'
        ], 200); // Success
    }

    // If verification fails
    return response()->json([
        'message' => 'Invalid verification code.'
    ], 400); // Bad request
}

public function resendCode(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $user = User::where('email', $request->email)->first();

    if ($user->is_verified) {
        return response()->json(['message' => 'User already verified.'], 400);
    }

    $newCode = rand(100000, 999999);

    // Store the new code in the cache with a new expiration time
    Cache::put('verification_code_' . $user->id, $newCode, 60); // 1 minutes expiration

    Mail::raw('Your new verification code is: ' . $newCode, function ($message) use ($user) {
        $message->to($user->email)
                ->subject('Resend Verification Code');
    });

    return response()->json(['message' => 'A new verification code has been sent to your email.'], 200);
}



    // تسجيل الدخول
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'sometimes|boolean'
        ]);
    
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');
    
        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
    
        $user = Auth::user();
    
        if (!$user->is_verified) {
            Auth::logout();
            return response()->json([
                'message' => 'Please verify your email before logging in.'
            ], 403);
        }
    
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
