<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use App\Support\CmsContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdvertiseHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_ads_show_featured_first_then_most_recent(): void
    {
        $oldNonFeatured = $this->makeAd('Old non-featured', now()->subDays(3), featured: false);
        $olderFeatured = $this->makeAd('Older featured', now()->subDays(2), featured: true);
        $recentNonFeatured = $this->makeAd('Recent non-featured', now()->subDay(), featured: false);
        $recentFeatured = $this->makeAd('Recent featured', now()->subDays(1), featured: true);
        $expired = $this->makeAd('Expired advert', now(), featured: true, endsAt: now()->subDay()->toDateString());
        $draft = $this->makeAd('Draft advert', now(), featured: true, published: false);

        $heroAds = app(CmsContent::class)->heroSponsoredAds(3);

        $this->assertSame(
            [$recentFeatured->id, $olderFeatured->id, $recentNonFeatured->id],
            $heroAds->pluck('id')->all(),
            'Featured ads should lead (most recent first), then the most recent non-featured ads.'
        );

        $this->assertFalse($heroAds->contains('id', $expired->id));
        $this->assertFalse($heroAds->contains('id', $draft->id));
        $this->assertFalse($heroAds->contains('id', $oldNonFeatured->id), 'Only the most recent non-featured ads should fill remaining slots.');
    }

    public function test_hero_ads_are_capped_at_the_requested_limit(): void
    {
        foreach (range(1, 5) as $i) {
            $this->makeAd("Partner advert {$i}", now()->subDays($i));
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
            ->assertDontSee('2,000+ Families Supported');
    }

    public function test_sponsored_adverts_are_shown_as_hero_slides(): void
    {
        SiteContent::create([
            'section' => 'advertise',
            'type' => 'hero',
            'slug' => 'advertise-hero',
            'title' => 'Advertise with NCC',
            'is_published' => true,
        ]);

        $this->makeAd('Hero partner advert', now()->subDay()->toDateString(), endsAt: now()->addDays(5)->toDateString());

        $this->get(route('advertise'))
            ->assertOk()
            ->assertSee('Hero partner advert')
            ->assertSee('Ends');
    }

    public function test_hero_stats_are_derived_from_backend_counts(): void
    {
        $this->makeAd('Live partner advert', now()->subDay()->toDateString());
        $this->makeEnquiry();

        $this->get(route('advertise'))
            ->assertOk()
            ->assertSee('Live partner adverts')
            ->assertSee('Partner enquiries received');
    }

    public function test_hero_stats_render_nothing_when_all_counts_are_zero(): void
    {
        $this->get(route('advertise'))
            ->assertOk()
            ->assertDontSee('Live partner adverts')
            ->assertDontSee('Partner enquiries received');
    }

    private function makeAd(string $title, ?string $publishedAt = null, bool $featured = false, bool $published = true, ?string $endsAt = null): SiteContent
    {
        return SiteContent::create([
            'section' => 'advertise',
            'type' => 'card',
            'slug' => Str::slug($title),
            'title' => $title,
            'category' => 'Sponsored Ads',
            'data' => array_filter([
                'is_featured' => $featured,
                'ends_at' => $endsAt,
            ], fn ($value) => ! is_null($value)),
            'is_published' => $published,
            'published_at' => $published ? ($publishedAt ? Carbon::parse($publishedAt) : now()) : null,
        ]);
    }

    private function makeEnquiry(): SiteContent
    {
        return SiteContent::create([
            'section' => 'advertise',
            'type' => 'submission',
            'slug' => 'ad-2026-20001',
            'title' => 'Sunrise Media — AD-2026-20001',
            'category' => 'Ad Inquiries',
            'data' => [
                'company' => 'Sunrise Media',
                'status' => 'pending_review',
                'submitted_at' => now()->toIso8601String(),
            ],
            'is_published' => false,
        ]);
    }
}
