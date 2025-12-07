<?php

namespace Tests\Feature\V1\Auth;

use App\Models\V1\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake notifications to prevent actual email sending
        Notification::fake();
        
        // Seed permissions and roles (but not users - we'll create test users)
        $this->seed([
            \Database\Seeders\V1\Auth\PermissionSeeder::class,
            \Database\Seeders\V1\Auth\RoleSeeder::class,
        ]);
    }

    /**
     * Test user registration.
     */
    public function test_user_can_register(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'first' => 'Test',
            'last' => 'User',
            'type' => 'owner',
            'device_name' => 'Test Device',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'uuid',
                        'email',
                        'first',
                        'last',
                    ],
                    'tokens' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                    ],
                ],
            ])
            ->assertCookie('refresh_token');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test registration validation.
     */
    public function test_registration_requires_valid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test registration requires password confirmation.
     */
    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test user can login with email.
     */
    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'tokens' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                    ],
                ],
            ])
            ->assertCookie('refresh_token');

        $this->assertNotNull($response->json('data.tokens.access_token'));
    }

    /**
     * Test user can login with phone.
     */
    public function test_user_can_login_with_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
            'password' => Hash::make('Password123!'),
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '+1234567890',
            'password' => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'tokens',
                ],
            ])
            ->assertCookie('refresh_token');
    }

    /**
     * Test login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test login fails for inactive user.
     */
    public function test_login_fails_for_inactive_user(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test token refresh.
     */
    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'active' => true,
        ]);

        // Login first to get tokens
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $loginResponse->assertStatus(200);
        
        // Get refresh token from cookie
        $cookies = $loginResponse->headers->getCookies();
        $this->assertNotEmpty($cookies, 'Login should set refresh_token cookie');
        
        $refreshTokenCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'refresh_token') {
                $refreshTokenCookie = $cookie;
                break;
            }
        }
        
        $this->assertNotNull($refreshTokenCookie, 'refresh_token cookie should be set');
        $refreshToken = $refreshTokenCookie->getValue();
        $this->assertNotEmpty($refreshToken, 'Refresh token should not be empty');

        // Refresh token - chain withCookie properly
        $response = $this->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/api/v1/auth/refresh', []);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'tokens' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                    ],
                ],
            ])
            ->assertCookie('refresh_token');

        // Verify new access token is different
        $this->assertNotEquals(
            $loginResponse->json('data.tokens.access_token'),
            $response->json('data.tokens.access_token')
        );
    }

    /**
     * Test refresh fails without cookie.
     */
    public function test_refresh_fails_without_cookie(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', []);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Refresh token not found.',
            ]);
    }

    /**
     * Test refresh fails with invalid token.
     */
    public function test_refresh_fails_with_invalid_token(): void
    {
        $response = $this->withCookie('refresh_token', 'invalid-token')
            ->postJson('/api/v1/auth/refresh', []);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test user can logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'active' => true,
        ]);

        // Login first
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $accessToken = $loginResponse->json('data.tokens.access_token');
        $cookies = $loginResponse->headers->getCookies();
        $refreshToken = $cookies[0]->getValue();

        // Logout
        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->withCookie('refresh_token', $refreshToken)
            ->postJson('/api/v1/auth/logout', []);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ])
            ->assertCookieExpired('refresh_token');

        // Verify token is revoked
        $this->assertNull(
            PersonalAccessToken::findToken($accessToken)
        );
    }

    /**
     * Test user can logout from all devices.
     */
    public function test_user_can_logout_from_all_devices(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'active' => true,
        ]);

        // Login multiple times
        $login1 = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'device_name' => 'Device 1',
        ]);

        $login2 = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'device_name' => 'Device 2',
        ]);

        $accessToken1 = $login1->json('data.tokens.access_token');
        $accessToken2 = $login2->json('data.tokens.access_token');

        // Logout from all devices
        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken1)
            ->postJson('/api/v1/auth/logout-all', []);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out from all devices successfully.',
            ])
            ->assertCookieExpired('refresh_token');

        // Verify all tokens are revoked
        $this->assertNull(PersonalAccessToken::findToken($accessToken1));
        $this->assertNull(PersonalAccessToken::findToken($accessToken2));
    }

    /**
     * Test get authenticated user.
     */
    public function test_user_can_get_authenticated_user(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'active' => true,
        ]);

        // Login
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $accessToken = $loginResponse->json('data.tokens.access_token');

        // Get authenticated user
        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'uuid',
                    'email',
                    'first',
                    'last',
                ],
            ])
            ->assertJson([
                'data' => [
                    'email' => 'test@example.com',
                ],
            ]);
    }

    /**
     * Test get authenticated user requires authentication.
     */
    public function test_get_authenticated_user_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test email verification.
     */
    public function test_user_can_verify_email(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $hash = sha1($user->getEmailForVerification());

        $response = $this->getJson("/api/v1/auth/verify-email/{$user->id}/{$hash}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email verified successfully.',
            ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /**
     * Test email verification fails with invalid hash.
     */
    public function test_email_verification_fails_with_invalid_hash(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->getJson("/api/v1/auth/verify-email/{$user->id}/invalid-hash");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid verification link.',
            ]);
    }

    /**
     * Test resend email verification.
     */
    public function test_user_can_resend_email_verification(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => null,
            'active' => true,
        ]);

        // Login first
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $accessToken = $loginResponse->json('data.tokens.access_token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson('/api/v1/auth/resend-verification', []);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Verification email sent successfully.',
            ]);
    }

    /**
     * Test resend verification fails if already verified.
     */
    public function test_resend_verification_fails_if_already_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'email_verified_at' => now(),
            'active' => true,
        ]);

        // Login first
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'device_name' => 'Test Device',
        ]);

        $accessToken = $loginResponse->json('data.tokens.access_token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson('/api/v1/auth/resend-verification', []);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Email already verified.',
            ]);
    }

    /**
     * Test forgot password.
     */
    public function test_user_can_request_password_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset link sent to your email.',
            ]);
    }

    /**
     * Test forgot password requires valid email.
     */
    public function test_forgot_password_requires_valid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test reset password.
     */
    public function test_user_can_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = \Illuminate\Support\Facades\Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successfully.',
            ]);

        // Verify password was changed
        $this->assertTrue(
            Hash::check('NewPassword123!', $user->fresh()->password)
        );
    }

    /**
     * Test reset password requires valid token.
     */
    public function test_reset_password_requires_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Unable to reset password.',
            ]);
    }

    /**
     * Test reset password requires password confirmation.
     */
    public function test_reset_password_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = \Illuminate\Support\Facades\Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'test@example.com',
            'password' => 'NewPassword123!',
            'token' => $token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}

