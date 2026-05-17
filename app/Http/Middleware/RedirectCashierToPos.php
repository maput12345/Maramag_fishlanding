<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectCashierToPos
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->isCashier()) {
            return redirect()
                ->route('broker.transaction', ['pos' => 1])
                ->with('info', 'Cashier staff can only access the POS transaction screen.');
        }

        return $next($request);
    }
}
