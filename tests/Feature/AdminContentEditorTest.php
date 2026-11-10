<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentEditorTest extends TestCase
{
    use RefreshDatabase;

    private function assertBlockVisible(string $html, string $block): void
    {
        $node = $this->blockNode($html, $block);
        $classes = preg_split('/\s+/', $node->getAttribute('class'));

        $this->assertNotContains('hidden', $classes, "Block [{$block}] should be visible.");
    }

    private function assertBlockHidden(string $html, string $block): void
    {
        $node = $this->blockNode($html, $block);
        $classes = preg_split('/\s+/', $node->getAttribute('class'));

        $this->assertContains('hidden', $classes, "Block [{$block}] should be hidden.");
    }

    private function blockNode(string $html, string $block): \DOMElement
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-editor-block="'.$block.'"]');

        $this->assertNotEmpty($nodes, "Block [{$block}] is missing from the editor.");

        return $nodes->item(0);
    }

    public function test_resource_editor_shows_document_and_resource_metadata_only(): void
    {
        $admin = $this->userWithRole('system_admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.contents.create', ['section' => 'resources', 'type' => 'resource']))
            ->assertOk()
            ->getContent();

        foreach (['Document file', 'Resource category', 'File size', 'Page count', 'Download button label'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        foreach (['document', 'resource_meta', 'button_label', 'category', 'body'] as $block) {
            $this->assertBlockVisible($html, $block);
        }

        foreach (['source', 'bio', 'placements', 'location', 'image', 'alt_text', 'button', 'media_library'] as $block) {
            $this->assertBlockHidden($html, $block);
        }
    }

    public function test_news_editor_shows_source_and_placements(): void
    {
        $admin = $this->userWithRole('system_admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.contents.create', ['section' => 'news', 'type' => 'news']))
            ->assertOk()
            ->getContent();

        foreach (['Headline', 'Location', 'Published date', 'Source citation', 'News placement'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        foreach (['source', 'placements', 'location', 'published_on', 'body', 'image', 'alt_text', 'category'] as $block) {
            $this->assertBlockVisible($html, $block);
        }

        foreach (['document', 'bio', 'button', 'media_library', 'bullets'] as $block) {
            $this->assertBlockHidden($html, $block);
        }
    }

    public function test_about_commissioner_card_shows_biography_fields(): void
    {
        $admin = $this->userWithRole('system_admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.contents.create', ['section' => 'about', 'type' => 'card', 'category' => 'Commissioners']))
            ->assertOk()
            ->getContent();

        foreach (['Role', 'Biography sections'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        foreach (['bio', 'image', 'alt_text', 'subtitle', 'body'] as $block) {
            $this->assertBlockVisible($html, $block);
        }

        foreach (['source', 'document', 'bullets', 'button'] as $block) {
            $this->assertBlockHidden($html, $block);
        }
    }

    public function test_about_people_categories_share_the_same_card_fields(): void
    {
        $admin = $this->userWithRole('system_admin');

        foreach (['Commissioners', 'Ex-Officials', 'Management'] as $category) {
            $html = $this->actingAs($admin)
                ->get(route('admin.contents.create', ['section' => 'about', 'type' => 'card', 'category' => $category]))
                ->assertOk()
                ->getContent();

            foreach (['Role', 'Main image', 'Biography sections'] as $label) {
                $this->assertStringContainsString($label, $html, "{$category} editor should show [{$label}].");
            }

            foreach (['subtitle', 'image', 'bio'] as $block) {
                $this->assertBlockVisible($html, $block);
            }

            $this->assertStringNotContainsString('Organogram', $html);
        }
    }

    public function test_advertise_sponsored_ad_shows_expiry_and_feature_toggle(): void
    {
        $admin = $this->userWithRole('system_admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.contents.create', ['section' => 'advertise', 'type' => 'card', 'category' => 'Sponsored Ads']))
            ->assertOk()
            ->getContent();

        foreach (['Campaign ends', 'Feature in the advertise hero'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        foreach (['ad_expiry', 'button', 'image', 'body'] as $block) {
            $this->assertBlockVisible($html, $block);
        }

        foreach (['bio', 'source', 'tier'] as $block) {
            $this->assertBlockHidden($html, $block);
        }
    }

    public function test_donate_category_options_include_all_seeded_groups(): void
    {
        $admin = $this->userWithRole('system_admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.contents.create', ['section' => 'donate', 'type' => 'card']))
            ->assertOk()
            ->getContent();

        foreach ([
            'Giving Options',
            'Even a little Donation makes a big Difference',
            'Where will your donation go',
            'Watch Our Story',
            'Thanks to our supporters',
            "FAQs about donating to National Children's Commission",
        ] as $category) {
            $this->assertStringContainsString('<option value="'.e($category), $html);
        }
    }

    public function test_childrens_corner_offers_resource_type_and_icon_field(): void
    {
        $admin = $this->userWithRole('system_admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.contents.create', ['section' => 'childrens-corner', 'type' => 'card', 'category' => 'Activities']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<option value="resource"', $html);
        $this->assertStringContainsString('Icon class', $html);
        $this->assertBlockVisible($html, 'subtitle');
    }

    public function test_home_update_shows_accent_and_read_more_only(): void
    {
        $admin = $this->userWithRole('system_admin');

        $html = $this->actingAs($admin)
            ->get(route('admin.contents.create', ['section' => 'home', 'type' => 'update']))
            ->assertOk()
            ->getContent();

        foreach (['Accent colour', 'Read more label'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        foreach (['accent', 'button_label', 'body', 'image'] as $block) {
            $this->assertBlockVisible($html, $block);
        }

        foreach (['source', 'bio', 'location', 'published_on', 'button'] as $block) {
            $this->assertBlockHidden($html, $block);
        }
    }

    public function test_editor_states_page_and_type_in_context_banner(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->get(route('admin.contents.create', ['section' => 'resources', 'type' => 'resource']))
            ->assertOk()
            ->assertSee('Showing fields used by')
            ->assertSee('<strong data-banner-type>Resource document</strong>', false)
            ->assertSee('<strong>Resources</strong>', false);
    }

    public function test_add_new_only_offers_types_the_page_renders(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'resources']))
            ->assertOk()
            ->assertSee('Page header')
            ->assertSee('Resource document')
            ->assertSee('A downloadable PDF listed under Resources')
            ->assertDontSee('<option value="card">', false);

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'news']))
            ->assertOk()
            ->assertSee('News article')
            ->assertSee('<option value="news"', false)
            ->assertSee('News Item')
            ->assertDontSee('<option value="hero">', false);
    }

    public function test_page_list_has_view_page_link_and_category_filter(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'donate']))
            ->assertOk()
            ->assertSee('View page')
            ->assertSee('<select name="category"', false)
            ->assertSee('Giving Options')
            ->assertSee('Watch Our Story');

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'home']))
            ->assertOk()
            ->assertSee('View page')
            ->assertDontSee('name="category"', false);
    }

    public function test_editor_emits_the_schema_for_client_side_block_switching(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->get(route('admin.contents.create', ['section' => 'about', 'type' => 'card']))
            ->assertOk()
            ->assertSee('window.cmsEditorSchema', false)
            ->assertSee('category_blocks', false)
            ->assertSee('data-editor-block="identity"', false);
    }

    public function test_editor_autosave_key_and_preview_link_are_preserved(): void
    {
        $admin = $this->userWithRole('system_admin');
        $content = SiteContent::create([
            'section' => 'about',
            'type' => 'card',
            'slug' => 'autosave-check',
            'title' => 'Autosave Check',
            'category' => 'Commissioners',
            'is_published' => false,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contents.edit', ['section' => 'about', 'id' => $content->id]))
            ->assertOk()
            ->assertSee('cms-editor-about-'.$content->id.'-card')
            ->assertSee('Preview Draft')
            ->assertSee(route('admin.contents.preview', ['section' => 'about', 'id' => $content->id]), false);
    }

    public function test_sidebar_groups_website_pages_with_icons(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Website pages')
            ->assertSee('fa-house', false)
            ->assertSee('fa-circle-info', false)
            ->assertSee('fa-bullhorn', false);
    }

    public function test_index_category_filter_filters_rows(): void
    {
        $admin = $this->userWithRole('system_admin');

        SiteContent::create([
            'section' => 'donate',
            'type' => 'card',
            'slug' => 'donation-faq',
            'title' => 'Donation FAQ Item',
            'category' => "FAQs about donating to National Children's Commission",
            'is_published' => true,
            'sort_order' => 0,
        ]);

        SiteContent::create([
            'section' => 'donate',
            'type' => 'card',
            'slug' => 'donation-story',
            'title' => 'Donation Story Item',
            'category' => 'Watch Our Story',
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'donate', 'category' => 'Watch Our Story']))
            ->assertOk()
            ->assertSee('Donation Story Item')
            ->assertDontSee('Donation FAQ Item');
    }
}
