<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BrokerMiddleware
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
        if (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isBroker())) {
            return $next($request);
        }

        abort(403, 'Access denied. Broker privileges required.');
    }
}
