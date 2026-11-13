<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('registers a personal account from first and last name', function () {
    $this->post('/account/register', [
        'first_name' => 'Wanjiru', 'last_name' => 'Kamau', 'email' => 'wanjiru@example.test', 'password' => 'secret123',
    ])->assertRedirect('/account');

    expect(Customer::where('email', 'wanjiru@example.test')->value('name'))->toBe('Wanjiru Kamau');
    $this->assertAuthenticated('customer');
});

it('rejects a wrong password with a clear message on the password step', function () {
    $c = Customer::first();
    $this->post('/account/login', ['email' => $c->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('password');
    $this->assertGuest('customer');
});

it('only remembers the customer when asked', function () {
    $c = Customer::first();
    $c->update(['password' => Hash::make('password')]);

    $this->post('/account/login', ['email' => $c->email, 'password' => 'password', 'remember' => false]);
    expect($c->fresh()->remember_token)->toBeNull();
});

it('requires the current password to change it', function () {
    $c = Customer::first();
    $c->update(['password' => Hash::make('old-password')]);
    $this->actingAs($c, 'customer');

    $this->put('/account/password', ['current_password' => 'nope', 'password' => 'new-password1', 'password_confirmation' => 'new-password1'])
        ->assertSessionHasErrors('current_password');

    $this->put('/account/password', ['current_password' => 'old-password', 'password' => 'new-password1', 'password_confirmation' => 'new-password1'])
        ->assertSessionHasNoErrors();
    expect(Hash::check('new-password1', $c->fresh()->password))->toBeTrue();
});

it('manages addresses with one default, scoped to the owner', function () {
    [$a, $b] = Customer::take(2)->get();
    CustomerAddress::where('customer_id', $a->id)->delete();
    $this->actingAs($a, 'customer');

    $this->post('/account/addresses', ['label' => 'Home', 'line1' => '1 Test St', 'city' => 'Nairobi'])->assertSessionHasNoErrors();
    $this->post('/account/addresses', ['label' => 'Office', 'line1' => '2 Test Ave', 'city' => 'Nairobi', 'is_default' => true]);

    $home = CustomerAddress::where('customer_id', $a->id)->where('label', 'Home')->first();
    $office = CustomerAddress::where('customer_id', $a->id)->where('label', 'Office')->first();
    expect($home->is_default)->toBeFalse()->and($office->is_default)->toBeTrue();

    $this->delete("/account/addresses/{$office->id}");
    expect($home->fresh()->is_default)->toBeTrue();

    $other = CustomerAddress::create(['customer_id' => $b->id, 'label' => 'B', 'line1' => 'x', 'city' => 'y']);
    $this->delete("/account/addresses/{$other->id}")->assertNotFound();
});

it('renders the account settings and recently viewed pages', function () {
    $this->actingAs(Customer::first(), 'customer');
    $this->get('/account/settings')->assertOk();
    $this->get('/account/recently-viewed')->assertOk();
});

it('sends a signed-in customer from the sign-in and register pages to My QUEENSONS', function () {
    $this->actingAs(Customer::first(), 'customer');
    $this->get('/account/login')->assertRedirect('/account');
    $this->get('/account/register')->assertRedirect('/account');
});

it('resets a customer password through the storefront link, not the staff one', function () {
    \Illuminate\Support\Facades\Notification::fake();
    $c = Customer::first();

    $this->post('/account/forgot-password', ['email' => $c->email])->assertSessionHas('status');

    \Illuminate\Support\Facades\Notification::assertSentTo($c, \App\Notifications\CustomerResetPassword::class, function ($n) use ($c) {
        $url = $n->toMail($c)->actionUrl;
        expect($url)->toContain('/account/reset-password/');

        $this->post('/account/reset-password', ['token' => $n->token, 'email' => $c->email, 'password' => 'brand-new-pass1'])
            ->assertRedirect('/account');

        return true;
    });

    expect(Hash::check('brand-new-pass1', $c->fresh()->password))->toBeTrue();
    $this->assertAuthenticatedAs($c->fresh(), 'customer');
});

it('does not reveal whether an email has an account', function () {
    \Illuminate\Support\Facades\Notification::fake();
    $this->post('/account/forgot-password', ['email' => 'nobody@example.test'])
        ->assertSessionHas('status')->assertSessionHasNoErrors();
    \Illuminate\Support\Facades\Notification::assertNothingSent();
});
