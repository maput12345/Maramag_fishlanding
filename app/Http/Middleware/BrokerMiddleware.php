<?php

namespace App\Http\Middleware;

use App\Models\Broker;
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
        if (!auth()->check()) {
            abort(403, 'Access denied. Broker privileges required.');
        }

        if (auth()->user()->isBroker()) {
            return $next($request);
        }

        if (auth()->user()->isCashier()) {
            $allowedRoutes = [
                'broker.transaction',
                'broker.sales.sales',
                'broker.sales.store',
                'broker.sales.update',
                'broker.sales-payments.store',
                'broker.fish-boxes.qr',
                'broker.sales.scan-sessions.store',
                'broker.sales.scan-sessions.scanner',
                'broker.sales.scan-sessions.scan',
                'broker.sales.scan-sessions.items',
                'broker.sales.scan-sessions.close',
            ];

            if ($request->routeIs(...$allowedRoutes)) {
                return $next($request);
            }

            return redirect()
                ->route('broker.transaction')
                ->with('info', 'Cashier staff can only access POS and their own transactions.');
        }

        if (auth()->user()->isAdmin()) {
            if (Broker::isAdminImpersonatingBroker(auth()->user())) {
                $isReadOnlyBrokerView = Broker::isAdminBrokerViewReadOnly(auth()->user());
                $isSafeMethod = in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true);

                if ($isReadOnlyBrokerView && !$isSafeMethod) {
                    $message = 'Broker view is read-only. Enable Support Actions before making broker changes.';

                    if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $message,
                        ], 403);
                    }

                    $redirectTarget = $request->headers->get('referer') ?: route('broker.dashboard');

                    return redirect()
                        ->to($redirectTarget)
                        ->with('error', $message);
                }

                return $next($request);
            }

            return redirect()
                ->route('admin.users.index', ['tab' => 'brokers'])
                ->with('info', 'Select a broker from User Management to enter broker view.');
        }

        abort(403, 'Access denied. Broker privileges required.');
    }
}
