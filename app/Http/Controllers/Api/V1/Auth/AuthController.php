<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Requests\V1\Auth\RefreshTokenRequest;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Http\Requests\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\V1\Auth\VerifyEmailRequest;
use App\Http\Resources\V1\Auth\UserResource;
use App\Services\V1\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
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
            $result = $this->authService->login(
                $request->only(['email', 'phone', 'password']),
                $request->input('device_name')
            );
            
            // Ensure refresh_token exists in result
            if (!isset($result['tokens']['refresh_token']) || empty($result['tokens']['refresh_token'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate refresh token.',
                ], 500);
            }
            
            $refreshCookie = $this->authService->createRefreshTokenCookie($result['tokens']['refresh_token']);

            $response = response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'user' => new UserResource($result['user']->load('ownerships.settings')),
                    'current_ownership_uuid' => $result['default_ownership_uuid'],
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
     */
    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        try {
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
     * Send password reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status !== Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to send reset link.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reset link.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => \Illuminate\Support\Facades\Hash::make($password),
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

