<?php

namespace Tests\Feature;

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
                'item_label' => ['Reach'],
                'item_value' => ['National'],
            ])
            ->assertRedirect(route('admin.contents.index', ['section' => 'news']));

        $content = SiteContent::where('title', 'Structured Story')->firstOrFail();

        $this->assertSame('NCC Source', $content->data['source']['name']);
        $this->assertSame('https://example.com/story', $content->data['source']['url']);
        $this->assertSame('National', $content->data['items'][0]['value']);
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
