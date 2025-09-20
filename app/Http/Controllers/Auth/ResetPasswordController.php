<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;


class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    // use ResetsPasswords;

    // /**
    //  * Where to redirect users after resetting their password.
    //  *
    //  * @var string
    //  */
    // protected $redirectTo = '/home';

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = hash('sha256', Str::random(60));

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );

        $resetUrl = config('app.frontend_url') . "/reset-password?token=$token&email={$request->email}";

        Mail::raw("Reset your password: $resetUrl", function ($message) use ($request) {
            $message->to($request->email)->subject('Reset Password');
        });

        return response()->json(['message' => 'Reset link sent']);
    }


    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:App\Models\User,email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->where('created_at', '>', now()->subMinutes(60))
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired token'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // حذف التوكن بعد الاستخدام
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password updated']);
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->rest_password) {
            return response()->json(['message' => 'User already verified.'], 400);
        }

        $newCode = rand(100000, 999999);

        // Store the new code in the cache with a new expiration time
        Cache::put('reset_password_code_' . $user->id, $newCode, 60); // 1 minutes expiration

        Mail::raw('Your new reset password code is: ' . $newCode, function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Send reset password Code');
        });

        return response()->json(['message' => 'A reset password code has been sent to your email.'], 200);
    }

    public function verifyCode(Request $request)
    {
        // Custom validation rules
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'reset_password_code' => 'required|string|size:6', // Example: 6 digits code
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422); // 422 Unprocessable Entity
        }

        // Find the user by email
        $user = User::where('email', $request->email)->first();

//        $cachedCode = Cache::get('reset_password_code_' . $user->id);
//
//         // Check if the verification code matches
//         if ($cachedCode && $cachedCode == $request->reset_password_code) {
//             $user->rest_password = true;
//             $user->save();
//
//             Cache::forget('reset_password_code_' . $user->id);
//
//             $token = $user->createToken('auth_token')->plainTextToken;
//
//
//             return response()->json([
//                 'message' => 'Verification successful!',
//                 'access_token' => $token,
//                 'user' => $user
//             ], 200); // Success
//         }
//
//         // If verification fails
//         return response()->json([
//             'message' => 'Invalid verification code.'
//         ], 400); // Bad request

        $user->rest_password = true;
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Verification successful!',
            'access_token' => $token,
            'user' => $user
        ], 200); // Success
    }
}


