<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicCmsContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_published_cms_content_from_controller(): void
    {
        SiteContent::create([
            'section' => 'home',
            'type' => 'hero',
            'title' => 'CMS Hero Title',
            'subtitle' => 'CMS hero subtitle',
            'button_text' => 'Get Help',
            'button_url' => '/reporting',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        SiteContent::create([
            'section' => 'home',
            'type' => 'update',
            'title' => 'CMS Top Story',
            'description' => 'A published top story from the CMS.',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        SiteContent::create([
            'section' => 'home',
            'type' => 'news',
            'title' => 'Hidden Draft News',
            'is_published' => false,
            'sort_order' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('CMS Hero Title')
            ->assertSee('CMS hero subtitle')
            ->assertSee('CMS Top Story')
            ->assertDontSee('Hidden Draft News');
    }

    public function test_homepage_news_links_point_to_article_detail_pages(): void
    {
        SiteContent::create([
            'section' => 'home',
            'type' => 'update',
            'slug' => 'legacy-top-story',
            'title' => 'Legacy Top Story',
            'description' => 'Top story with a legacy placeholder link.',
            'button_text' => 'Read More',
            'button_url' => '#',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'linked-homepage-story',
            'title' => 'Linked Homepage Story',
            'description' => 'This article should be reachable from the homepage.',
            'image_path' => 'images/news1.png',
            'location' => 'Lilongwe',
            'published_on' => '2026-08-01',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('news'), false)
            ->assertSee(route('news.show', ['slug' => 'linked-homepage-story']), false);

        preg_match_all('/<a\s+href="([^"]*)"[^>]*>\s*Read\s+More/si', $response->getContent(), $matches);

        $this->assertNotEmpty($matches[1]);
        $this->assertNotContains('#', $matches[1]);
    }

    public function test_non_home_pages_render_published_cms_highlights(): void
    {
        SiteContent::create([
            'section' => 'about',
            'type' => 'card',
            'title' => 'CMS About Highlight',
            'description' => 'Editable about-page card content.',
            'accent_color' => 'green',
            'button_text' => 'Learn More',
            'button_url' => '/about',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        SiteContent::create([
            'section' => 'about',
            'type' => 'card',
            'title' => 'Draft About Highlight',
            'is_published' => false,
            'sort_order' => 1,
        ]);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Content')
            ->assertSee('CMS About Highlight')
            ->assertSee('Editable about-page card content.')
            ->assertDontSee('Draft About Highlight');
    }

    public function test_news_page_renders_cms_hero_and_articles(): void
    {
        SiteContent::create([
            'section' => 'news',
            'type' => 'hero',
            'slug' => 'featured-news',
            'title' => 'Featured CMS News',
            'description' => "Featured paragraph one.\n\nFeatured paragraph two.",
            'category' => 'News',
            'published_on' => '2026-06-02',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'visible-news-card',
            'title' => 'Visible News Card',
            'description' => str_repeat('Visible news body excerpt text. ', 16) . 'FULL ARTICLE TAIL SHOULD ONLY SHOW ON DETAIL.',
            'category' => 'Child Protection',
            'location' => 'Lilongwe',
            'published_on' => '2026-08-06',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'draft-news-card',
            'title' => 'Draft News Card',
            'is_published' => false,
            'sort_order' => 2,
        ]);

        $this->get(route('news'))
            ->assertOk()
            ->assertSee('Featured CMS News')
            ->assertSee('Featured paragraph one.')
            ->assertSee('Visible News Card')
            ->assertSee('Child Protection')
            ->assertDontSee('FULL ARTICLE TAIL SHOULD ONLY SHOW ON DETAIL')
            ->assertDontSee('Draft News Card');
    }

    public function test_resources_page_renders_cms_documents(): void
    {
        SiteContent::create([
            'section' => 'resources',
            'type' => 'hero',
            'slug' => 'resources-header',
            'title' => 'CMS Resources',
            'description' => 'CMS resource intro.',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        SiteContent::create([
            'section' => 'resources',
            'type' => 'resource',
            'slug' => 'cms-guideline',
            'title' => 'CMS Guideline',
            'description' => 'Downloadable CMS guideline.',
            'category' => 'Guidelines',
            'file_path' => 'resource/NCC NATIONAL GUIDELINES.pdf',
            'button_text' => 'Download',
            'button_url' => 'resource/NCC NATIONAL GUIDELINES.pdf',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('resources'))
            ->assertOk()
            ->assertSee('CMS Resources')
            ->assertSee('CMS Guideline')
            ->assertSee('Downloadable CMS guideline.')
            ->assertSee('resource/NCC NATIONAL GUIDELINES.pdf', false);
    }

    public function test_news_detail_page_renders_cms_article_by_slug(): void
    {
        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'detail-story',
            'title' => 'Detail Story',
            'description' => 'Full article body from CMS.',
            'category' => 'Policy',
            'location' => 'Lilongwe',
            'published_on' => '2026-08-01',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('news.show', ['slug' => 'detail-story']))
            ->assertOk()
            ->assertSee('Detail Story')
            ->assertSee('Full article body from CMS.')
            ->assertSee('Policy')
            ->assertSee('Lilongwe');

        $this->get(route('news.show', ['slug' => 'missing-story']))
            ->assertNotFound();
    }

    public function test_news_detail_page_renders_structured_source_citation(): void
    {
        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'sourced-story',
            'title' => 'Sourced Story',
            'description' => 'Article body structured from a source post.',
            'category' => 'Monitoring',
            'published_on' => '2026-08-18',
            'data' => [
                'source' => [
                    'name' => 'National Children’s Commission of Malawi on Facebook',
                    'url' => 'https://www.facebook.com/permalink.php?story_fbid=test&id=61578135073164',
                    'type' => 'original',
                    'posted_on' => '2026-08-18',
                ],
            ],
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('news.show', ['slug' => 'sourced-story']))
            ->assertOk()
            ->assertSee('Source')
            ->assertSee('National Children’s Commission of Malawi on Facebook')
            ->assertSee('https://www.facebook.com/permalink.php?story_fbid=test&amp;id=61578135073164', false)
            ->assertSee('Posted 18 August 2026')
            ->assertDontSee('structured from an original NCC post');
    }

    public function test_resource_detail_page_renders_cms_resource_by_slug(): void
    {
        SiteContent::create([
            'section' => 'resources',
            'type' => 'resource',
            'slug' => 'detail-resource',
            'title' => 'Detail Resource',
            'description' => 'Full resource summary from CMS.',
            'category' => 'Acts',
            'file_path' => 'resource/NCC-Act.pdf',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('resources.show', ['slug' => 'detail-resource']))
            ->assertOk()
            ->assertSee('Detail Resource')
            ->assertSee('Full resource summary from CMS.')
            ->assertSee('View Document')
            ->assertSee('resource/NCC-Act.pdf', false);

        $this->get(route('resources.show', ['slug' => 'missing-resource']))
            ->assertNotFound();
    }

    public function test_resource_download_route_serves_cms_document(): void
    {
        SiteContent::create([
            'section' => 'resources',
            'type' => 'resource',
            'slug' => 'ncc-act',
            'title' => 'NCC Act',
            'description' => 'The official NCC Act.',
            'category' => 'Acts',
            'file_path' => 'resource/NCC-Act.pdf',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('resources.download', ['slug' => 'ncc-act']))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->get(route('resources.download', ['slug' => 'missing-resource']))
            ->assertNotFound();
    }

    public function test_migrated_static_pages_render_cms_blocks(): void
    {
        foreach (['what-we-do', 'reporting', 'donate', 'advertise'] as $section) {
            SiteContent::create([
                'section' => $section,
                'type' => 'hero',
                'slug' => $section . '-hero',
                'title' => strtoupper($section) . ' Hero',
                'description' => 'Hero content for ' . $section,
                'is_published' => true,
                'sort_order' => 0,
            ]);

            SiteContent::create([
                'section' => $section,
                'type' => 'card',
                'slug' => $section . '-card',
                'category' => 'CMS Group',
                'title' => strtoupper($section) . ' Card',
                'description' => 'Card content for ' . $section,
                'is_published' => true,
                'sort_order' => 1,
            ]);
        }

        $this->get(route('what-we-do'))->assertOk()->assertSee('WHAT-WE-DO Hero')->assertSee('WHAT-WE-DO Card');
        $this->get(route('reporting'))->assertOk()->assertSee('REPORTING Hero')->assertSee('REPORTING Card');
        $this->get(route('donate'))->assertOk()->assertSee('DONATE Hero')->assertSee('DONATE Card');
        $this->get(route('advertise'))->assertOk()->assertSee('ADVERTISE Hero')->assertSee('ADVERTISE Card');
    }

    public function test_reporting_form_submission_is_stored_as_cms_submission(): void
    {
        $response = $this->post(route('reporting.store'), [
            'child_age' => 12,
            'child_name' => 'Mock Child',
            'district' => 'Lilongwe',
            'village' => 'Area 25',
            'gender' => 'Female',
            'violation_type' => 'Neglect',
            'incident_date' => '2026-08-30',
            'description' => 'Mock report created during submission testing.',
            'reporter_name' => 'Mock Reporter',
            'preferred_contact' => '+265 999 000 000',
        ]);

        $response
            ->assertRedirect(route('reporting'))
            ->assertSessionHas('report_reference');

        $submission = SiteContent::where('section', 'reporting')
            ->where('type', 'submission')
            ->where('category', 'Case Submissions')
            ->first();

        $this->assertNotNull($submission);
        $this->assertFalse($submission->is_published);
        $this->assertSame('Neglect', $submission->subtitle);
        $this->assertSame('Lilongwe', $submission->location);
        $this->assertSame('Mock report created during submission testing.', $submission->description);
        $this->assertSame('received', $submission->data['status']);
        $this->assertSame(12, $submission->data['child_age']);
        $this->assertSame('Mock Child', $submission->data['child_name']);
        $this->assertSame('Mock Reporter', $submission->data['reporter_name']);
    }

    public function test_reporting_evidence_upload_is_attached_to_cms_submission(): void
    {
        Storage::fake('public');

        $submission = SiteContent::create([
            'section' => 'reporting',
            'type' => 'submission',
            'slug' => 'ncc-2026-12345',
            'title' => 'Report NCC-2026-12345',
            'subtitle' => 'Neglect',
            'category' => 'Case Submissions',
            'location' => 'Lilongwe',
            'description' => 'Existing mock report for evidence upload.',
            'data' => [
                'reference_number' => 'NCC-2026-12345',
                'status' => 'received',
            ],
            'is_published' => false,
            'sort_order' => 0,
        ]);

        $response = $this->post(route('reporting.evidence.store'), [
            'reference_number' => 'NCC-2026-12345',
            'evidence' => [
                UploadedFile::fake()->create('mock-evidence.pdf', 128, 'application/pdf'),
            ],
        ]);

        $response
            ->assertRedirect(route('reporting'))
            ->assertSessionHas('evidence_status');

        $submission->refresh();

        $this->assertSame(1, $submission->data['evidence_count']);
        $this->assertSame('mock-evidence.pdf', $submission->data['evidence'][0]['original_name']);
        $this->assertSame('application/pdf', $submission->data['evidence'][0]['mime_type']);

        Storage::disk('public')->assertExists($submission->data['evidence'][0]['path']);
        $this->assertDatabaseHas('media_assets', [
            'path' => $submission->data['evidence'][0]['path'],
            'original_name' => 'mock-evidence.pdf',
        ]);
    }

    public function test_childrens_corner_renders_cms_activity_right_gallery_and_news(): void
    {
        SiteContent::create([
            'section' => 'childrens-corner',
            'type' => 'hero',
            'slug' => 'kids-hero',
            'title' => 'Kids CMS Hero',
            'description' => 'Kids hero content.',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        SiteContent::create([
            'section' => 'childrens-corner',
            'type' => 'card',
            'slug' => 'kids-activity',
            'category' => 'Activities',
            'title' => 'CMS Quiz',
            'subtitle' => 'fa-solid fa-question',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        SiteContent::create([
            'section' => 'childrens-corner',
            'type' => 'card',
            'slug' => 'kids-right',
            'category' => 'Child Rights',
            'title' => 'CMS Education Rights',
            'is_published' => true,
            'sort_order' => 2,
        ]);

        SiteContent::create([
            'section' => 'childrens-corner',
            'type' => 'news',
            'slug' => 'kids-news',
            'title' => 'CMS Kids Update',
            'button_text' => 'Share Video',
            'button_url' => '#',
            'is_published' => true,
            'sort_order' => 3,
        ]);

        $this->get(route('childrens-corner'))
            ->assertOk()
            ->assertSee('Kids CMS Hero')
            ->assertSee('CMS Quiz')
            ->assertSee('CMS Education Rights')
            ->assertSee('CMS Kids Update');
    }
}
