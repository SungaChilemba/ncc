<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use App\Support\CmsEditorSchema;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsConfigParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_seeded_section_type_and_category_is_valid_for_its_page(): void
    {
        $rows = (new SiteContentSeeder)->rows();

        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $section = $row['section'];
            $type = $row['type'];
            $category = $row['category'] ?? null;
            $slug = $row['slug'] ?? 'unknown';

            $this->assertArrayHasKey(
                $section,
                config('cms.sections'),
                "Seeded row [{$slug}] uses unknown section [{$section}]."
            );

            $this->assertContains(
                $type,
                config("cms.sections.{$section}.types"),
                "Seeded row [{$slug}] uses type [{$type}] which is not allowed for [{$section}]."
            );

            if ($category !== null) {
                $options = CmsEditorSchema::categoryOptions($section, $type);

                if ($options !== null) {
                    $this->assertArrayHasKey(
                        $category,
                        $options,
                        "Seeded row [{$slug}] uses category [{$category}] which is not configured for [{$section}]."
                    );
                }
            }
        }
    }

    public function test_prune_command_dry_run_keeps_rows_and_real_run_deletes_only_unconfigured_types(): void
    {
        $valid = SiteContent::create([
            'section' => 'news',
            'type' => 'news',
            'slug' => 'valid-news',
            'title' => 'Valid News',
            'is_published' => true,
        ]);

        $invalid = SiteContent::create([
            'section' => 'news',
            'type' => 'hero',
            'slug' => 'invalid-hero',
            'title' => 'Invalid Hero',
            'is_published' => true,
        ]);

        $account = SiteContent::create([
            'section' => 'home',
            'type' => 'card',
            'slug' => 'invalid-card',
            'title' => 'Invalid Card',
            'is_published' => true,
        ]);

        $this->artisan('cms:prune-invalid-types', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('site_contents', ['id' => $invalid->id]);
        $this->assertDatabaseHas('site_contents', ['id' => $account->id]);

        $this->artisan('cms:prune-invalid-types')->assertSuccessful();

        $this->assertDatabaseMissing('site_contents', ['id' => $invalid->id]);
        $this->assertDatabaseMissing('site_contents', ['id' => $account->id]);
        $this->assertDatabaseHas('site_contents', ['id' => $valid->id]);
    }
}
