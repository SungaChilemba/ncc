<?php

namespace Tests\Feature;

use App\Support\CmsEditorSchema;
use Tests\TestCase;

class CmsEditorSchemaTest extends TestCase
{
    public function test_final_type_inventory_matches_the_plan(): void
    {
        $expected = [
            'home' => ['hero', 'update', 'news'],
            'about' => ['hero', 'card', 'stat'],
            'what-we-do' => ['hero', 'card', 'stat', 'cta'],
            'childrens-corner' => ['hero', 'card', 'gallery', 'news', 'resource', 'cta'],
            'reporting' => ['hero', 'card'],
            'resources' => ['hero', 'resource'],
            'news' => ['news'],
            'donate' => ['hero', 'card', 'stat', 'cta'],
            'advertise' => ['hero', 'card', 'stat', 'cta', 'submission'],
        ];

        foreach ($expected as $section => $types) {
            $this->assertSame($types, config("cms.sections.{$section}.types"), "Unexpected type list for {$section}.");
        }
    }

    public function test_every_configured_type_has_editor_metadata(): void
    {
        foreach (config('cms.sections') as $section => $sectionConfig) {
            foreach ($sectionConfig['types'] as $type) {
                $this->assertArrayHasKey($type, config('cms.types'), "{$section}.{$type} is missing a global type label.");
                $this->assertArrayHasKey(
                    $type,
                    $sectionConfig['type_options'] ?? [],
                    "{$section}.{$type} is missing type_options."
                );

                $schema = CmsEditorSchema::for($section, $type);

                $this->assertSame($section, $schema['section']);
                $this->assertSame($type, $schema['type']);
                $this->assertNotEmpty($schema['blocks'], "{$section}.{$type} has no blocks.");
                $this->assertNotEmpty($schema['fields'], "{$section}.{$type} has no fields.");
            }
        }
    }

    public function test_unknown_types_fall_back_to_the_first_section_type(): void
    {
        $this->assertSame('hero', CmsEditorSchema::for('about', 'not-a-type')['type']);
        $this->assertSame([], CmsEditorSchema::for('not-a-section', 'hero'));
    }

    public function test_about_people_categories_share_the_same_card_fields(): void
    {
        $expected = [
            'category',
            'title',
            'subtitle',
            'description',
            'image',
            'alt_text',
            'bio_heading',
            'bio_body',
            'sort_order',
        ];

        foreach (['Commissioners', 'Ex-Officials', 'Management'] as $category) {
            $schema = CmsEditorSchema::for('about', 'card', $category);

            foreach ($expected as $field) {
                $this->assertContains($field, $schema['fields'], "{$category} is missing {$field}.");
            }

            foreach (['source_name', 'button_text', 'bullets', 'document'] as $field) {
                $this->assertNotContains($field, $schema['fields'], "{$category} should not show {$field}.");
            }

            $this->assertSame('Role', $schema['field_meta']['subtitle']['label']);
            $this->assertSame('Fallback biography', $schema['field_meta']['description']['label']);
        }
    }

    public function test_about_category_options_do_not_offer_the_retired_organogram(): void
    {
        $options = CmsEditorSchema::categoryOptions('about', 'card');

        $this->assertArrayHasKey('Management', $options);
        $this->assertArrayHasKey('Ex-Officials', $options);
        $this->assertArrayNotHasKey('Organogram', $options);
    }

    public function test_about_story_cards_use_bullets_instead_of_biography_fields(): void
    {
        foreach (['Mandate, Mission & Objectives', 'Mandate, Mission & Vision'] as $category) {
            $schema = CmsEditorSchema::for('about', 'card', $category);

            $this->assertContains('bullets', $schema['fields']);
            $this->assertContains('slug', $schema['fields']);
            $this->assertContains('image', $schema['fields']);
            $this->assertNotContains('bio_heading', $schema['fields']);
            $this->assertNotContains('subtitle', $schema['fields']);
            $this->assertNotContains('button_text', $schema['fields']);
        }
    }

