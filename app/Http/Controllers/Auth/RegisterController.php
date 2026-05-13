<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\ApplicationOpening;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/applications';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application registration form.
     */
    public function showRegistrationForm()
    {
        if (!$this->hasAvailableApplicationOpening()) {
            return redirect()->route('login')
                ->with('error', 'No vacant stall is open for applications right now.');
        }

        return view('auth.register-polished');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:User'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
        ]);

        $validator->after(function ($validator) {
            if (!$this->hasAvailableApplicationOpening()) {
                $validator->errors()->add('availability', 'No vacant stall is open for applications right now.');
            }
        });

        return $validator;
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::createUserWithRole([
            'email' => $data['email'],
            'email_verified_at' => null,
            'password' => $data['password'],
            'role' => 'applicant',
        ], [
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'suffix' => $data['suffix'] ?? null,
        ]);
    }

    /**
     * Send newly registered applicants to the email verification gate.
     */
    protected function registered(Request $request, $user)
    {
        return redirect()->route('verification.notice')
            ->with('status', 'Please verify your email address before continuing.');
    }

    private function hasAvailableApplicationOpening(): bool
    {
        return ApplicationOpening::availableForApplication()->exists();
    }
}
