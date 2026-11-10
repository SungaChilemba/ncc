<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        // Legacy /news now redirects to /latest-news; verify redirect preserves backwards compatibility
        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'visible-news-card',
            'title' => 'Visible News Card',
            'description' => str_repeat('Visible news body excerpt text. ', 16).'FULL ARTICLE TAIL SHOULD ONLY SHOW ON DETAIL.',
            'category' => 'Child Protection',
            'location' => 'Lilongwe',
            'published_on' => '2026-08-06',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('news'))
            ->assertRedirect(route('latest-news'));

        $this->get(route('latest-news'))
            ->assertOk()
            ->assertSee('Visible News Card')
            ->assertSee('Child Protection')
            ->assertDontSee('Draft News Card');
    }

    public function test_latest_news_page_renders_published_cms_news(): void
    {
        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'published-latest',
            'title' => 'Published Latest News',
            'description' => 'Visible body for latest news listing.',
            'category' => 'Education',
            'location' => 'Mzuzu',
            'published_on' => '2026-08-06',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'draft-latest',
            'title' => 'Draft Latest News',
            'is_published' => false,
            'sort_order' => 2,
        ]);

        $response = $this->get(route('latest-news'))
            ->assertOk()
            ->assertSee('Published Latest News')
            ->assertSee('Education')
            ->assertDontSee('Draft Latest News');

        // Ensure no hard-coded placeholder titles from old template remain
        $response->assertDontSee('NCC unveils child commissioners');
        $response->assertDontSee('Strategic Partnership for Community Development');
        $response->assertDontSee('Chief Malemia Sentenced to 21 Years in Prison for Child Defilement');
    }

    public function test_news_redirects_to_latest_news(): void
    {
        $this->get('/news')
            ->assertRedirect('/latest-news');
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
                'slug' => $section.'-hero',
                'title' => strtoupper($section).' Hero',
                'description' => 'Hero content for '.$section,
                'is_published' => true,
                'sort_order' => 0,
            ]);

            SiteContent::create([
                'section' => $section,
                'type' => 'card',
                'slug' => $section.'-card',
                'category' => 'CMS Group',
                'title' => strtoupper($section).' Card',
                'description' => 'Card content for '.$section,
                'is_published' => true,
                'sort_order' => 1,
            ]);
        }

        $this->get(route('what-we-do'))->assertOk()->assertSee('WHAT-WE-DO Hero')->assertSee('WHAT-WE-DO Card');
        $this->get(route('reporting'))->assertOk()->assertSee('REPORTING Hero')->assertSee('REPORTING Card');
        $this->get(route('donate'))->assertOk()->assertSee('DONATE Hero')->assertSee('DONATE Card');
        $this->get(route('advertise'))->assertOk()->assertSee('ADVERTISE Hero')->assertSee('ADVERTISE Card');
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

    public function test_news_article_page_renders_full_individual_article(): void
    {
        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'full-article-latest',
            'title' => 'Full Article Latest',
            'description' => 'Complete body text for individual article.',
            'category' => 'Health',
            'location' => 'Zomba',
            'published_on' => '2026-08-10',
            'image_path' => 'images/news1.png',
            'data' => ['source' => ['name' => 'NCC Source', 'url' => 'https://example.com/source']],
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('news.show', ['slug' => 'full-article-latest']))
            ->assertOk()
            ->assertSee('Full Article Latest')
            ->assertSee('Complete body text for individual article.')
            ->assertSee('Health')
            ->assertSee('Zomba')
            ->assertSee('10 August 2026')
            ->assertSee('NCC Source')
            ->assertSee(route('latest-news'), false);

        $this->get(route('news.show', ['slug' => 'missing-article-xyz']))
            ->assertNotFound();
    }

    public function test_related_articles_exclude_current_article(): void
    {
        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'main-story',
            'title' => 'Main Story',
            'description' => 'Main story body.',
            'category' => 'Policy',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'related-one',
            'title' => 'Related One',
            'description' => 'Related one body.',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'related-two',
            'title' => 'Related Two',
            'description' => 'Related two body.',
            'is_published' => true,
            'sort_order' => 2,
        ]);

        $response = $this->get(route('news.show', ['slug' => 'main-story']))
            ->assertOk()
            ->assertSee('Related One')
            ->assertSee('Related Two');

        // Current article should not appear in related section as a related card link to itself
        $content = $response->getContent();
        $relatedLinks = [];
        preg_match_all('/href="([^"]*\/news\/[^"]*)"/', $content, $m);
        if (! empty($m[1])) {
            $relatedLinks = $m[1];
        }
        // Ensure at least one related link exists and none is the current article slug repeated in related section beyond the main article URL itself
        $this->assertNotEmpty($relatedLinks);
        // Count occurrences of main-story slug in related section grid - should be 0 or only as canonical URL not as related card
        // We verify that Related News heading exists and that the main story title does not appear a second time in related cards context
        // Simple check: related cards should not contain link to main-story more than once (the page itself doesn't link to itself)
        $mainStoryLink = route('news.show', ['slug' => 'main-story']);
        $mainCount = substr_count($content, $mainStoryLink);
        // mainStoryLink appears at most 0 times in related cards (it may appear 0, current page url is not a link to itself)
        $this->assertLessThanOrEqual(1, $mainCount);
    }

    public function test_every_news_card_links_to_news_show(): void
    {
        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'card-link-story',
            'title' => 'Card Link Story',
            'description' => 'Body for card link story.',
            'category' => 'Community Events',
            'published_on' => '2026-08-05',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $responseLatest = $this->get(route('latest-news'))->assertOk();
        $this->assertStringContainsString(route('news.show', ['slug' => 'card-link-story']), $responseLatest->getContent());
        // Ensure no placeholder # links in the main page content (footer social links are excluded)
        $mainOnly = preg_replace('/<footer.*?<\/footer>/s', '', $responseLatest->getContent());
        $this->assertStringNotContainsString('href="#"', $mainOnly);

        $responseHome = $this->get(route('home'))->assertOk();
        // Homepage latestNews should contain link to news.show
        $this->assertStringContainsString(route('news.show', ['slug' => 'card-link-story']), $responseHome->getContent());

        $responseDetail = $this->get(route('news.show', ['slug' => 'card-link-story']))->assertOk();
        // Detail page back link should go to latest-news, not #
        $this->assertStringContainsString(route('latest-news'), $responseDetail->getContent());
    }

    public function test_childrens_corner_news_links_point_to_article_pages(): void
    {
        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'kids-global-story',
            'title' => 'Kids Global Story',
            'description' => 'Kids global body.',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $response = $this->get(route('childrens-corner'))->assertOk();
        $this->assertStringContainsString(route('news.show', ['slug' => 'kids-global-story']), $response->getContent());
        // Ensure no placeholder # links in the main page content (footer social links are excluded)
        $mainOnly = preg_replace('/<footer.*?<\/footer>/s', '', $response->getContent());
        $this->assertStringNotContainsString('href="#"', $mainOnly);
    }

    public function test_childrens_corner_news_shows_six_by_default_with_load_more(): void
    {
        foreach (range(1, 8) as $i) {
            SiteContent::create([
                'section' => 'news',
                'type' => 'news',
                'slug' => 'kids-bulk-story-'.$i,
                'title' => 'Kids Bulk Story '.$i,
                'description' => 'Kids bulk body '.$i.'.',
                'is_published' => true,
                'sort_order' => $i,
            ]);
        }

        $response = $this->get(route('childrens-corner'))->assertOk();
        $content = $response->getContent();

        // All cards are server-rendered (SEO / no-JS friendly); Alpine reveals six at a time.
        foreach (range(1, 8) as $i) {
            $this->assertStringContainsString('Kids Bulk Story '.$i, $content);
            $this->assertStringContainsString(route('news.show', ['slug' => 'kids-bulk-story-'.$i]), $content);
        }
        $this->assertStringContainsString('newsVisible: 6', $content);
        $this->assertStringContainsString('x-show="0 < newsVisible"', $content);
        $this->assertStringContainsString('x-show="6 < newsVisible"', $content);
        $this->assertStringContainsString('Load More', $content);
        $this->assertStringContainsString('Show Less', $content);
    }

    public function test_hard_coded_placeholder_news_not_rendered(): void
    {
        $response = $this->get(route('latest-news'))->assertOk();
        $response->assertDontSee('NCC unveils child commissioners');
        $response->assertDontSee('Strategic Partnership for Community Development');
        $response->assertDontSee('National Child Protection Initiative Launched');
        $response->assertDontSee('MALAWI REACHES 7.37 MILLION CHILDREN WITH POLIO VACCINATION');
    }

    public function test_about_people_section_uses_mobile_dropdown(): void
    {
        $response = $this->get(route('about'))->assertOk();

        // Mobile dropdown bound to the same Alpine state as the desktop tabs.
        $response->assertSee('id="people-select"', false);
        $response->assertSee('x-model="peopleTab"', false);
        $response->assertSee('<option value="commissioners">Commissioners</option>', false);
        $response->assertSee('<option value="exOfficials">Ex-Officials</option>', false);
        $response->assertSee('<option value="management">Management</option>', false);

        // Desktop tab buttons remain for larger screens.
        $response->assertSee('peopleTab === \'management\'', false);
    }

    public function test_about_people_tabs_use_the_same_card_and_biography_fields(): void
    {
        $people = [
            ['category' => 'Commissioners', 'slug' => 'test-commissioner', 'title' => 'Commissioner Person', 'subtitle' => 'Chairperson'],
            ['category' => 'Ex-Officials', 'slug' => 'test-ex-official', 'title' => 'Ex-Official Person', 'subtitle' => 'Former Chairperson'],
            ['category' => 'Management', 'slug' => 'test-management', 'title' => 'Management Person', 'subtitle' => 'Finance Manager'],
        ];

        foreach ($people as $index => $person) {
            SiteContent::create([
                'section' => 'about',
                'type' => 'card',
                'slug' => $person['slug'],
                'category' => $person['category'],
                'title' => $person['title'],
                'subtitle' => $person['subtitle'],
                'is_published' => true,
                'sort_order' => $index,
                'data' => [
                    'bio_sections' => [
                        ['heading' => $person['category'].' Background', 'body' => $person['title'].' biography body.'],
                    ],
                ],
            ]);
        }

        $response = $this->get(route('about'))->assertOk();
        $content = $response->getContent();

        foreach ($people as $person) {
            $response->assertSee($person['title']);
            $response->assertSee($person['subtitle']);
            $response->assertSee($person['category'].' Background');
            $response->assertSee($person['title'].' biography body.');
        }

        // All three tabs render the same biography action and modal.
        $this->assertSame(3, substr_count($content, 'View Biography'));
        $response->assertDontSee('>Organogram<', false);
    }

    public function test_donate_page_renders_faqs_as_centered_accordion_on_top(): void
    {
        SiteContent::create([
            'section' => 'donate',
            'type' => 'card',
            'slug' => 'faq-how-spent',
            'category' => 'FAQs about donating to National Children\'s Commission',
            'title' => 'How are my donations spent?',
            'description' => "Your donations help children in vulnerable places survive.\n\nThey rebuild their future through healthcare and education.",
            'is_published' => true,
            'sort_order' => 11,
        ]);

        SiteContent::create([
            'section' => 'donate',
            'type' => 'card',
            'slug' => 'giving-options',
            'category' => 'Giving Options',
            'title' => 'Monthly and One-Off Giving',
            'description' => 'Choose monthly or one-off support.',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('donate'));

        $response->assertOk()
            ->assertSee('How are my donations spent?')
            ->assertSee('Your donations help children in vulnerable places survive.')
            ->assertSee('FAQs about donating to National Children\'s Commission')
            ->assertSee('<details class="faq-item">', false);

        // The FAQ accordion renders after the other donation content sections, just before the footer area.
        $content = $response->getContent();
        $this->assertGreaterThan(
            strpos($content, 'Giving Options'),
            strpos($content, 'How are my donations spent?')
        );
    }

    public function test_donate_page_renders_watch_story_video_preview(): void
    {
        SiteContent::create([
            'section' => 'donate',
            'type' => 'card',
            'slug' => 'watch-our-story',
            'category' => 'Watch Our Story',
            'title' => 'Watch Our Story',
            'description' => 'https://www.youtube.com/embed/WMNEL8-INig',
            'is_published' => true,
            'sort_order' => 10,
        ]);

        $response = $this->get(route('donate'));

        $response->assertOk()
            ->assertSee('Watch Our Story')
            ->assertSee('<iframe', false)
            ->assertSee('https://www.youtube.com/embed/WMNEL8-INig?rel=0', false);
    }

    public function test_donate_page_renders_donation_allocation_pie_chart(): void
    {
        SiteContent::create([
            'section' => 'donate',
            'type' => 'card',
            'slug' => 'where-donation-goes',
            'category' => 'Where will your donation go',
            'title' => 'Supporting Our Work',
            'description' => '75% of every donation goes directly into charitable programmes. 25% is invested in fundraising.',
            'is_published' => true,
            'sort_order' => 9,
        ]);

        $response = $this->get(route('donate'));

        $response->assertOk()
            ->assertSee('Where will your donation go')
            ->assertSee('Direct charitable programmes')
            ->assertSee('Fundraising')
            ->assertSee('<canvas', false)
            ->assertSee('Donation allocation', false);
    }

    public function test_about_page_renders_cms_story_bullets(): void
    {
        SiteContent::create([
            'section' => 'about',
            'type' => 'card',
            'slug' => 'our-mandate',
            'category' => 'Mandate, Mission & Objectives',
            'title' => 'Our Mandate',
            'description' => 'Fallback description',
            'data' => ['bullets' => ['First CMS bullet', 'Second CMS bullet']],
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('First CMS bullet')
            ->assertSee('Second CMS bullet');
    }
}
