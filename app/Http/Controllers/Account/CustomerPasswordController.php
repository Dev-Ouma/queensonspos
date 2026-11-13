<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CustomerPasswordController extends Controller
{
    public function forgotForm()
    {
        return Inertia::render('Account/ForgotPassword', ['status' => session('status')]);
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker('customers')->sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        // Same message whether or not the email exists, so accounts can't be discovered.
        return back()->with('status', 'If an account exists for that email, we\'ve sent a link to reset your password.');
    }

    public function resetForm(Request $request, string $token)
    {
        return Inertia::render('Account/ResetPassword', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill(['password' => $password, 'remember_token' => null])->save();
                Auth::guard('customer')->login($customer);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }
        $request->session()->regenerate();

        return redirect()->route('account.dashboard')->with('success', 'Your password has been reset.');
    }
}
