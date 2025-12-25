<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Requests\V1\Auth\RefreshTokenRequest;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Http\Requests\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\V1\Auth\VerifyEmailRequest;
use App\Http\Requests\V1\Auth\VerifyOtpRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Services\V1\Auth\AuthService;
use App\Services\V1\Auth\OtpService;
use App\Repositories\V1\Auth\Interfaces\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private OtpService $otpService,
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());
            $cookie = $this->authService->createRefreshTokenCookie($result['tokens']['refresh_token']);

            $message = config('auth.verification.enabled', false)
                ? 'Registration successful. Please verify your email.'
                : 'Registration successful.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'user' => new UserResource($result['user']->load('ownerships.settings')),
                    'tokens' => [
                        'access_token' => $result['tokens']['access_token'],
                        'token_type' => $result['tokens']['token_type'],
                        'expires_in' => $result['tokens']['expires_in'],
                    ],
                ],
            ], 201)->withCookie($cookie);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $phone = $request->input('phone');
            $email = $request->input('email');
            $password = $request->input('password');
            $otp = $request->input('otp');
            $sessionId = $request->input('session_id');

            // If OTP is provided, verify and login with OTP
            if ($otp && $sessionId) {
                $identifier = $phone ?? $email;
                if (!$identifier) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phone or email is required for OTP login.',
                    ], 422);
                }

                // Verify OTP
                $verified = $this->otpService->verify(
                    phone: $phone,
                    email: $email,
                    otp: $otp,
                    purpose: 'login',
                    sessionId: $sessionId
                );

                if (!$verified) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired OTP.',
                    ], 401);
                }

                // Login with OTP (no password required)
                $result = $this->authService->loginWithOtp(
                    phone: $phone,
                    email: $email,
                    deviceName: $request->input('device_name')
                );
            }
            // If phone is provided without password, send OTP instead
            elseif ($phone && !$password) {
                // Generate and send OTP
                $result = $this->otpService->generateAndSend(
                    phone: $phone,
                    email: null,
                    purpose: 'login'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent to your phone. Please verify to continue.',
                    'data' => $result, // Contains session_id
                    'requires_otp' => true,
                ]);
            }
            // Normal login with password
            else {
                $result = $this->authService->login(
                    $request->only(['email', 'phone', 'password']),
                    $request->input('device_name')
                );
            }
            
            // Ensure refresh_token exists in result
            if (!isset($result['tokens']['refresh_token']) || empty($result['tokens']['refresh_token'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate refresh token.',
                ], 500);
            }
            
            $refreshCookie = $this->authService->createRefreshTokenCookie($result['tokens']['refresh_token']);

            $user = $result['user']->load('ownerships.settings');
            $response = response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'user' => new UserResource($user),
                    'current_ownership_uuid' => $result['default_ownership_uuid'],
                    'email_verified' => $user->hasVerifiedEmail(),
                    'tokens' => [
                        'access_token' => $result['tokens']['access_token'],
                        'token_type' => $result['tokens']['token_type'],
                        'expires_in' => $result['tokens']['expires_in'],
                    ],
                ],
            ])->withCookie($refreshCookie);

            // Set ownership cookie if default ownership exists
            if (isset($result['default_ownership_uuid']) && $result['default_ownership_uuid']) {
                $ownershipCookie = $this->authService->createOwnershipCookie($result['default_ownership_uuid']);
                $response = $response->withCookie($ownershipCookie);
            }

            return $response;
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Refresh access token.
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            // Get refresh token from cookie
            $refreshToken = $request->cookie('refresh_token');

            if (!$refreshToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refresh token not found.',
                ], 401);
            }

            $result = $this->authService->refreshToken($refreshToken);
            $refreshCookie = $this->authService->createRefreshTokenCookie($result['tokens']['refresh_token']);

            // Check if ownership cookie already exists
            $existingOwnershipCookie = $request->cookie('ownership_uuid');
            $currentOwnershipUuid = $existingOwnershipCookie;

            // Only get default ownership UUID if no cookie exists and user is not Super Admin
            if (!$existingOwnershipCookie && !$result['user']->isSuperAdmin()) {
                $defaultOwnership = $result['user']->getDefaultOwnership();
                if ($defaultOwnership) {
                    $currentOwnershipUuid = $defaultOwnership->uuid;
                }
            }

            $response = response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully.',
                'data' => [
                    'user' => new UserResource($result['user']->load('ownerships.settings')),
                    'current_ownership_uuid' => $currentOwnershipUuid,
                    'tokens' => [
                        'access_token' => $result['tokens']['access_token'],
                        'token_type' => $result['tokens']['token_type'],
                        'expires_in' => $result['tokens']['expires_in'],
                    ],
                ],
            ])->withCookie($refreshCookie);

            // Set ownership cookie only if no cookie already exists and we have a default ownership
            if (!$existingOwnershipCookie && $currentOwnershipUuid) {
                $ownershipCookie = $this->authService->createOwnershipCookie($currentOwnershipUuid);
                $response = $response->withCookie($ownershipCookie);
            }

            return $response;
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            $cookie = $this->authService->clearRefreshTokenCookie();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401)->withCookie($cookie);
        }
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $refreshToken = $request->cookie('refresh_token');
            $this->authService->logout($refreshToken);
            $refreshCookie = $this->authService->clearRefreshTokenCookie();
            $ownershipCookie = $this->authService->clearOwnershipCookie();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.',
            ])->withCookie($refreshCookie)->withCookie($ownershipCookie);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout from all devices.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $this->authService->logoutAll();
            $refreshCookie = $this->authService->clearRefreshTokenCookie();
            $ownershipCookie = $this->authService->clearOwnershipCookie();

            return response()->json([
                'success' => true,
                'message' => 'Logged out from all devices successfully.',
            ])->withCookie($refreshCookie)->withCookie($ownershipCookie);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('ownerships.settings');
        $currentOwnershipUuid = $request->input('current_ownership_uuid'); // From ownership.scope middleware

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'current_ownership_uuid' => $currentOwnershipUuid,
            ],
        ]);
    }

    /**
     * Verify email.
     * Validates signed route parameters (signature and expires) before verifying.
     */
    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        try {
            // Validate signed route parameters
            $signature = $request->query('signature');
            $expires = $request->query('expires');
            
            if (!$signature || !$expires) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification link. Missing signature parameters.',
                ], 400);
            }
            
            // Check if link has expired
            if ((int) $expires < time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification link has expired. Please request a new one.',
                ], 400);
            }
            
            // Validate the signature
            // Reconstruct the URL path that was signed
            $urlPath = route('v1.auth.verify-email', [
                'id' => $id,
                'hash' => $hash,
            ], false);
            
            // Build the full URL with query parameters for signature validation
            $fullUrl = $request->schemeAndHttpHost() . $urlPath . '?' . http_build_query([
                'signature' => $signature,
                'expires' => $expires,
            ]);
            
            // Validate signature using Laravel's URL validation
            $validationRequest = Request::create($fullUrl, 'GET');
            if (!URL::hasValidSignature($validationRequest)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired verification link.',
                ], 400);
            }
            
            // Verify email
            $verified = $this->authService->verifyEmail($id, $hash);

            if (!$verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification link.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resend email verification.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified.',
                ], 400);
            }

            $this->authService->resendEmailVerification($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent successfully.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Send password reset OTP via Email or SMS.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $email = $request->input('email');
            $phone = $request->input('phone');

            // Generate and send OTP
            $result = $this->otpService->generateAndSend(
                phone: $phone,
                email: $email,
                purpose: 'forgot_password'
            );

            $message = $phone 
                ? 'Password reset OTP sent to your phone.'
                : 'Password reset OTP sent to your email.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify OTP before allowing password reset or login.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $email = $request->input('email');
            $phone = $request->input('phone');
            $otp = $request->input('otp');
            $sessionId = $request->input('session_id');
            $purpose = $request->input('purpose', 'forgot_password'); // Default to forgot_password for backward compatibility

            // Verify OTP
            $verified = $this->otpService->verify(
                phone: $phone,
                email: $email,
                otp: $otp,
                purpose: $purpose,
                sessionId: $sessionId
            );

            if (!$verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP.',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OTP verification failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password using OTP or Token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $email = $request->input('email');
            $phone = $request->input('phone');
            $otp = $request->input('otp');
            $token = $request->input('token');
            $password = $request->input('password');
            $sessionId = $request->input('session_id');

            // OTP-based reset (new method)
            if ($otp) {
                // Verify OTP (allow already verified OTPs since it was verified in verifyOtp endpoint)
                $verified = $this->otpService->verify(
                    phone: $phone,
                    email: $email,
                    otp: $otp,
                    purpose: 'forgot_password',
                    sessionId: $sessionId,
                    allowVerified: true
                );

                if (!$verified) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid or expired OTP.',
                    ], 401);
                }

                // Find user
                $user = $phone 
                    ? $this->userRepository->findByPhone($phone)
                    : $this->userRepository->findByEmail($email);

                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found.',
                    ], 404);
                }

                // Reset password
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Password reset successfully.',
                ]);
            }

            // Token-based reset (legacy email method)
            if ($token && $email) {
                $status = Password::reset(
                    $request->only('email', 'password', 'password_confirmation', 'token'),
                    function ($user, $password) {
                        $user->forceFill([
                            'password' => Hash::make($password),
                        ])->save();
                    }
                );

                if ($status !== Password::PASSWORD_RESET) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to reset password.',
                    ], 400);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Password reset successfully.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Either OTP or token must be provided.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

