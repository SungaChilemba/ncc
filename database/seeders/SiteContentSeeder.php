<?php

namespace Database\Seeders;

use App\Models\SiteContent;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rows() as $row) {
            $attributes = [
                ...$row,
                'is_published' => $row['is_published'] ?? true,
                'published_at' => ($row['is_published'] ?? true) ? now() : null,
            ];

            $content = SiteContent::where('section', $row['section'])
                ->where('type', $row['type'])
                ->where('slug', $row['slug'])
                ->first();

            $content ??= SiteContent::where('section', $row['section'])
                ->where('type', $row['type'])
                ->where('title', $row['title'] ?? null)
                ->first();

            $content ? $content->update($attributes) : SiteContent::create($attributes);
        }
    }

    private function rows(): array
    {
        return [
            [
                'section' => 'home',
                'type' => 'hero',
                'slug' => 'our-children-our-responsibility',
                'title' => 'Our Children, Our Responsibility',
                'subtitle' => 'Safeguarding children’s rights and dignity.',
                'description' => null,
                'image_path' => 'images/NCChero.jpg',
                'accent_color' => 'green',
                'button_text' => 'Report a Case Now',
                'button_url' => '/reporting',
                'sort_order' => 0,
            ],
            [
                'section' => 'home',
                'type' => 'update',
                'slug' => 'take-on-typhoid',
                'title' => 'TAKE ON TYPHOID',
                'description' => 'Typhoid vaccine being administered to children below 15',
                'image_path' => 'images/vaccine.jpg',
                'accent_color' => 'red',
                'button_text' => 'Read More',
                'button_url' => '#',
                'sort_order' => 0,
            ],
            [
                'section' => 'home',
                'type' => 'update',
                'slug' => 'children-in-tech',
                'title' => 'CHILDREN IN TECH',
                'description' => 'Children in Tech highlights the growing involvement of young people in the digital world, where technology is shaping education, creativity, and future opportunities. An initiative sponsored by UNICEF and the Malawi government.',
                'image_path' => 'images/Kids-Coding.jpg',
                'accent_color' => 'red',
                'button_text' => 'Read More',
                'button_url' => '#',
                'sort_order' => 1,
            ],
            [
                'section' => 'home',
                'type' => 'news',
                'slug' => 'flea-market-child-protection',
                'title' => 'On Friday, 5 June - NCC engaged the Leaders of the flea Market in Lilongwe in efforts to promote child protection.',
                'image_path' => 'images/nccNews1.jpg',
                'accent_color' => 'red',
                'published_on' => '2026-06-05',
                'sort_order' => 0,
            ],
            [
                'section' => 'home',
                'type' => 'news',
                'slug' => 'day-of-african-child-2025',
                'title' => 'May 2025 - National Day of the African Child - Celebrations & Pledges',
                'image_path' => 'images/update-2.jpg',
                'accent_color' => 'red',
                'published_on' => '2025-05-01',
                'sort_order' => 1,
            ],
            [
                'section' => 'news',
                'type' => 'hero',
                'slug' => 'protect-families-and-children',
                'title' => 'Protect Families & Children',
                'category' => 'News',
                'published_on' => '2026-06-02',
                'description' => "The National Children's Commission joined the Ministry of Gender, Children, Disability and Social Welfare, development partners, traditional leaders, and community members in commemorating the International Day of Families and the International Day of Street-Connected Children at Lunzu Primary School Ground in Blantyre.\n\nThe commemoration was presided over by the Honourable Minister of Gender, Children, Disability and Social Welfare, Honourable Mary Thom Navicha, MP, and attended by traditional leaders led by Senior Chief Kapeni. The Commission was represented by Commissioner Julia Chimuna and members of the Secretariat. Held under the theme \"Families, Inequalities and Child Well being\", the event provided an opportunity for stakeholders to reflect on the importance of strong families in promoting child well being and addressing challenges affecting street-connected children.",
                'image_path' => 'images/newsbanner.png',
                'accent_color' => 'red',
                'sort_order' => 0,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'commission-monitoring-visits-blantyre-zomba',
                'title' => 'NCC Monitors Child Protection Facilities in Blantyre and Zomba',
                'description' => "From 11 to 12 August 2026, the National Children’s Commission conducted monitoring visits in Blantyre and Zomba, led by Commissioner Lyka Milanzi and Commissioner Dr Lucy Kachapila.\n\nIn Blantyre, the team visited Mpemba Reformatory Centre to assess progress since the 2024 assessment. The Commission noted improvements including cleaner water access through a solar-powered water system, acquisition of a bus, improved food security, and establishment of a vegetable garden.\n\nThe monitoring team also recorded remaining gaps, including staffing shortages, inadequate bedding and clothing, infrastructure needs, and limited recreational and educational resources.\n\nIn Zomba, the team visited Chirwa Rehabilitation Facility to assess legal compliance, standards of care, staffing, infrastructure, education, health care, psychosocial support, and reintegration services. The team engaged children and facility management to better understand experiences and challenges affecting service delivery.\n\nThe Commission said the visits helped assess progress, follow up on earlier recommendations, and identify areas requiring urgent attention to strengthen protection, care, and wellbeing for children.",
                'image_path' => 'images/facebook-busy-week-2026.jpg',
                'alt_text' => 'NCC monitoring visit activity captured in the August 2026 Facebook post',
                'category' => 'Monitoring',
                'location' => 'Blantyre and Zomba',
                'published_on' => '2026-08-18',
                'data' => [
                    'source' => [
                        'name' => 'National Children’s Commission of Malawi on Facebook',
                        'url' => 'https://www.facebook.com/permalink.php?story_fbid=pfbid06wqdCPUB2NiXhvG9z7RdnemKHs19jmWZXzxJsnUonVhcKYVMMhfhTw8MxG2A71nJl&id=61578135073164',
                        'type' => 'original',
                        'posted_on' => '2026-08-18',
                        'visible_excerpt' => 'It has been a very busy week for the Commission!',
                    ],
                    'media' => [
                        [
                            'path' => 'images/facebook-busy-week-2026.jpg',
                            'caption' => 'Image captured from the original NCC Facebook post for local CMS use.',
                        ],
                    ],
                ],
                'sort_order' => 1,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'stronger-action-against-child-labour',
                'title' => 'NCC Calls For Stronger Action Against Child Labour.',
                'description' => 'Leaders need to put more effort in nabbing perpetrators of child labor.',
                'image_path' => 'images/news1.png',
                'category' => 'Child Protection',
                'location' => 'Lilongwe',
                'published_on' => '2026-08-06',
                'sort_order' => 2,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'new-child-protection-guidelines-launched',
                'title' => 'New Child Protection Guidelines Launched',
                'description' => 'NCC introduces new child protection guidelines, as one of the ways to help.',
                'image_path' => 'images/news2.png',
                'category' => 'Policy',
                'location' => 'Gaza',
                'published_on' => '2026-08-04',
                'sort_order' => 3,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'youth-leaders-child-participation',
                'title' => 'NCC Meets Youth Leaders to Promote Child Participation',
                'description' => 'Thousands of migrants have crossed into Ceuta, many of them children unaccompanied or separated from families.',
                'image_path' => 'images/news3.png',
                'category' => 'Community Events',
                'location' => 'Global',
                'published_on' => '2026-07-31',
                'sort_order' => 4,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'chief-malemia-sentenced',
                'title' => 'Chief Malemia sentenced to 21 years imprisonment for child defilement.',
                'description' => "The National Children's Commission (NCC) welcomes the sentencing of Traditional Authority Malemia to 21 years' imprisonment.\n\nThe case had been under NCC monitoring earlier in 2026. In an update posted from Zomba, the Commission said it had spent three days observing proceedings at the Zomba Magistrates Court, where Traditional Authority Malemia was answering charges related to sexual intercourse with a child under Section 138 of the Penal Code of Malawi and procuring an abortion of a child under Section 149.\n\nAfter the prosecution presented evidence, the court ruled that the accused had a case to answer on both charges. NCC said the ruling allowed the matter to proceed to the defence stage and reaffirmed that the Commission would continue monitoring the case and keeping the public informed as proceedings progressed.",
                'image_path' => 'images/facebook-chief-malemia-case-2026.jpg',
                'alt_text' => 'NCC Facebook image accompanying the Chief Malemia court monitoring update',
                'category' => 'Child Protection',
                'location' => 'Zomba',
                'published_on' => '2026-07-24',
                'data' => [
                    'source' => [
                        'name' => 'National Children’s Commission of Malawi on Facebook',
                        'url' => 'https://www.facebook.com/people/National-Childrens-Commission-of-Malawi/61578135073164/',
                        'type' => 'original',
                        'posted_on' => '2026-04-07',
                        'visible_excerpt' => 'The National Children’s Commission (NCC) has, over the past three days, been in Zomba observing court proceedings...',
                    ],
                    'merge_note' => 'Merged with existing Chief Malemia sentencing article to preserve a single case chronology.',
                ],
                'sort_order' => 5,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'save-the-children-collaboration',
                'title' => 'NCC and Save the Children Strengthen Collaboration',
                'description' => "Today, Save the Children was honored to host the National Children's Commission of Malawi (NCC), led by Vice Chairperson, Commissioner Benedicto Kondowe.",
                'image_path' => 'images/image3.jpg',
                'category' => 'Partnerships',
                'location' => 'Global',
                'published_on' => '2026-07-25',
                'sort_order' => 6,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'ndondomeko-ufulu-wa-ana',
                'title' => 'Bungwe la NCC lakhazikitsa ndondomeko zopititsa ufulu wa ana patsogolo.',
                'description' => 'National Children\'s Commission lati liyika ndondomeko zabwino zofuna kuonetsetsa kuti ufulu wa ana ukupita patsogolo mdziko muno.',
                'image_path' => 'images/image4.jpg',
                'category' => 'Policy',
                'location' => 'Global',
                'published_on' => '2026-03-05',
                'sort_order' => 7,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'dedza-border-post-visit',
                'title' => 'NCC Commissioners Visit Dedza Border Post to Strengthen Child Protection',
                'description' => 'Visited the Dedza Border Post to appreciate the child protection services being offered at the facility.',
                'image_path' => 'images/image1.jpg',
                'category' => 'Child Protection',
                'location' => 'Lilongwe',
                'published_on' => '2026-05-12',
                'sort_order' => 8,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'ncc-mourns-thandi-mbogha',
                'title' => 'NCC Mourns Child Rights Advocate Thandi Mbogha',
                'description' => "The National Children’s Commission joined national mourning for Thandi Mbogha, describing her as a passionate young leader who stood boldly for the rights and wellbeing of children.\n\nThe Commission extended condolences to her family, friends, and all those affected by the loss. The post was published with NGO Coalition on Child Rights tagged as a partner page.",
                'image_path' => 'images/facebook-thandi-mbogha-2026.jpg',
                'alt_text' => 'NCC Facebook image accompanying the Thandi Mbogha remembrance post',
                'category' => 'Child Participation',
                'location' => 'Malawi',
                'published_on' => '2026-04-13',
                'data' => [
                    'source' => [
                        'name' => 'National Children’s Commission of Malawi on Facebook',
                        'url' => 'https://www.facebook.com/permalink.php?story_fbid=pfbid0aqEu6fTJLbHkh2Gyi8fHrMtKAh4ZNVD5QyFDhSzspw1BVEyCki9sDnS5tFf1i6zwl&id=61578135073164',
                        'type' => 'original',
                        'posted_on' => '2026-04-13',
                        'visible_excerpt' => 'We join the nation in mourning the loss of Thandi Mbogha...',
                    ],
                    'partners' => [
                        'NGO Coalition on Child Rights',
                    ],
                ],
                'sort_order' => 9,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'child-friendly-budget-analysis-2026-27',
                'title' => 'NCC Attends 2026/27 Child Friendly Budget Analysis Presentation',
                'description' => "The National Children’s Commission attended the presentation of the 2026/27 Child Friendly Budget Analysis by the NGO Coalition on Child Rights in Lilongwe.\n\nThe engagement focused on child-responsive public financing and the importance of ensuring that national budget decisions protect children’s rights, wellbeing, and access to essential services.",
                'image_path' => 'images/facebook-child-budget-analysis-2026.jpg',
                'alt_text' => 'NCC Facebook image accompanying the 2026/27 Child Friendly Budget Analysis post',
                'category' => 'Policy',
                'location' => 'Lilongwe',
                'published_on' => '2026-03-09',
                'data' => [
                    'source' => [
                        'name' => 'National Children’s Commission of Malawi on Facebook',
                        'url' => 'https://www.facebook.com/people/National-Childrens-Commission-of-Malawi/61578135073164/',
                        'type' => 'original',
                        'posted_on' => '2026-03-09',
                        'visible_excerpt' => 'The National Children’s Commission (NCC) today attended the presentation of the 2026/27 Child Friendly Budget Analysis...',
                    ],
                    'partners' => [
                        'NGO Coalition on Child Rights',
                    ],
                ],
                'sort_order' => 10,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'chief-malemia-prison',
                'title' => 'CHIEF MALEMIA SENTENCED TO 21 YEARS IN PRISON',
                'description' => "The National Children's Commission (NCC) welcomes the sentencing of Traditional Authority Malemia to 21 years' imprisonment.",
                'image_path' => 'images/news1.png',
                'category' => 'Child Protection',
                'location' => 'Blantyre',
                'published_on' => '2026-08-01',
                'sort_order' => 11,
            ],
            [
                'section' => 'news',
                'type' => 'news',
                'slug' => 'save-the-children-hosts-ncc',
                'title' => "Save the Children was honored to host the National Children's Commission of Malawi (NCC)",
                'description' => 'He committed that Save the Children will continue to walk hand-in-hand with the NCC.',
                'image_path' => 'images/news2.png',
                'category' => 'Partnerships',
                'location' => 'Zomba',
                'published_on' => '2026-07-20',
                'sort_order' => 12,
            ],
            [
                'section' => 'resources',
                'type' => 'hero',
                'slug' => 'resources-header',
                'title' => 'Resources',
                'description' => 'Browse official documents and guidelines. Use the search and filters to quickly find what you need.',
                'accent_color' => 'green',
                'sort_order' => 0,
            ],
            [
                'section' => 'resources',
                'type' => 'resource',
                'slug' => 'ncc-act',
                'title' => 'NCC Act',
                'description' => 'Official Act establishing the National Children’s Commission.',
                'category' => 'Acts',
                'file_path' => 'resource/NCC-Act.pdf',
                'button_text' => 'Download',
                'button_url' => 'resource/NCC-Act.pdf',
                'accent_color' => 'green',
                'sort_order' => 1,
            ],
            [
                'section' => 'resources',
                'type' => 'resource',
                'slug' => 'ncc-national-guidelines',
                'title' => 'NCC National Guidelines',
                'description' => 'Guidelines for implementing child protection and participation programs.',
                'category' => 'Guidelines',
                'file_path' => 'resource/NCC NATIONAL GUIDELINES.pdf',
                'button_text' => 'Download',
                'button_url' => 'resource/NCC NATIONAL GUIDELINES.pdf',
                'accent_color' => 'green',
                'sort_order' => 2,
            ],
            [
                'section' => 'about',
                'type' => 'hero',
                'slug' => 'about-commission',
                'title' => 'About Us',
                'subtitle' => 'Championing the Rights of Children in Malawi',
                'description' => 'The Commission was established under the Child Care, Protection and Justice Act to serve as the independent authority for promoting and protecting children’s rights across all 28 districts. We collaborate with government ministries, civil society, and communities to ensure every child in Malawi grows up safe and able to reach their full potential.',
                'image_path' => 'images/about-hero.png',
                'accent_color' => 'green',
                'sort_order' => 0,
            ],
            ...$this->aboutStoryTabs(),
            ...$this->commissioners(),
            [
                'section' => 'about',
                'type' => 'card',
                'slug' => 'chief-executive-officer',
                'category' => 'Organogram',
                'title' => 'Chief Executive Officer',
                'description' => 'Executive leadership for the National Children’s Commission.',
                'sort_order' => 20,
            ],
            [
                'section' => 'about',
                'type' => 'card',
                'slug' => 'director-of-compliance',
                'category' => 'Organogram',
                'title' => 'Director of Compliance',
                'description' => 'Leads compliance oversight and institutional accountability work.',
                'sort_order' => 21,
            ],
            [
                'section' => 'about',
                'type' => 'card',
                'slug' => 'finance-manager',
                'category' => 'Organogram',
                'title' => 'Finance Manager',
                'description' => 'Oversees finance operations and responsible resource use.',
                'sort_order' => 22,
            ],
            [
                'section' => 'about',
                'type' => 'card',
                'slug' => 'human-resource-manager',
                'category' => 'Organogram',
                'title' => 'Human Resource Manager',
                'description' => 'Supports staff capacity, welfare, and institutional administration.',
                'sort_order' => 23,
            ],
            ...$this->impactStats('about', 30, 'green'),
            [
                'section' => 'what-we-do',
                'type' => 'hero',
                'slug' => 'what-we-do-hero',
                'title' => 'WHAT WE DO',
                'subtitle' => 'Every child deserves to feel safe, heard and protected!',
                'image_path' => 'images/what-we-do-banner.png',
                'accent_color' => 'green',
                'sort_order' => 0,
            ],
            [
                'section' => 'what-we-do',
                'type' => 'card',
                'slug' => 'work-with-partners',
                'category' => 'How We Work',
                'title' => 'We work together with our partners, children, and communities to transform lives',
                'description' => 'We go to remote corners, villages, and cities where it is really tough to be a child. We ask children, their families, and communities what children need to be healthy, safe, and learning. We listen to their experiences, insights, and ideas. Together, we work hand in hand to adapt and create new solutions for children facing crisis, now and in the future.',
                'button_text' => 'Donate Now To Support Our Work',
                'button_url' => '/donate',
                'sort_order' => 1,
            ],
            ...$this->programCards('what-we-do', 2),
            [
                'section' => 'what-we-do',
                'type' => 'card',
                'slug' => 'our-child-our-responsibility',
                'category' => 'Our Child, Our Responsibility',
                'title' => 'OUR CHILD, OUR RESPONSIBILITY',
                'description' => 'We go to remote corners, villages, and cities where it is really tough to be a child. We ask children, their families, and communities what children need to be healthy, safe, and learning. We listen to their experiences, insights, and ideas. Together, we work hand in hand to adapt and create new solutions for children facing crisis, now and in the future.',
                'sort_order' => 10,
            ],
            ...$this->impactStats('what-we-do', 11, 'red'),
            [
                'section' => 'childrens-corner',
                'type' => 'hero',
                'slug' => 'child-rights-corner',
                'title' => 'CHILD RIGHTS CORNER',
                'description' => 'We are dedicated to safeguarding the well-being of all children by promoting their rights, protecting their dignity and empowering them to reach their full potential.',
                'image_path' => 'images/child-hero.png',
                'accent_color' => 'green',
                'sort_order' => 0,
            ],
            ...$this->childrenActivities(),
            ...$this->childrenRights(),
            ...$this->childrenGallery(),
            ...$this->childrenNews(),
            [
                'section' => 'reporting',
                'type' => 'hero',
                'slug' => 'reporting-and-tracking',
                'title' => 'Reporting and Tracking',
                'subtitle' => 'File complaints, attach evidence, and track case progress.',
                'description' => 'Use the reporting process to submit child-rights concerns, share supporting evidence, and follow up with a reference number.',
                'accent_color' => 'green',
                'sort_order' => 0,
            ],
            ...$this->reportingBlocks(),
            [
                'section' => 'donate',
                'type' => 'hero',
                'slug' => 'make-a-child-smile',
                'title' => 'MAKE A CHILD SMILE',
                'subtitle' => 'Donate to help children today',
                'description' => 'Your support can give children access to education, healthcare, and nutritious food. Even small contributions make a big difference in shaping their future. Together, we can bring hope and opportunity to every child in need.',
                'image_path' => 'images/girl child.jpg',
                'button_text' => 'Donate',
                'button_url' => '#donation-box',
                'sort_order' => 0,
            ],
            ...$this->donationBlocks(),
            [
                'section' => 'advertise',
                'type' => 'hero',
                'slug' => 'advertise-with-ncc',
                'title' => 'Advertise',
                'subtitle' => 'Partner with the National Children’s Commission to reach families, stakeholders, and child protection audiences.',
                'description' => 'Use this page to manage advertising and partnership messaging from the CMS.',
                'image_path' => 'images/newsbanner.png',
                'sort_order' => 0,
            ],
            [
                'section' => 'advertise',
                'type' => 'card',
                'slug' => 'advertising-opportunities',
                'category' => 'Opportunities',
                'title' => 'Advertising Opportunities',
                'description' => 'Create campaign placements, sponsor child-rights content, or partner on awareness initiatives that support NCC’s public-interest mission.',
                'button_text' => 'Contact Us',
                'button_url' => 'mailto:nccmalawi@gmail.com',
                'sort_order' => 1,
            ],
        ];
    }

    /**
     * Mandate, Mission and Objectives tabs shown at the top of the About page.
     * Each tab renders its own background image and bullet list.
     */
    private function aboutStoryTabs(): array
    {
        $tabs = [
            'our-mandate' => [
                'title' => 'Our Mandate',
                'image_path' => 'images/about-mandate.jpg',
                'bullets' => [
                    'To hold duty bearers of child services accountable for the discharge of their duties.',
                    'To promote, coordinate and monitor the implementation of child rights and welfare programs.',
                    'To provide oversight, advise and coordination on all child rights and welfare services.',
                    'To provide an independent oversight, coordination, and policy guidance to ensure that all duty bearers in Malawi fulfil their obligations in protecting and promoting the rights and well-being of every child.',
                ],
            ],
            'our-mission' => [
                'title' => 'Our Mission',
                'image_path' => 'images/about-mission.jpg',
                'bullets' => [
                    'To promote and protect the rights and welfare of children in Malawi.',
                    'To safeguard the rights and welfare of children in Malawi.',
                    'To promote, protect and uphold children’s rights and welfare through coordination and monitoring of child service providers.',
                    'To make childcare and protection a national priority through coordinated efforts that ensure every child’s safety and well-being.',
                ],
            ],
            'our-objectives' => [
                'title' => 'Our Objectives',
                'image_path' => 'images/about-vision.jpg',
                'bullets' => [
                    'To advocate for child rights and welfare.',
                    'To advise Government and stakeholders on the development and review of legal and policy instruments for care and protection of children.',
                    'To monitor implementation, compliance and enforcement of policies, laws, regulations and standards on children’s rights and welfare.',
                    'To ensure multi-sectoral coordination and collaboration among stakeholders providing child-related services.',
                    'To coordinate research and documentation for the effective and efficient delivery of child rights and welfare services.',
                    'To promote resource mobilization and utilization for the delivery of child rights and welfare services.',
                    'To promote fulfillment of treaty obligations as they relate to children and monitor implementation of concluding observations.',
                ],
            ],
        ];

        $slugs = array_keys($tabs);

        return array_map(function ($slug, $index) use ($tabs) {
            $tab = $tabs[$slug];

            return [
                'section' => 'about',
                'type' => 'card',
                'slug' => $slug,
                'category' => 'Mandate, Mission & Objectives',
                'title' => $tab['title'],
                'description' => implode("\n", $tab['bullets']),
                'image_path' => $tab['image_path'],
                'data' => ['bullets' => $tab['bullets']],
                'sort_order' => $index + 1,
            ];
        }, $slugs, array_keys($slugs));
    }

    private function commissioners(): array
    {
        $professionalBackground = 'Has over two decades of experience in child protection policy and advocacy across Malawi. This leadership role supports initiatives to strengthen legislative frameworks for children’s rights and work with government ministries and community organizations. The work focuses on ensuring vulnerable children have access to safety, education, and justice.';
        $education = 'Holds qualifications in social work, child rights, public policy, or a related field, and has completed professional development in child protection, advocacy, governance, and community engagement.';

        $rows = [
            ['slug' => 'vincent-mwakawawa', 'title' => 'Mr Vincent Mwakawawa', 'subtitle' => 'Chairperson', 'image_path' => 'images/vincent-mwakawawa.jpg'],
            ['slug' => 'benedicto-khondowe', 'title' => 'Mr Benedicto Khondowe', 'subtitle' => 'Vice Chairperson', 'image_path' => 'images/benedicto-khondowe.jpg'],
            ['slug' => 'lucy-kapachira', 'title' => 'Dr Lucy Kapachira', 'subtitle' => 'Chair, Corporate Division', 'image_path' => 'images/lucy-kapachira.jpg'],
            ['slug' => 'laika-milanzi', 'title' => 'Mrs Laika Milanzi', 'subtitle' => 'Chair, Compliance Division', 'image_path' => 'images/laika-milanzi.jpg'],
            ['slug' => 'julia-chimuna', 'title' => 'Mrs Julia Chimuna', 'subtitle' => 'Chair, Documentation & Learning Division', 'image_path' => 'images/julia-chimuna.jpg'],
        ];

        return array_map(fn ($row, $index) => [
            ...$row,
            'section' => 'about',
            'type' => 'card',
            'category' => 'Commissioners',
            'description' => $professionalBackground,
            'data' => [
                'bio_sections' => [
                    [
                        'heading' => 'Professional Background',
                        'body' => $professionalBackground,
                    ],
                    [
                        'heading' => 'Education & Certifications',
                        'body' => $education,
                    ],
                ],
            ],
            'accent_color' => 'green',
            'sort_order' => 10 + $index,
        ], $rows, array_keys($rows));
    }

    private function impactStats(string $section, int $start, string $color): array
    {
        $stats = [
            ['slug' => 'children-supported', 'title' => '1,249+', 'description' => 'Children Supported'],
            ['slug' => 'families-reached', 'title' => '2,000+', 'description' => 'Families Reached'],
            ['slug' => 'community-members', 'title' => '6,000+', 'description' => 'Community Members'],
            ['slug' => 'districts-covered', 'title' => '28', 'description' => 'Districts Covered'],
        ];

        return array_map(fn ($row, $index) => [
            ...$row,
            'section' => $section,
            'type' => 'stat',
            'accent_color' => $color,
            'sort_order' => $start + $index,
        ], $stats, array_keys($stats));
    }

    private function programCards(string $section, int $start): array
    {
        $programs = [
            ['slug' => 'advocacy-policy', 'title' => 'ADVOCACY & POLICY', 'image_path' => 'images/vaccine.jpg'],
            ['slug' => 'awareness', 'title' => 'AWARENESS', 'image_path' => 'images/update-1.jpg'],
            ['slug' => 'protection', 'title' => 'PROTECTION', 'image_path' => 'images/NCCkids.jpg'],
            ['slug' => 'referral-support', 'title' => 'REFERRAL & SUPPORT', 'image_path' => 'images/covered.jpg'],
            ['slug' => 'capacity-building', 'title' => 'CAPACITY BUILDING', 'image_path' => 'images/image4.jpg'],
            ['slug' => 'research', 'title' => 'RESEARCH', 'image_path' => 'images/mission.jpg'],
        ];

        return array_map(fn ($row, $index) => [
            ...$row,
            'section' => $section,
            'type' => 'card',
            'category' => 'Our Programs',
            'description' => $this->programDescription($row['title']),
            'accent_color' => 'green',
            'sort_order' => $start + $index,
        ], $programs, array_keys($programs));
    }

    private function programDescription(string $title): string
    {
        return match ($title) {
            'ADVOCACY & POLICY' => 'Influencing laws and policies to strengthen child rights and working with government and stakeholders for systemic change.',
            'AWARENESS' => 'Raising awareness through campaigns, workshops, and community outreach so children and families understand their rights.',
            'PROTECTION' => 'Monitoring and preventing abuse, neglect, and exploitation of children while promoting child-safe environments.',
            'REFERRAL & SUPPORT' => 'Connecting children and families to support services, counseling, healthcare, and legal aid.',
            'CAPACITY BUILDING' => 'Training professionals, institutions, and communities to strengthen child protection skills and resources.',
            default => 'Conducting studies and gathering data to inform evidence-based child protection strategies.',
        };
    }

    private function childrenActivities(): array
    {
        $items = [
            ['slug' => 'quizzes', 'title' => 'Quizzes', 'subtitle' => 'fa-solid fa-question'],
            ['slug' => 'games', 'title' => 'Games', 'subtitle' => 'fa-solid fa-gamepad'],
            ['slug' => 'videos', 'title' => 'Videos', 'subtitle' => 'fa-solid fa-video'],
            ['slug' => 'stories', 'title' => 'Stories', 'subtitle' => 'fa-solid fa-book-open'],
            ['slug' => 'share-story', 'title' => 'Share Story', 'subtitle' => 'fa-solid fa-pencil'],
            ['slug' => 'share-drawing', 'title' => 'Share Drawing', 'subtitle' => 'fa-solid fa-paintbrush'],
            ['slug' => 'share-poem', 'title' => 'Share Poem', 'subtitle' => 'fa-solid fa-feather'],
        ];

        return array_map(fn ($row, $index) => [
            ...$row,
            'section' => 'childrens-corner',
            'type' => 'card',
            'category' => 'Activities',
            'sort_order' => 1 + $index,
        ], $items, array_keys($items));
    }

    private function childrenRights(): array
    {
        $items = [
            ['slug' => 'education-rights', 'title' => 'Education Rights', 'subtitle' => 'fa-solid fa-graduation-cap'],
            ['slug' => 'participation-rights', 'title' => 'Participation Rights', 'subtitle' => 'fa-solid fa-people-group'],
            ['slug' => 'protection-rights', 'title' => 'Protection Rights', 'subtitle' => 'fa-solid fa-shield'],
            ['slug' => 'healthy-rights', 'title' => 'Healthy Rights', 'subtitle' => 'fa-solid fa-heart'],
        ];

        return array_map(fn ($row, $index) => [
            ...$row,
            'section' => 'childrens-corner',
            'type' => 'card',
            'category' => 'Child Rights',
            'description' => 'Awareness of child rights encourages families, schools, and communities to create a safe and supportive environment where every child can grow, learn, and reach their full potential.',
            'sort_order' => 20 + $index,
        ], $items, array_keys($items));
    }

    private function childrenGallery(): array
    {
        return array_map(fn ($image, $index) => [
            'section' => 'childrens-corner',
            'type' => 'gallery',
            'slug' => 'learning-window-'.($index + 1),
            'title' => 'Learning Window '.($index + 1),
            'image_path' => 'images/'.$image,
            'sort_order' => 40 + $index,
        ], ['window1.png', 'window2.png', 'window3.png', 'window4.png', 'window5.png'], range(0, 4));
    }

    private function childrenNews(): array
    {
        $items = [
            ['slug' => 'career-kids-girl', 'title' => 'Career Kids', 'image_path' => 'images/career-girl.png', 'button_text' => 'Share Video'],
            ['slug' => 'spelling-game', 'title' => 'SPELLING GAME', 'image_path' => 'images/spelling.png', 'button_text' => 'Compete'],
            ['slug' => 'career-kids-boy', 'title' => 'Career Kids', 'image_path' => 'images/career-boy.png', 'button_text' => 'Share Video'],
        ];

        return array_map(fn ($row, $index) => [
            ...$row,
            'section' => 'childrens-corner',
            'type' => 'news',
            'category' => 'Kids News & Updates',
            'button_url' => '#',
            'sort_order' => 60 + $index,
        ], $items, array_keys($items));
    }

    private function reportingBlocks(): array
    {
        $items = [
            ['slug' => 'file-complaint', 'title' => 'File a Complaint', 'description' => 'Capture the child’s age, name, district, village or T/A, gender, nature of violation, date of incident, description, and optional reporter information. You will receive a reference number to attach evidence and track your case.'],
            ['slug' => 'attach-evidence', 'title' => 'Attach Evidence', 'description' => 'Use the case reference number to upload supporting files. Allowed evidence includes JPEG, PNG, MP3, MP4, and PDF files up to 20 MB. Avoid identifying details if it puts you or a child at risk.'],
            ['slug' => 'track-case', 'title' => 'Track Your Case', 'description' => 'Use a reference number such as NCC-2025-00847 to see the status, violation type, region, timeline, and estimated resolution period.'],
            ['slug' => 'case-statistics', 'title' => 'Case Statistics Jan-Dec 2024 - Fully anonymised', 'description' => 'Cases filed: 1,284. Resolution rate: 71%. Average response: 23 days. All data is fully anonymised and no individual or identifiable information is published.'],
        ];

        return array_map(fn ($row, $index) => [
            ...$row,
            'section' => 'reporting',
            'type' => 'card',
            'category' => 'Reporting Workflow',
            'sort_order' => 1 + $index,
        ], $items, array_keys($items));
    }

    private function donationBlocks(): array
    {
        $items = [
            ['slug' => 'monthly-and-one-off-giving', 'category' => 'Giving Options', 'title' => 'Monthly and One-Off Giving', 'description' => 'Choose monthly or one-off support, then select Mkw 2,000, Mkw 5,000, or Mkw 10,000. Payment options shown include Airtel Money, Visa, PayPal, and TNM Mpamba.', 'image_path' => 'images/boy childd.jpg', 'button_text' => 'Continue', 'button_url' => '/checkout'],
            ['slug' => 'blanket-support', 'category' => 'Even a little Donation makes a big Difference', 'title' => 'A Mkw 500 Donation', 'description' => 'Can provide a warm blanket to a child lacking in our rural settlements.', 'image_path' => 'images/covered.jpg'],
            ['slug' => 'school-feeding-support', 'category' => 'Even a little Donation makes a big Difference', 'title' => 'A Mkw 1,000 Donation', 'description' => 'Can help feed children in schools located in hunger stricken areas.', 'image_path' => 'images/food child.jpg'],
            ['slug' => 'healthcare-support', 'category' => 'Even a little Donation makes a big Difference', 'title' => 'A Mkw 2,000 Donation', 'description' => 'Can assist in funding child healthcare programs in Malawi.', 'image_path' => 'images/vaccine1.jpg'],
            ['slug' => 'child-protection-support', 'category' => 'Even a little Donation makes a big Difference', 'title' => 'A Mkw 700 Donation', 'description' => 'Can help in child protection initiatives across the country.', 'image_path' => 'images/NCCkids.jpg'],
            ['slug' => 'supporters-safety', 'category' => 'Thanks to our supporters', 'title' => '12,000 Children', 'description' => 'Children have been kept safe through our child protection programmes.'],
            ['slug' => 'supporters-education', 'category' => 'Thanks to our supporters', 'title' => '23,000 Children', 'description' => 'Children have been kept at school through our child education programmes.'],
            ['slug' => 'supporters-awareness', 'category' => 'Thanks to our supporters', 'title' => '5,000 Guardians', 'description' => 'Guardians have been sensitized through our child protection awareness programmes.'],
            ['slug' => 'where-donation-goes', 'category' => 'Where will your donation go', 'title' => 'Supporting Our Work', 'description' => '75% of every donation goes directly into charitable programmes. 25% is invested in fundraising.'],
            ['slug' => 'watch-our-story', 'category' => 'Watch Our Story', 'title' => 'Watch Our Story', 'description' => 'https://www.youtube.com/embed/WMNEL8-INig'],
            ['slug' => 'faq-how-spent', 'category' => 'FAQs about donating to National Children\'s Commission', 'title' => 'How are my donations spent?', 'description' => 'Your donations help children in vulnerable places survive, recover from trauma, and rebuild their future through healthcare, counselling, play therapy, and education.'],
            ['slug' => 'faq-monthly', 'category' => 'FAQs about donating to National Children\'s Commission', 'title' => 'Why should I make a monthly donation?', 'description' => 'Monthly donations provide consistent support, allowing us to plan long-term projects and ensure children receive ongoing care and education.'],
            ['slug' => 'faq-support-ncc', 'category' => 'FAQs about donating to National Children\'s Commission', 'title' => 'Why should I support National Children\'s Commission?', 'description' => 'Supporting NCC means investing in children’s rights, safety, and future. Your contributions directly impact programs that protect and empower children.'],
            ['slug' => 'faq-difference', 'category' => 'FAQs about donating to National Children\'s Commission', 'title' => 'How do I know my donations are making a difference?', 'description' => 'We provide regular updates, reports, and stories showing how your donations are changing lives and strengthening communities.'],
            ['slug' => 'faq-where-used', 'category' => 'FAQs about donating to National Children\'s Commission', 'title' => 'Where in the world will my donations be used?', 'description' => 'Donations are directed to areas where children are most at risk, ensuring resources reach those who need them most.'],
        ];

        return array_map(fn ($row, $index) => [
            ...$row,
            'section' => 'donate',
            'type' => 'card',
            'sort_order' => 1 + $index,
        ], $items, array_keys($items));
    }
}
