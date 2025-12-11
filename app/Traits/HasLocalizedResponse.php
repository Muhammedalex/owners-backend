<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait HasLocalizedResponse
{
    /**
     * Return a success JSON response with localized message.
     */
    protected function successResponse(
        mixed $data = null,
        ?string $messageKey = null,
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
        ];

        if ($messageKey) {
            $response['message'] = __($messageKey);
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response with localized message.
     */
    protected function errorResponse(
        string $messageKey,
        int $statusCode = 400,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => __($messageKey),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a not found JSON response.
     */
    protected function notFoundResponse(string $resourceKey = 'messages.errors.not_found'): JsonResponse
    {
        return $this->errorResponse($resourceKey, 404);
    }

    /**
     * Return an unauthorized JSON response.
     */
    protected function unauthorizedResponse(string $messageKey = 'messages.errors.unauthorized'): JsonResponse
    {
        return $this->errorResponse($messageKey, 401);
    }

    /**
     * Return a forbidden JSON response.
     */
    protected function forbiddenResponse(string $messageKey = 'messages.errors.forbidden'): JsonResponse
    {
        return $this->errorResponse($messageKey, 403);
    }
}

