<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

/** Password reset email for shoppers: links to the storefront reset page, not the staff one. */
class CustomerResetPassword extends ResetPassword
{
    protected function resetUrl($notifiable)
    {
        return route('account.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
