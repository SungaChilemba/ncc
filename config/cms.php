<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content types
    |--------------------------------------------------------------------------
    |
    | Global type labels. Each section narrows this list through its own
    | "types" key below, so a type is only available on the pages that
    | actually render it.
    |
    */
    'types' => [
        'hero' => 'Hero',
        'update' => 'Top Story',
        'news' => 'News Item',
        'card' => 'Card',
        'resource' => 'Resource',
        'stat' => 'Statistic',
        'gallery' => 'Gallery Image',
        'cta' => 'Call to Action',
        'submission' => 'Report Submission',
    ],

    /*
    |--------------------------------------------------------------------------
    | Editor blocks
    |--------------------------------------------------------------------------
    |
    | A block is a logical group of form fields. The editor shows only the
    | blocks resolved for the selected section + type + category.
    |
    */
    'blocks' => [
        'identity' => ['type', 'title'],
        'permalink' => ['slug'],
        'subtitle' => ['subtitle'],
        'body' => ['description'],
        'category' => ['category'],
        'location' => ['location'],
        'published_on' => ['published_on'],
        'image' => ['image'],
        'alt_text' => ['alt_text'],
        'media_library' => ['media_asset_id'],
        'document' => ['document'],
        'button' => ['button_text', 'button_url'],
        'button_label' => ['button_text'],
        'accent' => ['accent_color'],
        'sort' => ['sort_order'],
        'source' => ['source_name', 'source_url', 'source_posted_on', 'source_excerpt'],
        'items' => ['item_label', 'item_value'],
        'bio' => ['bio_heading', 'bio_body'],
        'bullets' => ['bullets'],
        'placements' => ['news_placement'],
        'stat_placements' => ['stat_placements'],
        'tier' => ['price', 'badge', 'reach', 'tier_placement', 'tier_include'],
        'ad_expiry' => ['ends_at', 'hero_featured'],
        'stat_icon' => ['stat_icon'],
        'submission_meta' => ['enquiry_status', 'company', 'ends_at'],
        'resource_meta' => ['size', 'pages'],
        'video' => ['youtube_url'],
        'allocation' => ['allocation_direct', 'allocation_fundraising'],
        'json' => ['data_json'],
        'publish' => ['publish_mode', 'publish_at'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field metadata
    |--------------------------------------------------------------------------
    |
    | Default label, help text, control type, and options for each field.
    | Section/type/category overrides are merged by CmsEditorSchema.
    |
    */
    'field_meta' => [
        'type' => [
            'label' => 'Content type',
            'help' => 'What this item represents on the page.',
            'control' => 'select',
        ],
        'title' => [
            'label' => 'Title',
            'control' => 'text',
        ],
        'slug' => [
            'label' => 'URL slug',
            'help' => 'Leave blank to generate from the title.',
            'control' => 'text',
        ],
        'subtitle' => [
            'label' => 'Subtitle',
            'help' => 'Short line above or under the title.',
            'control' => 'text',
        ],
        'description' => [
            'label' => 'Body / description',
            'control' => 'textarea',
        ],
        'category' => [
            'label' => 'Category',
            'help' => 'Groups related items on the page.',
            'control' => 'select',
        ],
        'location' => [
            'label' => 'Location',
            'help' => 'Shown on news cards and article pages.',
            'control' => 'datalist',
        ],
        'published_on' => [
            'label' => 'Published date',
            'help' => 'Displayed date, not the publish switch.',
            'control' => 'date',
        ],
        'image' => [
            'label' => 'Main image',
            'control' => 'file',
        ],
        'alt_text' => [
            'label' => 'Image description (alt text)',
            'help' => 'Describe the image for screen readers.',
            'control' => 'text',
        ],
        'media_asset_id' => [
            'label' => 'Use a media library file',
            'control' => 'media',
        ],
        'document' => [
            'label' => 'Document file',
            'help' => 'PDF, DOC or DOCX up to 10 MB.',
            'control' => 'file',
        ],
        'button_text' => [
            'label' => 'Button label',
            'control' => 'text',
        ],
        'button_url' => [
            'label' => 'Button link',
            'help' => 'Internal path, full URL, or mailto:.',
            'control' => 'text',
        ],
        'accent_color' => [
            'label' => 'Accent colour',
            'control' => 'select',
            'options' => 'cms.accent_colors',
        ],
        'sort_order' => [
            'label' => 'Display order',
            'help' => 'Lower numbers appear first.',
            'control' => 'number',
        ],
        'source_name' => [
            'label' => 'Source name',
            'control' => 'text',
        ],
        'source_url' => [
            'label' => 'Source URL',
            'control' => 'url',
        ],
        'source_posted_on' => [
            'label' => 'Source date',
            'control' => 'date',
        ],
        'source_excerpt' => [
            'label' => 'Visible excerpt',
            'control' => 'textarea',
        ],
        'item_label' => [
            'label' => 'Detail label',
            'control' => 'text',
        ],
        'item_value' => [
            'label' => 'Detail value',
            'control' => 'text',
        ],
        'bio_heading' => [
            'label' => 'Biography heading',
            'control' => 'text',
        ],
        'bio_body' => [
            'label' => 'Biography body',
            'control' => 'textarea',
        ],
        'bullets' => [
            'label' => 'Bullet points',
            'help' => 'One per line; used by the About story tabs.',
            'control' => 'textarea',
        ],
        'news_placement' => [
            'label' => 'News placement',
            'help' => 'Choose where this story can appear.',
            'control' => 'checkboxes',
            'options' => [
                'hero' => 'Hero carousel',
                'featured' => 'Featured slot',
            ],
        ],
        'stat_placements' => [
            'label' => 'Show on pages',
            'help' => 'Tick every page where this statistic should appear.',
            'control' => 'checkboxes',
            'options' => [
                'home' => 'Home',
                'about' => 'About',
                'what-we-do' => 'What We Do',
                'childrens-corner' => "Children's Corner",
                'donate' => 'Donate',
                'advertise' => 'Advertise',
            ],
        ],
        'price' => [
            'label' => 'Package price',
            'control' => 'text',
        ],
        'badge' => [
            'label' => 'Package badge',
            'control' => 'text',
        ],
        'reach' => [
            'label' => 'Package reach',
            'control' => 'text',
        ],
        'tier_placement' => [
            'label' => 'Ad placements',
            'help' => 'One per line.',
            'control' => 'textarea',
        ],
        'tier_include' => [
            'label' => 'What is included',
            'help' => 'One per line.',
            'control' => 'textarea',
        ],
        'ends_at' => [
            'label' => 'Campaign ends',
            'help' => 'Expired adverts stop appearing.',
            'control' => 'date',
        ],
        'hero_featured' => [
            'label' => 'Feature in the advertise hero',
            'control' => 'checkbox',
        ],
        'stat_icon' => [
            'label' => 'Stat icon',
            'help' => 'Font Awesome class, e.g. fa-handshake.',
            'control' => 'text',
        ],
        'enquiry_status' => [
            'label' => 'Enquiry status',
            'control' => 'select',
            'options' => [
                'pending_review' => 'Pending review',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ],
        ],
        'company' => [
            'label' => 'Company / partner',
            'control' => 'text',
        ],
        'size' => [
            'label' => 'File size',
            'control' => 'text',
        ],
        'pages' => [
            'label' => 'Page count',
            'control' => 'number',
        ],
        'youtube_url' => [
            'label' => 'Video URL',
            'help' => 'YouTube or embed URL.',
            'control' => 'url',
        ],
        'allocation_direct' => [
            'label' => 'Direct programme %',
            'control' => 'number',
        ],
        'allocation_fundraising' => [
            'label' => 'Fundraising %',
            'control' => 'number',
        ],
        'data_json' => [
            'label' => 'Advanced JSON',
            'help' => 'Optional structured data for this item.',
            'control' => 'json',
        ],
        'publish_mode' => [
            'label' => 'Publishing',
            'control' => 'select',
            'options' => [
                'draft' => 'Draft',
                'published' => 'Published',
                'scheduled' => 'Scheduled',
            ],
        ],
        'publish_at' => [
            'label' => 'Publish at',
            'control' => 'datetime',
        ],
    ],

    'resource_categories' => [
        'Acts' => 'Acts',
        'Guidelines' => 'Guidelines',
        'Legal Instruments' => 'Legal Instruments',
        'Strategic Policies' => 'Strategic Policies',
        'Stakeholder Tools' => 'Stakeholder Tools',
        'Reports' => 'Reports',
    ],

    'advertise_categories' => [
        'Opportunities' => 'Opportunities',
        'Sponsorship Opportunities' => 'Sponsorship Opportunities',
        'Sponsored Ads' => 'Sponsored Ads',
        'Partnership' => 'Partnership',
        'Guidelines' => 'Guidelines',
        'Why Partner With NCC' => 'Why Partner With NCC',
    ],

    'donate_categories' => [
        'Giving Options' => 'Giving Options',
        'Even a little Donation makes a big Difference' => 'Even a little Donation makes a big Difference',
        'Where will your donation go' => 'Where will your donation go',
        'Watch Our Story' => 'Watch Our Story',
        'Thanks to our supporters' => 'Thanks to our supporters',
        "FAQs about donating to National Children's Commission" => "FAQs about donating to National Children's Commission",
    ],

    'about_categories' => [
        'Mandate, Mission & Objectives' => 'Mandate, Mission & Objectives',
        'Mandate, Mission & Vision' => 'Mandate, Mission & Vision',
        'Commissioners' => 'Commissioners',
        'Ex-Officials' => 'Ex-Officials',
        'Management' => 'Management',
    ],

    'what_we_do_categories' => [
        'How We Work' => 'How We Work',
        'Our Child, Our Responsibility' => 'Our Child, Our Responsibility',
        'Our Programs' => 'Our Programs',
    ],

    'children_categories' => [
        'Activities' => 'Activities',
        'Child Rights' => 'Child Rights',
        'Kids News & Updates' => 'Kids News & Updates',
    ],

    'reporting_categories' => [
        'Reporting Workflow' => 'Reporting Workflow',
    ],

    'news_categories' => [
        'News' => 'News',
        'Monitoring' => 'Monitoring',
        'Child Protection' => 'Child Protection',
        'Policy' => 'Policy',
        'Community Events' => 'Community Events',
        'Partnerships' => 'Partnerships',
        'Child Participation' => 'Child Participation',
    ],

    'locations' => [
        'Balaka',
        'Blantyre',
        'Chikwawa',
        'Chiradzulu',
        'Chitipa',
        'Dedza',
        'Dowa',
        'Karonga',
        'Kasungu',
        'Likoma',
        'Lilongwe',
        'Machinga',
        'Mangochi',
        'Mchinji',
        'Mulanje',
        'Mwanza',
        'Mzimba',
        'Neno',
        'Nkhata Bay',
        'Nkhotakota',
        'Nsanje',
        'Ntcheu',
        'Ntchisi',
        'Phalombe',
        'Rumphi',
        'Salima',
        'Thyolo',
        'Zomba',
        'National',
        'International',
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Font Awesome icon classes
    |--------------------------------------------------------------------------
    |
    | Suggestions for the icon-class datalist shown on cards and statistics.
    |
    */
    'icon_classes' => [
        'fa-solid fa-handshake',
        'fa-solid fa-hand-holding-heart',
        'fa-solid fa-child',
        'fa-solid fa-shield-halved',
        'fa-solid fa-scale-balanced',
        'fa-solid fa-gamepad',
        'fa-solid fa-graduation-cap',
        'fa-solid fa-heart',
        'fa-solid fa-people-group',
        'fa-solid fa-book-open',
        'fa-solid fa-kit-medical',
        'fa-solid fa-paintbrush',
        'fa-solid fa-feather',
        'fa-solid fa-pencil',
        'fa-solid fa-question',
        'fa-solid fa-video',
        'fa-solid fa-circle-info',
        'fa-solid fa-flag',
        'fa-solid fa-folder-open',
        'fa-solid fa-newspaper',
        'fa-solid fa-bullhorn',
        'fa-solid fa-house',
    ],

    /*
    |--------------------------------------------------------------------------
    | Type summaries (Add New chooser)
    |--------------------------------------------------------------------------
    |
    | One-line plain-English description shown beside each content type in
    | the Add New chooser on the page list.
    |
    */
    'type_summaries' => [
        'home' => [
            'hero' => 'The main banner at the top of the homepage.',
            'update' => 'A top story shown in the Home headlines.',
            'news' => 'A news highlight card on the homepage.',
        ],
        'about' => [
            'hero' => 'Page header for the About page.',
            'card' => 'A story tab, commissioner, or management profile.',
            'stat' => 'A headline impact number.',
        ],
        'what-we-do' => [
            'hero' => 'Page header for What We Do.',
            'card' => 'A program or approach card.',
            'stat' => 'A headline impact number.',
            'cta' => 'A call-to-action band.',
        ],
        'childrens-corner' => [
            'hero' => 'Page header for Children\'s Corner.',
            'card' => 'A kids activity or rights card with an icon.',
            'gallery' => 'A photo shown in the gallery strip.',
            'news' => 'A kids news or video item.',
            'resource' => 'A downloadable document for kids and parents.',
            'cta' => 'A call-to-action band.',
        ],
        'reporting' => [
            'hero' => 'Page header for Reporting.',
            'card' => 'A step or guide card for the reporting process.',
        ],
        'resources' => [
            'hero' => 'Page header for Resources.',
            'resource' => 'A downloadable PDF listed under Resources.',
        ],
        'news' => [
            'news' => 'A news article with source and placement.',
        ],
        'donate' => [
            'hero' => 'Page header for Donate.',
            'card' => 'A giving option, story, or FAQ card.',
            'stat' => 'A headline impact number.',
            'cta' => 'A call-to-action band.',
        ],
        'advertise' => [
            'hero' => 'Page header for Advertise.',
            'card' => 'A tier, sponsored ad, or partner card.',
            'stat' => 'A headline partner statistic.',
            'cta' => 'A call-to-action band.',
            'submission' => 'A private advertiser enquiry.',
        ],
    ],

    'accent_colors' => [
        'red' => 'Red',
        'green' => 'Green',
        'blue' => 'Blue',
        'yellow' => 'Yellow',
        'purple' => 'Purple',
        'orange' => 'Orange',
        'slate' => 'Slate',
    ],

    'sections' => [
        'home' => [
            'label' => 'Home',
            'description' => 'Homepage hero, top stories, and news highlights.',
            'route' => 'home',
            'icon' => 'fa-house',
            'types' => ['hero', 'update', 'news', 'stat'],
            'type_options' => [
                'hero' => [
                    'label' => 'Page header',
                    'blocks' => ['identity', 'subtitle', 'body', 'image', 'button', 'sort', 'json', 'publish'],
                    'labels' => [
                        'title' => 'Page heading',
                        'description' => 'Intro paragraph',
                    ],
                ],
                'update' => [
                    'label' => 'Top story',
                    'blocks' => ['identity', 'permalink', 'body', 'image', 'button_label', 'accent', 'sort', 'json', 'publish'],
                    'labels' => [
                        'title' => 'Story headline',
                        'button_text' => 'Read more label',
                    ],
                    'help' => [
                        'slug' => 'Used when the read-more button has no link.',
                    ],
                ],
                'news' => [
                    'label' => 'News highlight',
                    'blocks' => ['identity', 'permalink', 'image', 'alt_text', 'sort', 'json', 'publish'],
                ],
                'stat' => [
                    'label' => 'Impact statistic',
                    'blocks' => ['identity', 'body', 'stat_icon', 'stat_placements', 'sort', 'json', 'publish'],
                    'labels' => [
                        'title' => 'Number',
                        'description' => 'Label',
                        'stat_icon' => 'Icon',
                    ],
                    'help' => [
                        'stat_placements' => 'Tick every page where this statistic should appear. Leave blank to hide everywhere.',
                    ],
                ],
            ],
        ],

        'about' => [
            'label' => 'About',
            'description' => 'Mandate, mission, objectives, commissioners, and management content.',
            'route' => 'about',
            'icon' => 'fa-circle-info',
            'types' => ['hero', 'card'],
            'type_options' => [
                'hero' => [
                    'label' => 'Page header',
                    'blocks' => ['identity', 'subtitle', 'body', 'image', 'sort', 'json', 'publish'],
                ],
                'card' => [
                    'label' => 'Card',
                    'blocks' => ['identity', 'permalink', 'category', 'subtitle', 'body', 'image', 'alt_text', 'button', 'accent', 'sort', 'json', 'publish'],
                    'category_options' => 'cms.about_categories',
                    'labels' => [
                        'subtitle' => 'Role',
                        'description' => 'Fallback biography',
                    ],
                    'category_blocks' => [
                        'Mandate, Mission & Objectives' => ['identity', 'permalink', 'category', 'bullets', 'image', 'sort', 'json', 'publish'],
                        'Mandate, Mission & Vision' => ['identity', 'permalink', 'category', 'bullets', 'image', 'sort', 'json', 'publish'],
                        'Commissioners' => ['identity', 'category', 'subtitle', 'body', 'image', 'alt_text', 'bio', 'sort', 'json', 'publish'],
                        'Ex-Officials' => ['identity', 'category', 'subtitle', 'body', 'image', 'alt_text', 'bio', 'sort', 'json', 'publish'],
                        'Management' => ['identity', 'category', 'subtitle', 'body', 'image', 'alt_text', 'bio', 'sort', 'json', 'publish'],
                    ],
                    'category_labels' => [
                        'Mandate, Mission & Objectives' => [
                            'title' => 'Tab title',
                            'description' => 'Fallback body',
                            'bullets' => 'Story bullet points',
                            'image' => 'Tab background image',
                        ],
                        'Mandate, Mission & Vision' => [
                            'title' => 'Tab title',
                            'description' => 'Fallback body',
                            'bullets' => 'Story bullet points',
                            'image' => 'Tab background image',
                        ],
                    ],
                ],
            ],
        ],

        'what-we-do' => [
            'label' => 'What We Do',
            'description' => 'Programs, service areas, and institutional focus areas.',
            'route' => 'what-we-do',
            'icon' => 'fa-hands-holding-heart',
            'types' => ['hero', 'card', 'cta'],
            'type_options' => [
                'hero' => [
                    'label' => 'Page header',
                    'blocks' => ['identity', 'subtitle', 'body', 'image', 'alt_text', 'sort', 'json', 'publish'],
                ],
                'card' => [
                    'label' => 'Card',
                    'blocks' => ['identity', 'category', 'subtitle', 'body', 'image', 'items', 'button', 'sort', 'json', 'publish'],
                    'category_options' => 'cms.what_we_do_categories',
                ],
                'cta' => [
                    'label' => 'Call to action',
                    'blocks' => ['identity', 'body', 'button', 'sort', 'json', 'publish'],
                ],
            ],
        ],

        'childrens-corner' => [
            'label' => "Children's Corner",
            'description' => 'Child-friendly activities, rights education, stories, and gallery content.',
            'route' => 'childrens-corner',
            'icon' => 'fa-child',
            'types' => ['hero', 'card', 'gallery', 'news', 'resource', 'cta'],
            'type_options' => [
                'hero' => [
                    'label' => 'Page header',
                    'blocks' => ['identity', 'body', 'image', 'alt_text', 'sort', 'json', 'publish'],
                ],
                'card' => [
                    'label' => 'Kids card',
                    'blocks' => ['identity', 'category', 'subtitle', 'body', 'sort', 'json', 'publish'],
                    'category_options' => 'cms.children_categories',
                    'labels' => [
                        'subtitle' => 'Icon class',
                        'description' => 'Short description',
                    ],
                    'help' => [
                        'subtitle' => 'Font Awesome class, e.g. fa-solid fa-gamepad.',
                    ],
                    'category_blocks' => [
                        'Activities' => ['identity', 'category', 'subtitle', 'sort', 'json', 'publish'],
                        'Child Rights' => ['identity', 'category', 'subtitle', 'body', 'sort', 'json', 'publish'],
                    ],
                ],
                'gallery' => [
                    'label' => 'Gallery image',
                    'blocks' => ['identity', 'image', 'alt_text', 'sort', 'json', 'publish'],
                ],
                'news' => [
                    'label' => 'Kids news',
                    'blocks' => ['identity', 'permalink', 'category', 'location', 'published_on', 'body', 'image', 'alt_text', 'button_label', 'sort', 'json', 'publish'],
                    'category_options' => 'cms.children_categories',
                    'labels' => [
                        'button_text' => 'Read more label',
                    ],
                ],
                'resource' => [
                    'label' => 'Kids resource',
                    'blocks' => ['identity', 'category', 'body', 'button', 'sort', 'json', 'publish'],
                    'category_options' => 'cms.resource_categories',
                    'labels' => [
                        'title' => 'Resource title',
                        'category' => 'Resource category',
                        'button_text' => 'Download label',
                    ],
                    'help' => [
                        'button_text' => 'Defaults to "Download" when empty.',
                    ],
                ],
                'cta' => [
                    'label' => 'Call to action',
                    'blocks' => ['identity', 'body', 'button', 'sort', 'json', 'publish'],
                ],
            ],
        ],

        'reporting' => [
            'label' => 'Reporting',
            'description' => 'Reporting instructions, support copy, and response information.',
            'route' => 'reporting',
            'icon' => 'fa-flag',
            // Case submissions live in the dedicated case domain, never in the CMS.
            'types' => ['hero', 'card'],
            'type_options' => [
                'hero' => [
                    'label' => 'Page header',
                    'blocks' => ['identity', 'subtitle', 'body', 'sort', 'json', 'publish'],
                ],
                'card' => [
                    'label' => 'Card',
                    'blocks' => ['identity', 'category', 'subtitle', 'body', 'image', 'button', 'accent', 'sort', 'json', 'publish'],
                    'category_options' => 'cms.reporting_categories',
                    'category_blocks' => [
                        'Reporting Workflow' => ['identity', 'category', 'body', 'sort', 'json', 'publish'],
                    ],
                ],
            ],
        ],

        'resources' => [
            'label' => 'Resources',
            'description' => 'Publications, downloads, guidelines, reports, and learning resources.',
            'route' => 'resources',
            'icon' => 'fa-folder-open',
            'types' => ['hero', 'resource'],
            'type_options' => [
                'hero' => [
                    'label' => 'Page header',
                    'blocks' => ['identity', 'body', 'sort', 'json', 'publish'],
                    'labels' => [
                        'title' => 'Page heading',
                        'description' => 'Intro paragraph',
                    ],
                ],
                'resource' => [
                    'label' => 'Resource document',
                    'blocks' => ['identity', 'permalink', 'category', 'body', 'document', 'resource_meta', 'button_label', 'sort', 'json', 'publish'],
                    'category_options' => 'cms.resource_categories',
                    'labels' => [
                        'title' => 'Document title',
                        'category' => 'Resource category',
                        'button_text' => 'Download button label',
                        'document' => 'Document file',
                    ],
                    'help' => [
                        'category' => 'Controls the public filter. Must match one of the listed categories.',
                        'button_text' => 'Defaults to "Download" when empty.',
                    ],
                    'required' => ['title', 'category'],
                ],
            ],
        ],

        'news' => [
            'label' => 'News',
            'description' => 'News articles, related news, updates, and announcements.',
            'route' => 'news',
            'icon' => 'fa-newspaper',
            'types' => ['news'],
            'type_options' => [
                'news' => [
                    'label' => 'News article',
                    'blocks' => ['identity', 'permalink', 'category', 'location', 'published_on', 'body', 'image', 'alt_text', 'source', 'placements', 'sort', 'json', 'publish'],
                    'category_options' => 'cms.news_categories',
                    'category_allow_custom' => true,
                    'location_options' => 'cms.locations',
                    'location_allow_custom' => true,
                    'labels' => [
                        'title' => 'Headline',
                    ],
                    'required' => ['title'],
                ],
            ],
        ],

        'donate' => [
            'label' => 'Donate',
            'description' => 'Donation page copy, calls to action, giving options, and trust messaging.',
            'route' => 'donate',
            'icon' => 'fa-hand-holding-heart',
            'types' => ['hero', 'card', 'cta'],
            'type_options' => [
                'hero' => [
                    'label' => 'Page header',
                    'blocks' => ['identity', 'subtitle', 'body', 'image', 'alt_text', 'sort', 'json', 'publish'],
                ],
                'card' => [
                    'label' => 'Card',
                    'blocks' => ['identity', 'category', 'subtitle', 'body', 'image', 'button', 'sort', 'json', 'publish'],
                    'category_options' => 'cms.donate_categories',
                    'category_blocks' => [
                        'Giving Options' => ['identity', 'category', 'subtitle', 'body', 'image', 'items', 'button', 'sort', 'json', 'publish'],
                        'Even a little Donation makes a big Difference' => ['identity', 'category', 'image', 'alt_text', 'sort', 'json', 'publish'],
                        'Where will your donation go' => ['identity', 'category', 'body', 'allocation', 'sort', 'json', 'publish'],
                        'Watch Our Story' => ['identity', 'category', 'body', 'video', 'sort', 'json', 'publish'],
                        'Thanks to our supporters' => ['identity', 'category', 'body', 'sort', 'json', 'publish'],
                        "FAQs about donating to National Children's Commission" => ['identity', 'category', 'body', 'sort', 'json', 'publish'],
                    ],
                    'category_labels' => [
                        'Giving Options' => [
                            'subtitle' => 'Short label',
                        ],
                        'Even a little Donation makes a big Difference' => [
                            'title' => 'Donation amount',
                            'image' => 'Donation image',
                        ],
                        'Where will your donation go' => [
                            'title' => 'Chart title',
                            'description' => 'Allocation text',
                            'allocation_direct' => 'Direct programme %',
                            'allocation_fundraising' => 'Fundraising %',
                        ],
                        'Watch Our Story' => [
                            'description' => 'Video URL',
                            'youtube_url' => 'Video URL',
                        ],
                        'Thanks to our supporters' => [
                            'title' => 'Supporters heading',
                            'description' => 'Supporters copy',
                        ],
                        "FAQs about donating to National Children's Commission" => [
                            'title' => 'Question',
                            'description' => 'Answer',
                        ],
                    ],
                ],
                'cta' => [
                    'label' => 'Call to action',
                    'blocks' => ['identity', 'body', 'button', 'sort', 'json', 'publish'],
                ],
            ],
        ],

        'advertise' => [
            'label' => 'Advertise',
            'description' => 'Advertising packages, calls to action, and partner-facing copy.',
            'route' => 'advertise',
            'icon' => 'fa-bullhorn',
            'types' => ['hero', 'card', 'stat', 'cta', 'submission'],
            'type_options' => [
                'hero' => [
                    'label' => 'Page header',
                    'blocks' => ['identity', 'subtitle', 'body', 'image', 'button', 'sort', 'json', 'publish'],
                ],
                'card' => [
                    'label' => 'Card',
                    'blocks' => ['identity', 'category', 'subtitle', 'body', 'image', 'button', 'sort', 'json', 'publish'],
                    'category_options' => 'cms.advertise_categories',
                    'category_blocks' => [
                        'Opportunities' => ['identity', 'category', 'subtitle', 'image', 'tier', 'button', 'sort', 'json', 'publish'],
                        'Sponsorship Opportunities' => ['identity', 'category', 'subtitle', 'image', 'tier', 'button', 'sort', 'json', 'publish'],
                        'Sponsored Ads' => ['identity', 'category', 'body', 'image', 'ad_expiry', 'button', 'sort', 'json', 'publish'],
                        'Partnership' => ['identity', 'category', 'subtitle', 'body', 'button', 'sort', 'json', 'publish'],
                        'Guidelines' => ['identity', 'category', 'subtitle', 'body', 'button', 'sort', 'json', 'publish'],
                        'Why Partner With NCC' => ['identity', 'category', 'subtitle', 'body', 'button', 'sort', 'json', 'publish'],
                    ],
                    'category_labels' => [
                        'Opportunities' => [
                            'subtitle' => 'Package name',
                            'description' => 'Package summary',
                        ],
                        'Sponsorship Opportunities' => [
                            'subtitle' => 'Tier name',
                            'description' => 'Tier summary',
                            'price' => 'Price',
                            'badge' => 'Badge',
                            'reach' => 'Reach',
                            'tier_placement' => 'Ad placements',
                            'tier_include' => 'What is included',
                        ],
                        'Sponsored Ads' => [
                            'title' => 'Advertiser / campaign',
                            'description' => 'Ad copy',
                            'image' => 'Ad creative',
                            'ends_at' => 'Campaign ends',
                            'hero_featured' => 'Feature in the advertise hero',
                        ],
                    ],
                ],
                'stat' => [
                    'label' => 'Impact statistic',
                    'blocks' => ['identity', 'body', 'stat_icon', 'sort', 'json', 'publish'],
                    'labels' => [
                        'title' => 'Number',
                        'description' => 'Label',
                        'stat_icon' => 'Icon',
                    ],
                ],
                'cta' => [
                    'label' => 'Call to action',
                    'blocks' => ['identity', 'body', 'button', 'sort', 'json', 'publish'],
                ],
                'submission' => [
                    'label' => 'Ad enquiry',
                    'blocks' => ['identity', 'body', 'submission_meta', 'sort', 'json'],
                    'labels' => [
                        'title' => 'Reference / subject',
                        'description' => 'Inquiry details',
                    ],
                    'can_publish' => false,
                ],
            ],
        ],
    ],
];
