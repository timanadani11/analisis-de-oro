<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, \Closure $next)
    {
        // Log CSRF token information for debugging
        if ($request->is('admin/api/action/save-matches') || $request->is('football-api/*')) {
            Log::info('CSRF Debug', [
                'uri' => $request->path(),
                'method' => $request->method(),
                'has_csrf_token_header' => $request->hasHeader('X-CSRF-TOKEN') ? 'Yes' : 'No',
                'has_xsrf_token_header' => $request->hasHeader('X-XSRF-TOKEN') ? 'Yes' : 'No',
                'has_csrf_token_input' => $request->has('_token') ? 'Yes' : 'No',
                'token_matches' => $this->tokensMatch($request) ? 'Yes' : 'No',
            ]);
        }

        return parent::handle($request, $next);
    }
} 