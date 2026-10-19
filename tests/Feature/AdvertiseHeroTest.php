<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use App\Support\CmsContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdvertiseHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_ads_only_include_explicitly_featured_adverts(): void
    {
        $recentFeatured = $this->makeAd('Recent featured', now()->subDay(), featured: true);
        $olderFeatured = $this->makeAd('Older featured', now()->subDays(2), featured: true);
        $recentNonFeatured = $this->makeAd('Recent non-featured', now()->subHour(), featured: false);
        $oldNonFeatured = $this->makeAd('Old non-featured', now()->subDays(3), featured: false);
        $expired = $this->makeAd('Expired advert', now(), featured: true, endsAt: now()->subDay()->toDateString());
        $draft = $this->makeAd('Draft advert', now(), featured: true, published: false);

        $heroAds = app(CmsContent::class)->heroSponsoredAds(3);

        $this->assertSame(
            [$recentFeatured->id, $olderFeatured->id],
            $heroAds->pluck('id')->all(),
            'Only explicitly featured ads are eligible, ordered most recent first.'
        );

        $this->assertFalse($heroAds->contains('id', $expired->id), 'Expired adverts must never reach the hero.');
        $this->assertFalse($heroAds->contains('id', $draft->id), 'Unpublished adverts must never reach the hero.');
        $this->assertFalse($heroAds->contains('id', $recentNonFeatured->id), 'Unflagged ads must not auto-fill hero slots.');
        $this->assertFalse($heroAds->contains('id', $oldNonFeatured->id), 'Unflagged ads must not auto-fill hero slots.');
    }

    public function test_hero_ads_are_capped_at_the_requested_limit(): void
    {
        foreach (range(1, 5) as $i) {
            $this->makeAd("Partner advert {$i}", now()->subDays($i), featured: true);
        }

        $this->assertCount(3, app(CmsContent::class)->heroSponsoredAds(3));
    }

    public function test_advertise_page_uses_the_cms_hero_content_and_image(): void
    {
        SiteContent::create([
            'section' => 'advertise',
            'type' => 'hero',
            'slug' => 'advertise-hero',
            'title' => 'HERO TITLE TEXT',
            'subtitle' => 'HERO EYEBROW TEXT',
            'description' => 'HERO BODY TEXT',
            'image_path' => 'images/custom-hero.jpg',
            'button_text' => 'Start your partnership',
            'button_url' => '#inquiry-form',
            'is_published' => true,
        ]);

        $this->get(route('advertise'))
            ->assertOk()
            ->assertSee('HERO TITLE TEXT')
            ->assertSee('HERO EYEBROW TEXT')
            ->assertSee('HERO BODY TEXT')
            ->assertSee('images/custom-hero.jpg')
            ->assertSee('Start your partnership');
    }

    public function test_advertise_hero_renders_no_placeholder_copy_when_content_is_missing(): void
    {
        $this->get(route('advertise'))
            ->assertOk()
            ->assertDontSee('Partner & Advertise with NCC')
            ->assertDontSee('Child Protection & Social Impact in Malawi')
            ->assertDontSee('Start Your Partnership')
            ->assertDontSee('2,000+ Families Supported')
            ->assertSee('Start partnership');
    }

    public function test_featured_adverts_render_as_artwork_only_hero_slides_with_the_cta(): void
    {
        $ad = $this->makeAd(
            'Hero partner advert',
            now()->subDay(),
            featured: true,
            endsAt: now()->addDays(5)->toDateString(),
            imagePath: 'images/hero-artwork.jpg',
            description: 'Unique campaign copy that must not overlay the artwork.',
        );

        $response = $this->get(route('advertise'))->assertOk();
        $hero = $this->heroRegion($response);

        $this->assertStringContainsString('images/hero-artwork.jpg', $hero, 'The flagged ad artwork must render in the hero.');
        $this->assertStringContainsString('alt="Hero partner advert"', $hero);
        $this->assertStringContainsString('aria-label="1 of 2"', $hero, 'The default hero slide is followed by the ad slide.');
        $this->assertStringContainsString('aria-label="2 of 2"', $hero);
        $this->assertStringNotContainsString(
            'Unique campaign copy that must not overlay the artwork.',
            $hero,
            'Sponsored hero slides are artwork-only and must not render campaign copy.'
        );
        $this->assertSame(
            2,
            substr_count($hero, 'Start partnership'),
            'Every hero slide renders exactly one CTA button.'
        );

        $this->assertNotNull($ad->id);
    }

    public function test_featured_adverts_still_appear_in_the_sponsored_ads_carousel(): void
    {
        $this->makeAd('Hero partner advert', now()->subDay(), featured: true, imagePath: 'images/hero-artwork.jpg');

        $html = $this->get(route('advertise'))->assertOk()->getContent();

        $this->assertSame(
            2,
            substr_count($html, 'images/hero-artwork.jpg'),
            'A hero-selected advert must appear in both the hero and the sponsored ads carousel.'
        );
    }

    public function test_unflagged_adverts_do_not_add_hero_slides(): void
    {
        $this->makeAd('Carousel only advert', now()->subDay(), featured: false);

        $response = $this->get(route('advertise'))->assertOk();
        $hero = $this->heroRegion($response);

        $this->assertStringContainsString('aria-label="1 of 1"', $hero, 'Only the default hero slide should render.');
        $this->assertStringNotContainsString('aria-label="2 of', $hero);
    }

    public function test_hero_renders_a_single_partners_card_from_the_live_approved_count(): void
    {
        $this->makeApprovedEnquiry('Sunrise Media');
        $this->makeApprovedEnquiry('Sunrise Media');
        $this->makeApprovedEnquiry('Kwacha Films');
        $this->makeEnquiry();

        $response = $this->get(route('advertise'))->assertOk();

        $response
            ->assertSee('Partners')
            ->assertSee('fa-handshake')
            ->assertDontSee('Live partner adverts')
            ->assertDontSee('Partner enquiries received');

        $this->assertStringContainsString(
            'target: 2,',
            $response->getContent(),
            'The Partners card counts unique approved partner companies.'
        );
    }

    public function test_partners_card_prefers_an_admin_cms_stat_block(): void
    {
        SiteContent::create([
            'section' => 'advertise',
            'type' => 'stat',
            'slug' => 'advertise-partners-stat',
            'title' => '250+',
            'description' => 'Partner organisations',
            'data' => ['icon' => 'fa-people-group'],
            'is_published' => true,
        ]);

        $this->get(route('advertise'))
            ->assertOk()
            ->assertSee('250+')
            ->assertSee('Partner organisations')
            ->assertSee('fa-people-group')
            ->assertDontSee('fa-handshake');
    }

    private function heroRegion(TestResponse $response): string
    {
        $html = $response->getContent();
        $start = strpos($html, 'aria-label="Advertise with NCC"');
        $this->assertNotFalse($start, 'The hero carousel region was not found on the page.');

        $section = substr($html, $start);
        $end = strpos($section, '</section>');
        $this->assertNotFalse($end, 'The hero carousel section was not closed.');

        return substr($section, 0, $end);
    }

    private function makeAd(string $title, ?string $publishedAt = null, bool $featured = false, bool $published = true, ?string $endsAt = null, ?string $imagePath = null, ?string $description = null): SiteContent
    {
        return SiteContent::create([
            'section' => 'advertise',
            'type' => 'card',
            'slug' => Str::slug($title),
            'title' => $title,
            'description' => $description,
            'category' => 'Sponsored Ads',
            'image_path' => $imagePath,
            'data' => array_filter([
                'is_featured' => $featured,
                'ends_at' => $endsAt,
            ], fn ($value) => ! is_null($value)),
            'is_published' => $published,
            'published_at' => $published ? ($publishedAt ? Carbon::parse($publishedAt) : now()) : null,
        ]);
    }

    private function makeEnquiry(string $company = 'Sunrise Media', string $status = 'pending_review'): SiteContent
    {
        return SiteContent::create([
            'section' => 'advertise',
            'type' => 'submission',
            'slug' => 'ad-2026-'.random_int(20000, 99999),
            'title' => "{$company} — AD-2026-".random_int(20000, 99999),
            'category' => 'Ad Inquiries',
            'data' => [
                'company' => $company,
                'status' => $status,
                'submitted_at' => now()->toIso8601String(),
            ],
            'is_published' => false,
        ]);
    }

    private function makeApprovedEnquiry(string $company): SiteContent
    {
        return $this->makeEnquiry($company, 'approved');
    }
}
