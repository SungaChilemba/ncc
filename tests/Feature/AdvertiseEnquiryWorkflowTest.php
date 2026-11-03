<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use App\Models\User;
use App\Support\CmsContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdvertiseEnquiryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_review_enquiries(): void
    {
        $enquiry = $this->makeEnquiry();

        $this->get(route('admin.enquiries.review', $enquiry->id))->assertForbidden();
        $this->post(route('admin.enquiries.approve', $enquiry->id), ['ad_title' => 'Live ad'])->assertForbidden();
        $this->post(route('admin.enquiries.reject', $enquiry->id), ['rejection_reason' => 'Not a fit'])->assertForbidden();
    }

    public function test_review_screen_rejects_content_that_is_not_an_advert_enquiry(): void
    {
        $card = SiteContent::create([
            'section' => 'advertise',
            'type' => 'card',
            'category' => 'Sponsored Ads',
            'title' => 'Regular card',
            'is_published' => true,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.enquiries.review', $card->id))
            ->assertNotFound();
    }

    public function test_admin_can_open_the_enquiry_review_screen(): void
    {
        $enquiry = $this->makeEnquiry();

        $this->actingAs($this->admin())
            ->get(route('admin.enquiries.review', $enquiry->id))
            ->assertOk()
            ->assertSee('Sunrise Media')
            ->assertSee('AD-2026-10001')
            ->assertSee('tadwa@sunrise.mw')
            ->assertSee('Board decision')
            ->assertSee('Approve & publish advert');
    }

    public function test_approving_an_enquiry_publishes_a_sponsored_advert(): void
    {
        $admin = $this->admin();
        $enquiry = $this->makeEnquiry();
        $endsAt = now()->addDays(30)->toDateString();

        $this->actingAs($admin)
            ->post(route('admin.enquiries.approve', $enquiry->id), [
                'ad_title' => 'Sunrise Media child safety campaign',
                'ad_subtitle' => 'Media partner',
                'ad_blurb' => 'Supporting child protection across Malawi.',
                'button_text' => 'Learn more',
                'button_url' => 'https://sunrise.mw',
                'ends_at' => $endsAt,
                'sort_order' => 2,
                'publish_mode' => 'published',
                'is_featured' => '1',
            ])
            ->assertRedirect(route('admin.contents.index', [
                'section' => 'advertise',
                'status' => 'enquiries',
                'review' => 'approved',
            ]));

        $ad = SiteContent::where('section', 'advertise')->where('type', 'card')->where('category', 'Sponsored Ads')->first();

        $this->assertNotNull($ad, 'A sponsored ads card should be created on approval.');
        $this->assertTrue($ad->is_published);
        $this->assertNotNull($ad->published_at);
        $this->assertSame('Sunrise Media child safety campaign', $ad->title);
        $this->assertSame('Media partner', $ad->subtitle);
        $this->assertSame('Supporting child protection across Malawi.', $ad->description);
        $this->assertSame('https://sunrise.mw', $ad->button_url);
        $this->assertSame(2, (int) $ad->sort_order);
        $this->assertSame($endsAt, $ad->data['ends_at']);
        $this->assertSame($enquiry->id, $ad->data['submission_id']);
        $this->assertSame('AD-2026-10001', $ad->data['reference_number']);
        $this->assertTrue($ad->data['is_featured']);
        $this->assertSame('advertise-inquiries/ad-2026-10001/artwork.png', $ad->image_path);
        $this->assertNotEmpty($ad->slug);

        $enquiry->refresh();
        $this->assertSame('approved', $enquiry->data['status']);
        $this->assertSame($ad->id, $enquiry->data['ad_id']);
        $this->assertSame($admin->id, $enquiry->data['reviewed_by']);
        $this->assertFalse($enquiry->is_published, 'Enquiries must stay private.');

        $this->assertTrue(
            app(CmsContent::class)->sponsoredAds()->contains('id', $ad->id),
            'The approved advert should reach the sponsored carousel source.'
        );
    }

    public function test_the_published_advert_never_exposes_private_contact_details(): void
    {
        $enquiry = $this->makeEnquiry();

        $this->actingAs($this->admin())
            ->post(route('admin.enquiries.approve', $enquiry->id), [
                'ad_title' => 'Sunrise Media child safety campaign',
                'ad_blurb' => 'Supporting child protection across Malawi.',
                'publish_mode' => 'published',
            ])
            ->assertRedirect();

        $ad = SiteContent::where('type', 'card')->where('category', 'Sponsored Ads')->firstOrFail();

        foreach (['contact_name', 'contact_email', 'contact_phone', 'org_address', 'objectives'] as $privateField) {
            $this->assertArrayNotHasKey($privateField, $ad->data);
        }

        $this->get('/advertise')
            ->assertOk()
            ->assertSee('Sunrise Media child safety campaign')
            ->assertDontSee('tadwa@sunrise.mw')
            ->assertDontSee('+265 991 000 111')
            ->assertDontSee('Area 3, Lilongwe');
    }

    public function test_approving_as_draft_keeps_the_advert_off_the_public_board(): void
    {
        $enquiry = $this->makeEnquiry();

        $this->actingAs($this->admin())
            ->post(route('admin.enquiries.approve', $enquiry->id), [
                'ad_title' => 'Draft partner advert',
                'publish_mode' => 'draft',
            ])
            ->assertRedirect();

        $ad = SiteContent::where('type', 'card')->where('category', 'Sponsored Ads')->firstOrFail();

        $this->assertFalse($ad->is_published);
        $this->assertNull($ad->published_at);
        $this->assertSame('approved', $enquiry->fresh()->data['status']);
        $this->assertSame(0, app(CmsContent::class)->sponsoredAds()->count());

        $this->get('/advertise')->assertOk()->assertDontSee('Draft partner advert');
    }

    public function test_re_approving_updates_the_same_advert_instead_of_creating_a_second_one(): void
    {
        $enquiry = $this->makeEnquiry();
        $route = route('admin.enquiries.approve', $enquiry->id);
        $admin = $this->admin();

        $this->actingAs($admin)->post($route, ['ad_title' => 'First headline', 'publish_mode' => 'published']);
        $this->actingAs($admin)->post($route, ['ad_title' => 'Updated headline', 'publish_mode' => 'draft']);

        $this->assertSame(1, SiteContent::where('type', 'card')->where('category', 'Sponsored Ads')->count());

        $ad = SiteContent::where('type', 'card')->where('category', 'Sponsored Ads')->firstOrFail();
        $this->assertSame('Updated headline', $ad->title);
        $this->assertFalse($ad->is_published);
        $this->assertSame($ad->id, $enquiry->fresh()->data['ad_id']);
    }

    public function test_rejecting_an_enquiry_records_the_reason_and_unpublishes_the_linked_advert(): void
    {
        $admin = $this->admin();
        $enquiry = $this->makeEnquiry();

        $this->actingAs($admin)->post(route('admin.enquiries.approve', $enquiry->id), [
            'ad_title' => 'Sunrise Media child safety campaign',
            'publish_mode' => 'published',
        ]);

        $ad = SiteContent::where('type', 'card')->where('category', 'Sponsored Ads')->firstOrFail();
        $this->assertTrue($ad->is_published);

        $this->actingAs($admin)
            ->post(route('admin.enquiries.reject', $enquiry->id), [
                'rejection_reason' => 'Creative does not meet our child-safeguarding guidelines.',
            ])
            ->assertRedirect(route('admin.contents.index', [
                'section' => 'advertise',
                'status' => 'enquiries',
                'review' => 'rejected',
            ]));

        $this->assertFalse($ad->fresh()->is_published);

        $data = $enquiry->fresh()->data;
        $this->assertSame('rejected', $data['status']);
        $this->assertSame('Creative does not meet our child-safeguarding guidelines.', $data['rejection_reason']);
        $this->assertSame($admin->id, $data['reviewed_by']);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $enquiry = $this->makeEnquiry();

        $this->actingAs($this->admin())
            ->post(route('admin.enquiries.reject', $enquiry->id), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame('pending_review', $enquiry->fresh()->data['status']);
        $this->assertSame(0, SiteContent::where('type', 'card')->count());
    }

    public function test_renewing_an_advert_extends_its_end_date(): void
    {
        $admin = $this->admin();
        $enquiry = $this->makeEnquiry();
        $newEnd = now()->addDays(90)->toDateString();

        $this->actingAs($admin)->post(route('admin.enquiries.approve', $enquiry->id), [
            'ad_title' => 'Sunrise Media child safety campaign',
            'ends_at' => now()->addDays(10)->toDateString(),
            'publish_mode' => 'published',
        ]);

        $ad = SiteContent::where('type', 'card')->where('category', 'Sponsored Ads')->firstOrFail();
        $yesterday = now()->subDay()->toDateString();
        $ad->update(['data' => array_merge($ad->data, ['ends_at' => $yesterday])]);
        $enquiry->update(['data' => array_merge($enquiry->fresh()->data, ['ends_at' => $yesterday])]);

        $this->assertSame('expired', $enquiry->fresh()->enquiry_status);

        $this->actingAs($admin)
            ->post(route('admin.enquiries.renew', $enquiry->id), ['ends_at' => $newEnd])
            ->assertRedirect();

        $this->assertSame($newEnd, $ad->fresh()->data['ends_at']);
        $this->assertSame($newEnd, $enquiry->fresh()->data['ends_at']);
        $this->assertSame('approved', $enquiry->fresh()->enquiry_status);
        $this->assertTrue(app(CmsContent::class)->sponsoredAds()->contains('id', $ad->id));

        $this->actingAs($admin)
            ->post(route('admin.enquiries.renew', $enquiry->id), ['ends_at' => now()->toDateString()])
            ->assertSessionHasErrors('ends_at');
    }

    public function test_expired_adverts_leave_the_sponsored_carousel(): void
    {
        $live = $this->makeAd('Live partner advert', now()->addDays(5)->toDateString());
        $expired = $this->makeAd('Expired partner advert', now()->subDay()->toDateString());
        $openEnded = $this->makeAd('Open ended partner advert', null);

        $ads = app(CmsContent::class)->sponsoredAds();

        $this->assertTrue($ads->contains('id', $live->id));
        $this->assertTrue($ads->contains('id', $openEnded->id));
        $this->assertFalse($ads->contains('id', $expired->id));

        $this->get('/advertise')
            ->assertOk()
            ->assertSee('Live partner advert')
            ->assertSee('Open ended partner advert')
            ->assertDontSee('Expired partner advert');

        $this->get(route('latest-news'))
            ->assertOk()
            ->assertDontSee('Expired partner advert');
    }

    public function test_the_enquiries_board_filters_by_review_state(): void
    {
        $this->makeEnquiry(['slug' => 'ad-2026-10001', 'title' => 'Pending Media House']);
        $rejected = $this->makeEnquiry([
            'slug' => 'ad-2026-10002',
            'title' => 'Rejected Media House',
            'data' => ['reference_number' => 'AD-2026-10002', 'company' => 'Rejected Media House', 'status' => 'rejected', 'rejection_reason' => 'Out of scope'],
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries']))
            ->assertOk()
            ->assertSee('Pending Media House')
            ->assertSee('Rejected Media House')
            ->assertSee('Review');

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries', 'review' => 'pending_review']))
            ->assertOk()
            ->assertSee('Pending Media House')
            ->assertDontSee('Rejected Media House');

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries', 'review' => 'rejected']))
            ->assertOk()
            ->assertSee('Rejected Media House')
            ->assertSee('Out of scope')
            ->assertDontSee('Pending Media House');

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'advertise']))
            ->assertOk()
            ->assertDontSee('Pending Media House');
    }

    public function test_the_enquiries_board_can_be_searched_by_reference(): void
    {
        $this->makeEnquiry();

        $this->actingAs($this->admin())
            ->get(route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries', 'q' => 'AD-2026-10001']))
            ->assertOk()
            ->assertSee('Sunrise Media');

        $this->actingAs($this->admin())
            ->get(route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries', 'q' => 'AD-9999-00000']))
            ->assertOk()
            ->assertDontSee('Sunrise Media');
    }

    public function test_the_dashboard_reports_pending_enquiries(): void
    {
        $enquiry = $this->makeEnquiry();

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Advert enquiries')
            ->assertSee('1 awaiting review')
            ->assertSee('Sunrise Media')
            ->assertSee(route('admin.enquiries.review', $enquiry->id), false);
    }

    private function admin(): User
    {
        return $this->userWithRole('system_admin');
    }

    private function makeEnquiry(array $overrides = []): SiteContent
    {
        return SiteContent::create(array_merge([
            'section' => 'advertise',
            'type' => 'submission',
            'slug' => 'ad-2026-10001',
            'title' => 'Sunrise Media — AD-2026-10001',
            'subtitle' => 'Media',
            'category' => 'Ad Inquiries',
            'description' => 'We want to reach families across Malawi with child protection messaging.',
            'data' => [
                'reference_number' => 'AD-2026-10001',
                'company' => 'Sunrise Media',
                'industry' => 'Media',
                'objectives' => 'We want to reach families across Malawi with child protection messaging.',
                'start_date' => now()->addWeek()->toDateString(),
                'budget' => 'MWK 2,500,000',
                'contact_name' => 'Tadwa Phiri',
                'contact_email' => 'tadwa@sunrise.mw',
                'contact_phone' => '+265 991 000 111',
                'org_address' => 'Area 3, Lilongwe',
                'preferred_contact' => 'Email',
                'agreement' => true,
                'artwork' => [
                    'path' => 'advertise-inquiries/ad-2026-10001/artwork.png',
                    'url' => '/storage/advertise-inquiries/ad-2026-10001/artwork.png',
                    'original_name' => 'artwork.png',
                    'mime_type' => 'image/png',
                    'size' => 20480,
                ],
                'submitted_at' => now()->toIso8601String(),
                'status' => 'pending_review',
            ],
            'accent_color' => 'green',
            'is_published' => false,
        ], $overrides));
    }

    private function makeAd(string $title, ?string $endsAt): SiteContent
    {
        return SiteContent::create([
            'section' => 'advertise',
            'type' => 'card',
            'slug' => Str::slug($title),
            'title' => $title,
            'category' => 'Sponsored Ads',
            'data' => $endsAt ? ['ends_at' => $endsAt] : [],
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
