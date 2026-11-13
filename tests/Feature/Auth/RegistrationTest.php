<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Staff accounts come from Admin → Staff only; self sign-up is closed. */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_registration_page_redirects_to_sign_in(): void
    {
        $this->get('/register')->assertRedirect('/login');
    }

    public function test_staff_cannot_self_register(): void
    {
        $this->post('/register', [
            'name' => 'Intruder', 'email' => 'intruder@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing(User::class, ['email' => 'intruder@example.com']);
    }
}
