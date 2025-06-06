<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PreRegisteredUser;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

use Illuminate\Validation\ValidationException;
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
        // 'position' => 'required|in:Head,CoHead,Senior leader,Junior leader,Volunteer',
        // 'department' => 'required|in:IT&AI,Research,Design,Admin,Education,Media,Fundrising',
        // 'layer' => 'required|in:public health,resources management,economic factor,urban planning,ecological factor,social factor,building code,Culture and heritage,technology and infrastructure,data collection and analysis'
    ]);

    // Check if user already exists
    if (User::where('email', $request->email)->exists()) {
        return response()->json(['message' => 'User already registered with this email.'], 409);
    }

     // Check datasheet for employee details
     $employeeData = PreRegisteredUser::where('email', $request->email)->first();

     if (!$employeeData) {
        // الموظف غير موجود في البيانات المسبقة، يمنع التسجيل
        return response()->json(['message' => 'Voulnteer data not found. Registration is not allowed.'], 422);
    }

    $position = $employeeData->position;
    $department = $employeeData->department;
    $layer = $employeeData->layer;

    Log::info('Voulnteer found in datasheet', ['email' => $request->email, 'data' => $employeeData]);

    $verificationCode = rand(100000, 999999); // 6-digit random code

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'position' => $position,
        'department' => $department,
        'layer' => $layer,
        'is_verified' => false,
    ]);

    // // Store verification code in cache with 10 minutes expiration
    // Cache::put('verification_code_' . $user->id, $verificationCode, now()->addMinutes(10));
   

    // // Send email
    // Mail::raw('Your verification code is: ' . $verificationCode, function ($message) use ($user) {
    //     $message->to($user->email)
    //             ->subject('Verify Your Email');
    // });

    Log::info('User Registered Successfully:', ['user' => $user]);

    // $responseMessage = 'User registered successfully. Please check your email for the verification code.';
    return response()->json([
        'message' => 'User registered successfully.',
        'user_data' => [
            'position' => $position,
            'department' => $department,
            'layer' => $layer
        ]
    ], 201);
}

// Alternative method: Check employee data before registration
public function checkEmployee(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $employeeData = PreRegisteredUser::where('email', $request->email)->first();

    if ($employeeData) {
        return response()->json([
            'found' => true,
            'data' => [
                'position' => $employeeData->position,
                'department' => $employeeData->department,
                'layer' => $employeeData->layer
            ]
        ]);
    }

    return response()->json([
        'found' => false,
        'message' => 'Email not found in company database. Please provide your details manually.'
    ]);
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

    // Method to import datasheet (CSV/Excel) to database
    public function importDatasheet(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        
        if (($handle = fopen($path, 'r')) !== FALSE) {
            $header = fgetcsv($handle, 1000, ','); // Get header row
            $imported = 0;
            $errors = [];
            
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                try {
                    $row = array_combine($header, $data);
                    
                    PreRegisteredUser::updateOrCreate(
                        ['Email' => $row['email']],
                        [
                            'Your Position' => $row['position'],
                            'DEPARTMENT' => $row['department'],
                            'Layer' => $row['layer']
                        ]
                    );
                    $imported++;
                } catch (Exception $e) {
                    $errors[] = "Error importing row: " . implode(',', $data) . " - " . $e->getMessage();
                }
            }
            fclose($handle);
            
            return response()->json([
                'message' => "Successfully imported {$imported} records.",
                'errors' => $errors
            ]);
        }
        
        return response()->json(['message' => 'Failed to read file.'], 400);
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

         $user = User::where('email', $request->email)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    
        // if (!Auth::attempt($credentials, $remember)) {
        //     throw ValidationException::withMessages([
        //         'email' => ['The provided credentials are incorrect.'],
        //     ]);
        // }
    
        // $user = Auth::user();
    
        // if (!$user->is_verified) {
        //     Auth::logout();
        //     return response()->json([
        //         'message' => 'Please verify your email before logging in.'
        //     ], 403);
        // }
    
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
 



}
