<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Contoh: hanya admin yang boleh akses, sesuaikan logikanya
        if ($request->user() && $request->user()->role === 'admin') {
            return $next($request);
        }
        return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
    }
}
