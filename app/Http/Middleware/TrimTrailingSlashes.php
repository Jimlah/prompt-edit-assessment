<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Canonicalises URLs for GET requests so /dashboard/ resolves the same as
 * /dashboard. Laravel's router treats a trailing slash as a different path
 * and would answer with a 404 instead.
 *
 * @internal
 */
final class TrimTrailingSlashes
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        if ($request->isMethod('GET')
            && $path !== '/'
            && str_ends_with($path, '/')
            && ! str_ends_with($path, '//')) {
            return redirect()->to(rtrim($path, '/').($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
        }

        return $next($request);
    }
}
