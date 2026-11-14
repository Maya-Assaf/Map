<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PreRegisteredUser;
use App\Mail\EmailVerificationMail;
use App\Mail\ResetPasswordCodeMail;
use App\Mail\PasswordResetLinkMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class AuthEmailVerificationAndQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_dispatches_email_verification_mail_and_caches_code()
    {
        Mail::fake();

        PreRegisteredUser::create([
            'name' => 'Ahmad Test',
            'email' => 'ahmad@reparametrize.org',
            'department' => 'IT & AI DEPARTMENT',
            'position' => 'Head',
            'layer' => 'Other',
            'status' => 'Active',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Ahmad Test',
            'email' => 'ahmad@reparametrize.org',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'next_step' => 'verify_email',
            ]);

        $user = User::where('email', 'ahmad@reparametrize.org')->first();
        $this->assertNotNull($user);
        $this->assertFalse((bool) $user->is_verified);

        $cachedCode = Cache::get('verification_code_' . $user->id);
        $this->assertNotNull($cachedCode);
        $this->assertEquals(6, strlen($cachedCode));

        Mail::assertQueued(EmailVerificationMail::class, function ($mail) use ($user, $cachedCode) {
            return $mail->hasTo($user->email) && (string) $mail->code === (string) $cachedCode;
        });
    }

    public function test_verification_fails_with_invalid_or_expired_code()
    {
        $user = User::create([
            'name' => 'Sami Test',
            'email' => 'sami@reparametrize.org',
            'password' => Hash::make('secret123'),
            'position' => 'Junior',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'Other',
            'is_verified' => false,
        ]);

        Cache::put('verification_code_' . $user->id, '123456', now()->addMinutes(15));

        $response = $this->postJson('/api/verify-code', [
            'email' => $user->email,
            'verification_code' => '999999',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid verification code.'
            ]);

        $user->refresh();
        $this->assertFalse((bool) $user->is_verified);
    }

    public function test_verification_succeeds_with_correct_code()
    {
        $user = User::create([
            'name' => 'Kareem Test',
            'email' => 'kareem@reparametrize.org',
            'password' => Hash::make('secret123'),
            'position' => 'Junior',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'Other',
            'is_verified' => false,
        ]);

        Cache::put('verification_code_' . $user->id, '654321', now()->addMinutes(15));

        $response = $this->postJson('/api/verify-code', [
            'email' => $user->email,
            'verification_code' => '654321',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Verification successful!'
            ]);

        $user->refresh();
        $this->assertTrue((bool) $user->is_verified);
        $this->assertNotNull($user->email_verified_at);

        $this->assertNull(Cache::get('verification_code_' . $user->id));
    }

    public function test_resend_code_succeeds_and_dispatches_email()
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Lina Test',
            'email' => 'lina@reparametrize.org',
            'password' => Hash::make('secret123'),
            'position' => 'Junior',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'Other',
            'is_verified' => false,
        ]);

        $response = $this->postJson('/api/resend-code', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'A verification code has been sent to your email.'
            ]);

        $cachedCode = Cache::get('verification_code_' . $user->id);
        $this->assertNotNull($cachedCode);
        $this->assertEquals(6, strlen($cachedCode));

        Mail::assertQueued(EmailVerificationMail::class, function ($mail) use ($user, $cachedCode) {
            return $mail->hasTo($user->email) && (string) $mail->code === (string) $cachedCode;
        });
    }

    public function test_resend_code_fails_if_user_already_verified()
    {
        $user = User::create([
            'name' => 'Maya Test',
            'email' => 'maya@reparametrize.org',
            'password' => Hash::make('secret123'),
            'position' => 'Junior',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'Other',
            'is_verified' => true,
        ]);

        $response = $this->postJson('/api/resend-code', [
            'email' => $user->email,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'User already verified.'
            ]);
    }

    public function test_forgot_password_mob_sends_code_and_caches_it()
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Omar Test',
            'email' => 'omar@reparametrize.org',
            'password' => Hash::make('secret123'),
            'position' => 'Junior',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'Other',
            'is_verified' => true,
        ]);

        $response = $this->postJson('/api/forgot-password-for-mob', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'A reset password code has been sent to your email.'
            ]);

        $cachedCode = Cache::get('reset_password_code_' . $user->id);
        $this->assertNotNull($cachedCode);
        $this->assertEquals(6, strlen($cachedCode));

        Mail::assertQueued(ResetPasswordCodeMail::class, function ($mail) use ($user, $cachedCode) {
            return $mail->hasTo($user->email) && (string) $mail->code === (string) $cachedCode;
        });
    }

    public function test_verify_reset_code_for_mob_fails_with_invalid_code()
    {
        $user = User::create([
            'name' => 'Tarek Test',
            'email' => 'tarek@reparametrize.org',
            'password' => Hash::make('secret123'),
            'position' => 'Junior',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'Other',
            'is_verified' => true,
        ]);

        Cache::put('reset_password_code_' . $user->id, '112233', now()->addMinutes(15));

        $response = $this->postJson('/api/verify-reset-code-for-mob', [
            'email' => $user->email,
            'reset_password_code' => '999999',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid verification code.'
            ]);
    }

    public function test_verify_reset_code_for_mob_succeeds_with_correct_code()
    {
        $user = User::create([
            'name' => 'Salma Test',
            'email' => 'salma@reparametrize.org',
            'password' => Hash::make('secret123'),
            'position' => 'Junior',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'Other',
            'is_verified' => true,
        ]);

        Cache::put('reset_password_code_' . $user->id, '445566', now()->addMinutes(15));

        $response = $this->postJson('/api/verify-reset-code-for-mob', [
            'email' => $user->email,
            'reset_password_code' => '445566',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'user'
            ]);

        $user->refresh();
        $this->assertTrue((bool) $user->rest_password);
        $this->assertNull(Cache::get('reset_password_code_' . $user->id));
    }

    public function test_reset_password_succeeds_with_token()
    {
        $user = User::create([
            'name' => 'Hasan Test',
            'email' => 'hasan@reparametrize.org',
            'password' => Hash::make('old_secret123'),
            'position' => 'Junior',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'Other',
            'is_verified' => true,
        ]);

        $token = 'sample-reset-token-12345';
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new_secret123',
            'password_confirmation' => 'new_secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password updated'
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('new_secret123', $user->password));
    }

    public function test_send_reset_link_dispatches_email()
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Nour Test',
            'email' => 'nour@reparametrize.org',
            'password' => Hash::make('secret123'),
            'position' => 'Junior',
            'department' => 'IT & AI DEPARTMENT',
            'layer' => 'Other',
            'is_verified' => true,
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Reset link sent'
            ]);

        Mail::assertQueued(PasswordResetLinkMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_queue_database_driver_queues_and_processes_jobs()
    {
        // Test that database queue table works and can store jobs
        DB::table('jobs')->truncate();

        // Push a test mail using the database queue connection
        config(['queue.default' => 'database']);
        Mail::to('queued-test@reparametrize.org')->send(new EmailVerificationMail('123789', 'Queue Tester'));

        // Assert job exists in jobs table
        $jobCount = DB::table('jobs')->count();
        $this->assertGreaterThan(0, $jobCount);

        // Run one job
        $this->artisan('queue:work', [
            '--once' => true,
        ])->assertExitCode(0);

        // Assert job was processed
        $remainingJobs = DB::table('jobs')->count();
        $this->assertEquals(0, $remainingJobs);
    }
}
