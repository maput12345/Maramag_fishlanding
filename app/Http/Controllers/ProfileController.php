<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Show the user profile form.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $user = Auth::user();
        return view('auth.profile', compact('user'));
    }

    /**
     * Update the user's profile information.
     *
     * @param  \App\Http\Requests\ProfileRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProfileRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $passwordOption = $request->input('password_option', 'keep');

        // Update password only if changing password option is selected
        if ($passwordOption === 'change') {
            $user->password = bcrypt($request->password);
            $user->save();
        }

        if ($user->isAdmin() || $user->isStaff() || $user->isBroker() || $user->isApplicant()) {
            $profileData = [
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'suffix' => $request->suffix,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
            ];

            // Add stall_name for brokers only
            if ($user->isBroker() && $request->has('stall_name')) {
                $profileData['stall_name'] = $request->stall_name;
            }

            $user->updateProfile($profileData);
        }

        // Redirect back to the referring page without modal parameter on success
        $referer = request()->header('referer');
        if ($referer) {
            // Remove modal parameter from referer URL
            $cleanUrl = strtok($referer, '?');
            return redirect()->to($cleanUrl)->with('success', 'Profile updated successfully.');
        }

        // Fallback: redirect to appropriate dashboard based on user role
        if ($user->isAdmin() || $user->isStaff()) {
            return redirect()->route('admin.dashboard')->with('success', 'Profile updated successfully.');
        } elseif ($user->isBroker()) {
            return redirect()->route('broker.dashboard')->with('success', 'Profile updated successfully.');
        }

        return redirect()->route('applications.index')->with('success', 'Profile updated successfully.');
    }
}
