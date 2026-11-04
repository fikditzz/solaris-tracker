<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDevToolsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('tracker.dev_tools')) {
            return response()->json([
                'success' => false,
                'message' => 'Developer tools are disabled on this server.',
            ], 403);
        }

        return $next($request);
    }
}
