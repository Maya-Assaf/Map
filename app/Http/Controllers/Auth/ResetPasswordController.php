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
use App\Mail\ResetPasswordCodeMail;
use App\Mail\PasswordResetLinkMail;


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

        Mail::to($request->email)->send(new PasswordResetLinkMail($resetUrl));

        return response()->json(['message' => 'Reset link sent']);
    }


    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:App\Models\User,email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->where('created_at', '>', now()->subMinutes(60))
            ->first();

        $cachedCode = Cache::get('reset_password_code_' . $user->id) ?? Cache::get('reset_password_code_' . $request->email);
        $isCodeValid = $cachedCode && ((string) $cachedCode === (string) $request->token);

        if (!$record && !$isCodeValid) {
            return response()->json(['message' => 'Invalid or expired token'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->rest_password = false;
        $user->save();

        if ($record) {
            DB::table('password_resets')->where('email', $request->email)->delete();
        }
        if ($isCodeValid) {
            Cache::forget('reset_password_code_' . $user->id);
            Cache::forget('reset_password_code_' . $user->email);
        }

        return response()->json(['message' => 'Password updated']);
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        $newCode = (string) rand(100000, 999999);

        // Store the new code in the cache with a 15 minutes expiration time
        Cache::put('reset_password_code_' . $user->id, $newCode, now()->addMinutes(15));
        Cache::put('reset_password_code_' . $user->email, $newCode, now()->addMinutes(15));

        Mail::to($user->email)->send(new ResetPasswordCodeMail($newCode, $user->name));

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

        $cachedCode = Cache::get('reset_password_code_' . $user->id) ?? Cache::get('reset_password_code_' . $user->email);

        // Check if the verification code matches
        if ($cachedCode && (string) $cachedCode === (string) $request->reset_password_code) {
            $user->rest_password = true;
            $user->save();

            Cache::forget('reset_password_code_' . $user->id);
            Cache::forget('reset_password_code_' . $user->email);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Verification successful!',
                'access_token' => $token,
                'user' => $user
            ], 200); // Success
        }

        // If verification fails
        return response()->json([
            'message' => 'Invalid verification code.'
        ], 400); // Bad request
    }
}


