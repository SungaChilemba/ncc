<?php

namespace Tests\Feature;

use App\Models\ContentAudit;
use App\Models\MediaAsset;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_requires_admin_access(): void
    {
        $this->get(route('admin.dashboard'))->assertForbidden();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_editor_can_access_cms_without_full_admin_flag(): void
    {
        $editor = $this->userWithRole('cms_editor');

        $this->actingAs($editor)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Content Studio');
    }

    public function test_admin_can_view_cms_dashboard_and_section_editor(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Content Studio')
            ->assertSee('Website Sections')
            ->assertSee('Media assets');

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'home']))
            ->assertOk()
            ->assertSee('All Home')
            ->assertSee('Add New')
            ->assertSee('Top Story');
    }

    public function test_dashboard_recent_activity_shows_item_actor_and_action(): void
    {
        $admin = $this->userWithRole('system_admin');
        $content = SiteContent::create([
            'section' => 'about',
            'type' => 'card',
            'title' => 'Commissioner Grace Banda',
            'category' => 'Commissioners',
            'is_published' => true,
        ]);

        ContentAudit::create([
            'site_content_id' => $content->id,
            'user_id' => $admin->id,
            'action' => 'revision_restored',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Recent activity')
            ->assertSee('Commissioner Grace Banda')
            ->assertSee('Revision restored')
            ->assertSee('About · Card · by '.$admin->name)
            ->assertSee(route('admin.contents.edit', ['section' => 'about', 'id' => $content->id]), false)
            ->assertDontSee('Publishing Audit')
            ->assertDontSee('Recent Activity');
    }

    public function test_dashboard_recent_activity_shows_each_item_once(): void
    {
        $admin = $this->userWithRole('system_admin');
        $content = SiteContent::create([
            'section' => 'home',
            'type' => 'update',
            'title' => 'Repeated Campaign Story',
        ]);

        foreach (range(1, 3) as $ignored) {
            ContentAudit::create([
                'site_content_id' => $content->id,
                'user_id' => $admin->id,
                'action' => 'updated',
                'changes' => ['title' => ['from' => 'Old', 'to' => 'New']],
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'Repeated Campaign Story'));
    }

    public function test_dashboard_recent_activity_keeps_context_for_deleted_items(): void
    {
        $admin = $this->userWithRole('system_admin');
        $content = SiteContent::create([
            'section' => 'about',
            'type' => 'card',
            'title' => 'Former Commissioner',
            'category' => 'Commissioners',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.contents.destroy', ['section' => 'about', 'id' => $content->id]))
            ->assertRedirect(route('admin.contents.index', ['section' => 'about']));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Deleted')
            ->assertSee('Former Commissioner')
            ->assertSee('About · Card · by '.$admin->name)
            ->assertDontSee(route('admin.contents.edit', ['section' => 'about', 'id' => $content->id]), false);
    }

    public function test_admin_can_create_search_filter_and_publish_content(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'home']), [
                'type' => 'update',
                'title' => 'New Protection Campaign',
                'subtitle' => 'Lilongwe',
                'description' => 'NCC launches a new child protection awareness campaign.',
                'accent_color' => 'green',
                'button_text' => 'Read More',
                'button_url' => '/news',
                'sort_order' => 2,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'home']));

        $content = SiteContent::where('title', 'New Protection Campaign')->firstOrFail();

        $this->assertTrue($content->is_published);
        $this->assertNotNull($content->published_at);

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'home', 'q' => 'Protection', 'type' => 'update', 'status' => 'published']))
            ->assertOk()
            ->assertSee('New Protection Campaign');

        $this->actingAs($admin)
            ->post(route('admin.contents.toggle-publish', ['section' => 'home', 'id' => $content->id]))
            ->assertRedirect(route('admin.contents.index', ['section' => 'home']));

        $this->assertFalse($content->fresh()->is_published);
    }

    public function test_admin_create_and_edit_use_dedicated_editor_workspace(): void
    {
        $admin = $this->userWithRole('system_admin');
        $content = SiteContent::create([
            'section' => 'home',
            'type' => 'update',
            'title' => 'Dedicated Editor Test',
            'description' => 'This item should open in the full editor workspace.',
            'accent_color' => 'green',
            'preview_token' => 'dedicated-editor-preview-token',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contents.create', ['section' => 'home']))
            ->assertOk()
            ->assertSee('Add New Content')
            ->assertSee('Structured Fields')
            ->assertSee('Publish');

        $this->actingAs($admin)
            ->get(route('admin.contents.edit', ['section' => 'home', 'id' => $content->id]))
            ->assertOk()
            ->assertSee('Edit Content')
            ->assertSee('Dedicated Editor Test')
            ->assertSee('Save Changes');
    }

    public function test_admin_resource_entries_get_slugs_and_show_preview_actions(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'resources']), [
                'type' => 'resource',
                'title' => 'Annual Child Protection Report',
                'description' => 'A downloadable CMS report.',
                'accent_color' => 'green',
                'sort_order' => 0,
                'is_published' => '1',
                'document' => UploadedFile::fake()->create('report.pdf', 32, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'resources']));

        $content = SiteContent::where('title', 'Annual Child Protection Report')->firstOrFail();

        $this->assertSame('annual-child-protection-report', $content->slug);
        $this->assertNotNull($content->file_path);
        Storage::disk('public')->assertExists($content->file_path);

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'resources']))
            ->assertOk()
            ->assertSee(route('resources.show', $content->slug), false)
            ->assertSee(route('resources.download', $content->slug), false);
    }

    public function test_media_library_uploads_assets_for_reuse(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'asset' => UploadedFile::fake()->image('hero.jpg', 1200, 700),
                'alt_text' => 'Children attending an NCC event',
                'caption' => 'NCC field visit',
                'description' => 'Reusable homepage and news image.',
            ])
            ->assertRedirect(route('admin.media.index'));

        $this->actingAs($admin)
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('Children attending an NCC event')
            ->assertSee('NCC field visit')
            ->assertSee('Reusable homepage and news image.');
    }

    public function test_admin_can_update_replace_and_delete_unused_media(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'asset' => UploadedFile::fake()->image('old.jpg', 600, 400),
                'alt_text' => 'Old alt text',
            ]);

        $asset = MediaAsset::firstOrFail();
        $oldPath = $asset->path;

        $this->actingAs($admin)
            ->put(route('admin.media.update', $asset), [
                'asset' => UploadedFile::fake()->image('new.webp', 800, 500),
                'alt_text' => 'Updated alt text',
                'caption' => 'Updated caption',
                'description' => 'Updated media notes.',
            ])
            ->assertRedirect(route('admin.media.index'));

        $asset->refresh();

        $this->assertSame('new.webp', $asset->original_name);
        $this->assertSame('Updated alt text', $asset->alt_text);
        $this->assertSame('Updated caption', $asset->caption);
        $this->assertSame('Updated media notes.', $asset->description);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($asset->path);

        $path = $asset->path;

        $this->actingAs($admin)
            ->delete(route('admin.media.destroy', $asset))
            ->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_schedule_content_and_future_items_stay_hidden_publicly(): void
    {
        $admin = $this->userWithRole('system_admin');
        $future = now()->addDay()->format('Y-m-d\TH:i');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'home']), [
                'type' => 'update',
                'title' => 'Scheduled Protection Update',
                'description' => 'This should not appear publicly until the scheduled time.',
                'accent_color' => 'green',
                'publish_mode' => 'scheduled',
                'publish_at' => $future,
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'home']));

        $content = SiteContent::where('title', 'Scheduled Protection Update')->firstOrFail();

        $this->assertTrue($content->is_published);
        $this->assertTrue($content->published_at->isFuture());

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Scheduled Protection Update');
    }

    public function test_admin_structured_fields_create_source_items_and_bio_sections(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'news']), [
                'type' => 'news',
                'title' => 'Structured Story',
                'description' => 'Story body.',
                'accent_color' => 'green',
                'publish_mode' => 'published',
                'source_name' => 'NCC Source',
                'source_url' => 'https://example.com/story',
                'source_posted_on' => '2026-09-01',
                'source_excerpt' => 'Source excerpt',
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'news']));

        $news = SiteContent::where('title', 'Structured Story')->firstOrFail();

        $this->assertSame('NCC Source', $news->data['source']['name']);
        $this->assertSame('https://example.com/story', $news->data['source']['url']);

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'what-we-do']), [
                'type' => 'card',
                'category' => 'How We Work',
                'title' => 'Structured Programme',
                'description' => 'Programme body.',
                'publish_mode' => 'published',
                'item_label' => ['Reach'],
                'item_value' => ['National'],
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'what-we-do']));

        $card = SiteContent::where('title', 'Structured Programme')->firstOrFail();

        $this->assertSame('National', $card->data['items'][0]['value']);

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'about']), [
                'type' => 'card',
                'category' => 'Commissioners',
                'title' => 'Structured Commissioner',
                'description' => 'Biography fallback.',
                'publish_mode' => 'published',
                'bio_heading' => ['Professional Background'],
                'bio_body' => ['Two decades of child protection experience.'],
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'about']));

        $person = SiteContent::where('title', 'Structured Commissioner')->firstOrFail();

        $this->assertSame('Professional Background', $person->data['bio_sections'][0]['heading']);
        $this->assertSame('Two decades of child protection experience.', $person->data['bio_sections'][0]['body']);
    }

    public function test_admin_saves_sponsorship_tier_and_sponsored_ad_fields(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'advertise']), [
                'type' => 'card',
                'category' => 'Sponsorship Opportunities',
                'title' => 'Gold Package',
                'subtitle' => 'Gold',
                'publish_mode' => 'published',
                'price' => 'Mkw 500,000',
                'badge' => 'Best value',
                'reach' => '500,000 people',
                'tier_placement' => ["Homepage banner\nNewsletter"],
                'tier_include' => ["Logo placement\nTwo social posts"],
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'advertise']));

        $tier = SiteContent::where('title', 'Gold Package')->firstOrFail();

        $this->assertSame('Mkw 500,000', $tier->data['price']);
        $this->assertSame('Best value', $tier->data['badge']);
        $this->assertSame('500,000 people', $tier->data['reach']);
        $this->assertSame(['Homepage banner', 'Newsletter'], $tier->data['placements']);
        $this->assertSame(['Logo placement', 'Two social posts'], $tier->data['includes']);

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'advertise']), [
                'type' => 'card',
                'category' => 'Sponsored Ads',
                'title' => 'Summer Campaign',
                'description' => 'Sponsored ad copy.',
                'publish_mode' => 'published',
                'ends_at' => '2026-12-31',
                'hero_featured' => '1',
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'advertise']));

        $ad = SiteContent::where('title', 'Summer Campaign')->firstOrFail();

        $this->assertSame('2026-12-31', $ad->data['ends_at']);
        $this->assertTrue($ad->data['is_featured']);
    }

    public function test_admin_saves_news_placements_and_custom_categories(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'news']), [
                'type' => 'news',
                'title' => 'Local Voices Story',
                'category' => 'Local Voices',
                'location' => 'Lilongwe',
                'publish_mode' => 'published',
                'news_placement' => ['hero', 'featured'],
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'news']));

        $news = SiteContent::where('title', 'Local Voices Story')->firstOrFail();

        $this->assertSame('Local Voices', $news->category);
        $this->assertSame('Lilongwe', $news->location);
        $this->assertSame(['hero', 'featured'], $news->data['placements']);
    }

    public function test_admin_saves_donation_allocation_video_and_resource_metadata(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'donate']), [
                'type' => 'card',
                'category' => 'Where will your donation go',
                'title' => 'Supporting Our Work',
                'description' => 'How the money is split.',
                'publish_mode' => 'published',
                'allocation_direct' => 80,
                'allocation_fundraising' => 20,
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'donate']));

        $allocation = SiteContent::where('title', 'Supporting Our Work')->firstOrFail();

        $this->assertSame(80, $allocation->data['allocation']['direct']);
        $this->assertSame(20, $allocation->data['allocation']['fundraising']);

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'donate']), [
                'type' => 'card',
                'category' => 'Watch Our Story',
                'title' => 'Watch Our Story',
                'description' => 'Video story.',
                'publish_mode' => 'published',
                'youtube_url' => 'https://www.youtube.com/embed/abc123',
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'donate']));

        $video = SiteContent::where('title', 'Watch Our Story')->firstOrFail();

        $this->assertSame('https://www.youtube.com/embed/abc123', $video->data['youtube_url']);

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'resources']), [
                'type' => 'resource',
                'category' => 'Reports',
                'title' => 'Annual Report 2026',
                'publish_mode' => 'published',
                'size' => '2.4 MB',
                'pages' => 32,
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'resources']));

        $resource = SiteContent::where('title', 'Annual Report 2026')->firstOrFail();

        $this->assertSame('2.4 MB', $resource->data['size']);
        $this->assertSame(32, $resource->data['pages']);
    }

    public function test_admin_saves_about_bullets_and_ad_enquiry_metadata(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'about']), [
                'type' => 'card',
                'category' => 'Mandate, Mission & Objectives',
                'title' => 'Our Mandate',
                'publish_mode' => 'published',
                'bullets' => ["Hold duty bearers accountable.\nPromote child rights."],
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'about']));

        $story = SiteContent::where('title', 'Our Mandate')->firstOrFail();

        $this->assertSame(['Hold duty bearers accountable.', 'Promote child rights.'], $story->data['bullets']);

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'advertise']), [
                'type' => 'submission',
                'title' => 'NCC-AD-10001',
                'description' => 'Enquiry details.',
                'enquiry_status' => 'approved',
                'company' => 'Acme Malawi',
                'ends_at' => '2026-12-31',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'advertise']));

        $submission = SiteContent::where('title', 'NCC-AD-10001')->firstOrFail();

        $this->assertFalse($submission->is_published);
        $this->assertNull($submission->published_at);
        $this->assertSame('approved', $submission->data['status']);
        $this->assertSame('Acme Malawi', $submission->data['company']);
        $this->assertSame('2026-12-31', $submission->data['ends_at']);
    }

    public function test_hidden_blocks_do_not_persist_for_other_types(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'home']), [
                'type' => 'update',
                'title' => 'No Leaking Fields',
                'description' => 'Homepage story body.',
                'publish_mode' => 'published',
                'item_label' => ['Reach'],
                'item_value' => ['National'],
                'source_name' => 'Hidden source',
                'source_url' => 'https://example.com/hidden',
                'price' => 'Hidden price',
                'bullets' => ['Hidden bullet'],
                'stat_icon' => 'fa-solid fa-hidden',
                'allocation_direct' => 75,
                'size' => '1 MB',
                'pages' => 5,
                'youtube_url' => 'https://www.youtube.com/embed/hidden',
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'home']));

        $content = SiteContent::where('title', 'No Leaking Fields')->firstOrFail();
        $data = $content->data ?? [];

        foreach (['items', 'source', 'price', 'bullets', 'icon', 'allocation', 'size', 'pages', 'youtube_url'] as $key) {
            $this->assertArrayNotHasKey($key, $data, "Hidden field [{$key}] leaked into saved data.");
        }
    }

    public function test_fixed_category_lists_are_strict_and_custom_lists_are_allowed(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'resources']), [
                'type' => 'resource',
                'category' => 'Not a real category',
                'title' => 'Rejected Resource',
            ])
            ->assertSessionHasErrors('category');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'about']), [
                'type' => 'card',
                'category' => 'Organogram',
                'title' => 'Rejected About Card',
            ])
            ->assertSessionHasErrors('category');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'news']), [
                'type' => 'news',
                'category' => 'Custom News Category',
                'title' => 'Custom Category Story',
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'news']));

        $this->assertDatabaseHas('site_contents', [
            'section' => 'news',
            'title' => 'Custom Category Story',
            'category' => 'Custom News Category',
        ]);
    }

    public function test_admin_updates_create_revisions_and_can_restore_them(): void
    {
        $admin = $this->userWithRole('system_admin');

        $content = SiteContent::create([
            'section' => 'about',
            'type' => 'card',
            'title' => 'Original Title',
            'description' => 'Original description.',
            'is_published' => true,
            'published_at' => now(),
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.contents.update', ['section' => 'about', 'id' => $content->id]), [
                'type' => 'card',
                'title' => 'Updated Title',
                'description' => 'Updated description.',
                'accent_color' => 'green',
                'publish_mode' => 'published',
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'about']));

        $content->refresh();
        $revision = $content->revisions()->firstOrFail();

        $this->assertSame('Updated Title', $content->title);
        $this->assertSame('Original Title', $revision->snapshot['title']);

        $this->actingAs($admin)
            ->post(route('admin.contents.revisions.restore', [
                'section' => 'about',
                'id' => $content->id,
                'revision' => $revision->id,
            ]))
            ->assertRedirect(route('admin.contents.edit', ['section' => 'about', 'id' => $content->id]));

        $this->assertSame('Original Title', $content->fresh()->title);
    }

    public function test_admin_cannot_delete_media_used_by_cms_content(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'asset' => UploadedFile::fake()->image('used.jpg', 600, 400),
                'alt_text' => 'Used image',
            ]);

        $asset = MediaAsset::firstOrFail();

        SiteContent::create([
            'section' => 'home',
            'type' => 'hero',
            'title' => 'Uses media',
            'media_asset_id' => $asset->id,
            'image_path' => $asset->path,
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.media.destroy', $asset))
            ->assertRedirect(route('admin.media.index'))
            ->assertSessionHas('status', 'Media is currently used by CMS content and was not deleted.');

        $this->assertDatabaseHas('media_assets', ['id' => $asset->id]);
        Storage::disk('public')->assertExists($asset->path);
    }

    public function test_admin_can_reuse_media_bulk_publish_reorder_and_preview_draft(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.media.store'), [
                'asset' => UploadedFile::fake()->image('reuse.jpg', 600, 400),
                'alt_text' => 'Reusable image',
            ]);

        $asset = MediaAsset::firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'about']), [
                'type' => 'card',
                'title' => 'Reusable Media Card',
                'description' => 'Uses a library asset.',
                'data_json' => '{"items":[{"label":"Reach","value":"National"}]}',
                'media_asset_id' => $asset->id,
                'accent_color' => 'green',
                'sort_order' => 3,
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'about']));

        $content = SiteContent::where('title', 'Reusable Media Card')->firstOrFail();

        $this->assertSame($asset->path, $content->image_path);
        $this->assertSame('National', $content->data['items'][0]['value']);
        $this->assertNotNull($content->preview_token);

        $this->actingAs($admin)
            ->post(route('admin.contents.bulk', ['section' => 'about']), [
                'action' => 'unpublish',
                'ids' => [$content->id],
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'about']));

        $this->assertFalse($content->fresh()->is_published);

        $this->actingAs($admin)
            ->post(route('admin.contents.reorder', ['section' => 'about']), [
                'orders' => [$content->id => 1],
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'about']));

        $this->assertSame(1, $content->fresh()->sort_order);

        $this->actingAs($admin)
            ->get(route('admin.contents.preview', ['section' => 'about', 'id' => $content->id]))
            ->assertOk()
            ->assertSee('Reusable Media Card')
            ->assertSee('Structured Data');
    }

    public function test_duplicate_detail_slugs_are_rejected_within_section_and_type(): void
    {
        $admin = $this->userWithRole('system_admin');

        SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'same-story',
            'title' => 'Same Story',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'news']), [
                'type' => 'news',
                'slug' => 'same-story',
                'title' => 'Another Story',
                'accent_color' => 'red',
                'sort_order' => 1,
                'is_published' => '1',
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_admin_image_upload_is_validated_and_stored(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'home']), [
                'type' => 'news',
                'title' => 'Gallery Update',
                'description' => 'A new gallery item.',
                'accent_color' => 'green',
                'sort_order' => 0,
                'is_published' => '1',
                'image' => UploadedFile::fake()->image('children.webp', 800, 450),
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'home']));

        $content = SiteContent::where('title', 'Gallery Update')->firstOrFail();

        $this->assertNotNull($content->image_path);
        Storage::disk('public')->assertExists($content->image_path);
    }

    public function test_unknown_section_and_invalid_type_are_rejected(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->actingAs($admin)
            ->get(route('admin.contents.index', ['section' => 'not-a-page']))
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'home']), [
                'type' => 'gallery',
                'title' => 'Invalid home item',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_reporting_section_rejects_case_submission_content(): void
    {
        $admin = $this->userWithRole('system_admin');

        $this->assertNotContains('submission', config('cms.sections.reporting.types'));

        $this->actingAs($admin)
            ->post(route('admin.contents.store', ['section' => 'reporting']), [
                'type' => 'submission',
                'slug' => 'ncc-2026-90001',
                'title' => 'Report NCC-2026-90001',
                'subtitle' => 'Neglect',
                'description' => 'Sensitive report content.',
                'accent_color' => 'green',
                'is_published' => '1',
            ])
            ->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('site_contents', [
            'section' => 'reporting',
            'slug' => 'ncc-2026-90001',
        ]);

        $card = SiteContent::create([
            'section' => 'reporting',
            'type' => 'card',
            'slug' => 'reporting-guidance',
            'title' => 'Reporting guidance',
            'description' => 'Public guidance copy.',
            'is_published' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.contents.update', ['section' => 'reporting', 'id' => $card->id]), [
                'type' => 'submission',
                'slug' => 'ncc-2026-90001',
                'title' => 'Report NCC-2026-90001',
                'subtitle' => 'Neglect',
                'description' => 'Updated sensitive report content.',
                'accent_color' => 'green',
                'is_published' => '1',
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame('card', $card->fresh()->type);
    }

    public function test_report_submissions_cannot_be_published_by_toggle_or_bulk_action(): void
    {
        $admin = $this->userWithRole('system_admin');

        $submission = SiteContent::create([
            'section' => 'reporting',
            'type' => 'submission',
            'slug' => 'ncc-2026-90002',
            'title' => 'Report NCC-2026-90002',
            'subtitle' => 'Abuse',
            'description' => 'Sensitive report content.',
            'is_published' => false,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.contents.toggle-publish', ['section' => 'reporting', 'id' => $submission->id]))
            ->assertRedirect(route('admin.contents.index', ['section' => 'reporting']))
            ->assertSessionHas('status', 'Report submissions cannot be published.');

        $this->assertFalse($submission->fresh()->is_published);
        $this->assertNull($submission->fresh()->published_at);

        $this->actingAs($admin)
            ->post(route('admin.contents.bulk', ['section' => 'reporting']), [
                'action' => 'publish',
                'ids' => [$submission->id],
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'reporting']));

        $this->assertFalse($submission->fresh()->is_published);
        $this->assertNull($submission->fresh()->published_at);
    }
}
