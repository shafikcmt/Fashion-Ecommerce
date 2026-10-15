<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    public function test_registration_normalizes_email_and_cannot_grant_admin_role(): void
    {
        $this->post('/register', ['name' => 'Customer', 'email' => 'BUYER@example.test', 'password' => 'StrongPassword123', 'password_confirmation' => 'StrongPassword123', 'role' => 'super_admin'])->assertRedirect(route('verification.notice'));
        $u = User::first();
        $this->assertSame('customer', $u->role);
        $this->assertSame('buyer@example.test', $u->email);
        $this->assertNull($u->email_verified_at);
        Notification::assertSentTo($u, VerifyEmail::class);
        $this->get('/admin/products')->assertForbidden();
    }

    public function test_verification_link_verifies_only_its_owner(): void
    {
        $u = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $u->id, 'hash' => sha1($u->email)]);
        $this->actingAs($u)->get($url)->assertRedirect(route('account'));
        $this->assertNotNull($u->fresh()->email_verified_at);
        $this->actingAs(User::factory()->unverified()->create())->get($url)->assertForbidden();
    }

    public function test_password_reset_token_is_verified_and_reusable_tokens_are_rejected(): void
    {
        $u = User::factory()->create();
        $token = Password::createToken($u);
        $data = ['email' => $u->email, 'token' => $token, 'password' => 'DifferentPassword123', 'password_confirmation' => 'DifferentPassword123'];
        $this->post(route('password.update'), $data)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('DifferentPassword123', $u->fresh()->password));
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
    }

    public function test_customer_cannot_remove_another_customers_address(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = $owner->addresses()->create(['name' => 'Owner', 'phone' => '01712345678', 'address' => 'Test Road', 'district' => 'Dhaka']);
        $this->actingAs($other)->delete(route('addresses.destroy', $address))->assertForbidden();
        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }
}
