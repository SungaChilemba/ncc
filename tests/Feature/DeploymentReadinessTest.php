<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeploymentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_renders_donation_instructions(): void
    {
        $this->get(route('checkout'))
            ->assertOk()
            ->assertSee('Complete your donation')
            ->assertSee('Request payment details');
    }

    public function test_newsletter_subscription_is_stored_once_per_email(): void
    {
        $this->post(route('newsletter.store'), ['email' => 'Supporter@Example.com'])
            ->assertRedirect()
            ->assertSessionHas('newsletter_status');

        $this->post(route('newsletter.store'), ['email' => 'supporter@example.com'])
            ->assertRedirect()
            ->assertSessionHas('newsletter_status');

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'supporter@example.com']);
    }

    public function test_newsletter_rejects_an_invalid_email(): void
    {
        $this->post(route('newsletter.store'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_newsletter_honeypot_silently_accepts_bots_without_storing_them(): void
    {
        $this->post(route('newsletter.store'), [
            'email' => 'bot@example.com',
            'website' => 'https://spam.example',
        ])->assertRedirect()->assertSessionHas('newsletter_status');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_editor_role_can_log_in_to_the_admin_area(): void
    {
        $editor = User::factory()->create([
            'email' => 'editor@example.com',
            'password' => 'password',
            'is_admin' => false,
            'role' => 'editor',
        ]);

        $this->post('/admin/login', [
            'email' => 'editor@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($editor);
    }

    public function test_viewer_role_cannot_log_in_to_the_admin_area(): void
    {
        User::factory()->create([
            'email' => 'viewer@example.com',
            'password' => 'password',
            'is_admin' => false,
            'role' => 'viewer',
        ]);

        $this->post('/admin/login', [
            'email' => 'viewer@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_login_is_rate_limited_after_repeated_failures(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $response = $this->post('/admin/login', [
                'email' => 'throttled@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email'),
        );
        $this->assertGuest();
    }

    public function test_subscribed_email_is_not_stored_when_it_is_blank(): void
    {
        $this->post(route('newsletter.store'), ['email' => ''])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, NewsletterSubscriber::count());
    }
}
