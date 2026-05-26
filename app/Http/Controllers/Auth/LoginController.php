<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\ApplicationOpening;
use App\Models\RequirementType;
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
        if ($user && $user->isCashier()) {
            return route('broker.transaction');
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
            ->with('roles:id,role_name')
            ->first();

        if ($user && $user->status === UserStatusConstant::DEACTIVATED) {
            $message = $user->isApplicant() && !$user->isBroker()
                ? 'This applicant account has been archived after the application process. Please create a new account when a new stall application opens.'
                : 'Your account is deactivated.';

            $request->session()->put('auth.deactivated_message', $message);
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
        if ($deactivatedMessage = $request->session()->pull('auth.deactivated_message')) {
            throw ValidationException::withMessages([
                $this->username() => [$deactivatedMessage],
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
        return redirect()->intended($this->redirectTo());
    }

    /**
     * Show the application's login form.
     */
    public function showLoginForm()
    {
        $applicationOpenings = ApplicationOpening::with(['openingBatch', 'stall', 'requirementTypes'])
            ->availableForApplication()
            ->withCount('brokerApplications')
            ->get()
            ->sortBy('start_date')
            ->values();

        return view('auth.login-polished', [
            'hasAvailableStall' => $applicationOpenings->isNotEmpty(),
            'applicationOpenings' => $applicationOpenings,
            'defaultRequirementTypes' => RequirementType::officialChecklistTypes(),
        ]);
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