    public function test_donate_category_options_include_all_seeded_groups(): void
    {
        $options = CmsEditorSchema::categoryOptions('donate', 'card');

        foreach ([
            'Giving Options',
            'Even a little Donation makes a big Difference',
            'Where will your donation go',
            'Watch Our Story',
            'Thanks to our supporters',
            "FAQs about donating to National Children's Commission",
        ] as $category) {
            $this->assertArrayHasKey($category, $options);
        }
    }

    public function test_donate_categories_only_show_fields_the_page_renders(): void
    {
        $where = CmsEditorSchema::for('donate', 'card', 'Where will your donation go');
        $this->assertContains('allocation_direct', $where['fields']);
        $this->assertContains('allocation_fundraising', $where['fields']);
        $this->assertNotContains('image', $where['fields']);
        $this->assertSame('Allocation text', $where['field_meta']['description']['label']);

        $story = CmsEditorSchema::fieldsFor('donate', 'card', 'Watch Our Story');
        $this->assertContains('youtube_url', $story);
        $this->assertContains('description', $story);
        $this->assertNotContains('image', $story);

        $donation = CmsEditorSchema::fieldsFor('donate', 'card', 'Even a little Donation makes a big Difference');
        $this->assertContains('image', $donation);
        $this->assertContains('alt_text', $donation);
        $this->assertNotContains('description', $donation);
        $this->assertNotContains('button_text', $donation);

        $faq = CmsEditorSchema::fieldsFor('donate', 'card', "FAQs about donating to National Children's Commission");
        $this->assertContains('description', $faq);
        $this->assertNotContains('image', $faq);

        $giving = CmsEditorSchema::fieldsFor('donate', 'card', 'Giving Options');
        $this->assertContains('image', $giving);
        $this->assertContains('button_text', $giving);
        $this->assertContains('item_label', $giving);
    }

    public function test_advertise_tiers_and_sponsored_ads_have_specific_fields(): void
    {
        $tier = CmsEditorSchema::for('advertise', 'card', 'Sponsorship Opportunities');
        $this->assertContains('price', $tier['fields']);
        $this->assertContains('badge', $tier['fields']);
        $this->assertContains('reach', $tier['fields']);
        $this->assertContains('tier_placement', $tier['fields']);
        $this->assertContains('tier_include', $tier['fields']);
        $this->assertNotContains('description', $tier['fields']);

        $ad = CmsEditorSchema::for('advertise', 'card', 'Sponsored Ads');
        $this->assertContains('ends_at', $ad['fields']);
        $this->assertContains('hero_featured', $ad['fields']);
        $this->assertContains('button_url', $ad['fields']);
        $this->assertNotContains('bio_heading', $ad['fields']);
    }

    public function test_news_uses_datalists_and_source_placement_fields(): void
    {
        $schema = CmsEditorSchema::for('news', 'news');

        $this->assertTrue($schema['category_allow_custom']);
        $this->assertSame('datalist', $schema['field_meta']['category']['control']);
        $this->assertSame('datalist', $schema['field_meta']['location']['control']);
        $this->assertContains('source_name', $schema['fields']);
        $this->assertContains('news_placement', $schema['fields']);
        $this->assertContains('published_on', $schema['fields']);
    }

    public function test_resources_resource_only_shows_document_fields(): void
    {
        $fields = CmsEditorSchema::fieldsFor('resources', 'resource');

        foreach (['document', 'size', 'pages', 'button_text', 'category'] as $field) {
            $this->assertContains($field, $fields);
        }

        foreach (['image', 'alt_text', 'button_url', 'source_name'] as $field) {
            $this->assertNotContains($field, $fields);
        }
    }

    public function test_advertise_submission_is_private_and_uses_enquiry_metadata(): void
    {
        $schema = CmsEditorSchema::for('advertise', 'submission');

        $this->assertFalse($schema['can_publish']);
        $this->assertContains('enquiry_status', $schema['fields']);
        $this->assertContains('company', $schema['fields']);
        $this->assertContains('ends_at', $schema['fields']);
        $this->assertNotContains('publish_mode', $schema['fields']);
    }

    public function test_accent_colours_include_red_and_resolve_to_options(): void
    {
        $meta = CmsEditorSchema::fieldMeta('accent_color');

        $this->assertArrayHasKey('red', $meta['options']);
        $this->assertSame('Red', $meta['options']['red']);
    }
}
