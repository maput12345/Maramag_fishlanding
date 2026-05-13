<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
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
        $this->middleware('auth')->except('verify');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend', 'updateEmail');
    }

    /**
     * Show the email correction form for users waiting on verification.
     */
    public function editEmail()
    {
        return view('auth.verify', ['editingEmail' => true]);
    }

    /**
     * Update a typo in the pending verification email and send a new link.
     */
    public function updateEmail(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('User', 'email')->ignore($user->id),
            ],
        ], [
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
        ]);

        $email = strtolower($validated['email']);
        $emailWasChanged = $user->email !== $email;

        if ($emailWasChanged) {
            $user->forceFill([
                'email' => $email,
                'email_verified_at' => null,
            ])->save();
        }

        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice')->with(
            'status',
            $emailWasChanged
                ? 'Your email address was updated and a fresh verification link has been sent.'
                : 'A fresh verification link has been sent to your email address.'
        );
    }

    /**
     * Verify a signed email link even when it opens in a fresh browser session.
     */
    public function verify(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException;
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($this->redirectPath())->with('verified', true);
    }
}
