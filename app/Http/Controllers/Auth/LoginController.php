<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Constants\UserStatusConstant;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected function redirectTo()
    {
        $user = auth()->user();
        if ($user && ($user->isAdmin() || $user->isStaff())) {
            return route('admin.dashboard');
        }
        if ($user && $user->isBroker()) {
            return route('broker.dashboard');
        }

        return route('applications.index');
    }

    /**
     * Attempt to log the user into the application.
     * Blocks deactivated accounts with a specific error.
     */
    protected function attemptLogin(Request $request)
    {
        $user = User::where('email', $request->input('email'))
            ->first();

        if ($user && $user->status === UserStatusConstant::DEACTIVATED) {
            // Flag deactivated state for a custom failed response
            $request->session()->put('auth.deactivated', true);
            return false;
        }

        return $this->guard()->attempt(
            $this->credentials($request),
            $request->boolean('remember')
        );
    }

    /**
     * Get the failed login response instance.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        if ($request->session()->pull('auth.deactivated', false)) {
            throw ValidationException::withMessages([
                $this->username() => ['Your account is deactivated.'],
            ]);
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        if ($user->isAdmin() || $user->isStaff()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isBroker()) {
            return redirect()->route('broker.dashboard');
        }

        return redirect()->route('applications.index');
    }

    /**
     * Show the application's login form.
     */
    public function showLoginForm()
    {
        return view('auth.login-polished');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
