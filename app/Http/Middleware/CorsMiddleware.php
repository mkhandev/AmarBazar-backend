<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {

        $allowedOrigins = [
            'http://localhost:3000',
            'https://amar-bazar-phi.vercel.app',
        ];

        $origin = $request->headers->get('origin');

        $headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ];

        if (in_array($origin, $allowedOrigins)) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        }

        // Handle preflight requests globally
        if ($request->getMethod() === 'OPTIONS') {
            return response()->json('OK aaa', 200, $headers);
        }

        // print_r($request->getMethod());
        // exit;

        $response = $next($request);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
