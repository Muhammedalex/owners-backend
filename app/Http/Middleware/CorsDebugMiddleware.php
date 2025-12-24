<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsDebugMiddleware
{
    /**
     * Debug CORS and Output Buffering Issues
     */
    public function handle(Request $request, Closure $next): Response
    {
        // فقط للـ API routes
        if (!$request->is('api/*')) {
            return $next($request);
        }

        // 1. Check if headers already sent
        $headersSent = headers_sent($file, $line);
        
        // 2. Check output buffering level
        $obLevel = ob_get_level();
        $obContents = '';
        if ($obLevel > 0) {
            $obContents = ob_get_contents();
        }

        // 3. Get current headers list
        $currentHeaders = [];
        if (function_exists('getallheaders')) {
            $currentHeaders = getallheaders();
        }

        // 4. Log debug info
        \Log::info('CORS Debug Info', [
            'path' => $request->path(),
            'method' => $request->method(),
            'origin' => $request->header('Origin'),
            'headers_sent' => $headersSent,
            'headers_sent_file' => $file ?? null,
            'headers_sent_line' => $line ?? null,
            'ob_level' => $obLevel,
            'ob_has_content' => !empty($obContents),
            'ob_content_length' => strlen($obContents),
            'ob_content_preview' => substr($obContents, 0, 200),
            'current_headers_count' => count($currentHeaders),
        ]);

        // 5. Check for any output before headers
        if ($obLevel > 0 && !empty($obContents)) {
            \Log::warning('Output buffer has content before CORS headers!', [
                'content' => substr($obContents, 0, 500),
                'length' => strlen($obContents),
            ]);
        }

        // 6. Check if headers were already sent
        if ($headersSent) {
            \Log::error('Headers already sent!', [
                'file' => $file,
                'line' => $line,
            ]);
        }

        // Process request
        $response = $next($request);

        // 7. Check CORS headers in response
        $corsHeaders = [
            'Access-Control-Allow-Origin' => $response->headers->get('Access-Control-Allow-Origin'),
            'Access-Control-Allow-Methods' => $response->headers->get('Access-Control-Allow-Methods'),
            'Access-Control-Allow-Headers' => $response->headers->get('Access-Control-Allow-Headers'),
            'Access-Control-Allow-Credentials' => $response->headers->get('Access-Control-Allow-Credentials'),
            'Access-Control-Max-Age' => $response->headers->get('Access-Control-Max-Age'),
        ];

        \Log::info('CORS Headers in Response', [
            'headers' => $corsHeaders,
            'all_headers' => $response->headers->all(),
        ]);

        // 8. Check if CORS headers are missing
        $missingHeaders = [];
        if (empty($corsHeaders['Access-Control-Allow-Origin'])) {
            $missingHeaders[] = 'Access-Control-Allow-Origin';
        }
        if (empty($corsHeaders['Access-Control-Allow-Credentials'])) {
            $missingHeaders[] = 'Access-Control-Allow-Credentials';
        }

        if (!empty($missingHeaders)) {
            \Log::error('Missing CORS Headers!', [
                'missing' => $missingHeaders,
                'request_origin' => $request->header('Origin'),
            ]);
        }

        return $response;
    }
}

