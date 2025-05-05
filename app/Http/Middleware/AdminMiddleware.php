<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated and is an admin
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Unauthorized. Please log in as an administrator.',
                    'code' => 'unauthorized'
                ], 401);
            }
            
            // Store the intended URL if it's a GET request
            if ($request->isMethod('get')) {
                session()->put('url.intended', $request->url());
            }
            
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
