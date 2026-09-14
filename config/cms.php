<?php

return [
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
        'Sponsored Ads' => 'Sponsored Ads',
        'Partnership' => 'Partnership',
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
            'description' => 'Homepage hero, top stories, news highlights, and impact blocks.',
            'route' => 'home',
            'types' => ['hero', 'update', 'news', 'stat', 'cta'],
        ],
        'about' => [
            'label' => 'About',
            'description' => 'Mandate, mission, vision, commissioners, leadership, and organization content.',
            'route' => 'about',
            'types' => ['hero', 'card', 'stat', 'cta'],
        ],
        'what-we-do' => [
            'label' => 'What We Do',
            'description' => 'Programs, service areas, case work, and institutional focus areas.',
            'route' => 'what-we-do',
            'types' => ['hero', 'card', 'stat', 'cta'],
        ],
        'childrens-corner' => [
            'label' => "Children's Corner",
            'description' => 'Child-friendly activities, rights education, stories, drawings, and gallery content.',
            'route' => 'childrens-corner',
            'types' => ['hero', 'card', 'gallery', 'news', 'cta'],
        ],
        'reporting' => [
            'label' => 'Reporting',
            'description' => 'Reporting instructions, support copy, forms guidance, and response information.',
            'route' => 'reporting',
            'types' => ['hero', 'card', 'stat', 'cta', 'submission'],
        ],
        'resources' => [
            'label' => 'Resources',
            'description' => 'Publications, downloads, guidelines, reports, and learning resources.',
            'route' => 'resources',
            'types' => ['hero', 'resource', 'card'],
        ],
        'news' => [
            'label' => 'News',
            'description' => 'News articles, related news, updates, and announcements.',
            'route' => 'news',
            'types' => ['hero', 'news', 'card'],
        ],
        'donate' => [
            'label' => 'Donate',
            'description' => 'Donation page copy, calls to action, giving options, and trust messaging.',
            'route' => 'donate',
            'types' => ['hero', 'card', 'stat', 'cta'],
        ],
        'advertise' => [
            'label' => 'Advertise',
            'description' => 'Advertising packages, calls to action, and partner-facing copy.',
            'route' => 'advertise',
            'types' => ['hero', 'card', 'cta', 'submission'],
        ],
    ],
];
