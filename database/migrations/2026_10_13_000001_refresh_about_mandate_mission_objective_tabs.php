<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Replace the old "Mandate, Mission & Vision" about tabs with the
     * client supplied "Mandate, Mission & Objectives" content.
     */
    public function up(): void
    {
        foreach ($this->tabs() as $slug => $tab) {
            $content = $this->findCard($slug, $tab['legacy_slugs']);

            if (! $content) {
                continue;
            }

            $content->update([
                'slug' => $slug,
                'title' => $tab['title'],
                'category' => $tab['category'],
                'description' => implode("\n", $tab['bullets']),
                'data' => array_merge($content->data ?? [], ['bullets' => $tab['bullets']]),
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->tabs() as $slug => $tab) {
            $content = SiteContent::forSection('about')->where('slug', $slug)->first();

            if (! $content) {
                continue;
            }

            $data = $content->data ?? [];
            unset($data['bullets']);

            $content->update([
                'slug' => $tab['legacy_slugs'][0],
                'title' => $tab['legacy_title'],
                'category' => 'Mandate, Mission & Vision',
                'description' => $tab['legacy_description'],
                'data' => $data ?: null,
            ]);
        }
    }

    private function findCard(string $slug, array $legacySlugs): ?SiteContent
    {
        return SiteContent::forSection('about')
            ->ofType('card')
            ->whereIn('slug', array_merge([$slug], $legacySlugs))
            ->orderByRaw('case when slug = ? then 0 else 1 end', [$slug])
            ->first();
    }

    private function tabs(): array
    {
        return [
            'our-mandate' => [
                'title' => 'Our Mandate',
                'category' => 'Mandate, Mission & Objectives',
                'legacy_slugs' => [],
                'legacy_title' => 'Our Mandate',
                'legacy_description' => 'Safeguarding children from abuse and exploitation. Monitoring child rights laws and conventions. Amplifying children’s voices in society.',
                'bullets' => [
                    'To hold duty bearers of child services accountable for the discharge of their duties.',
                    'To promote, coordinate and monitor the implementation of child rights and welfare programs.',
                    'To provide oversight, advise and coordination on all child rights and welfare services.',
                    'To provide an independent oversight, coordination, and policy guidance to ensure that all duty bearers in Malawi fulfil their obligations in protecting and promoting the rights and well-being of every child.',
                ],
            ],
            'our-mission' => [
                'title' => 'Our Mission',
                'category' => 'Mandate, Mission & Objectives',
                'legacy_slugs' => [],
                'legacy_title' => 'Our Mission',
                'legacy_description' => 'Coordinate, monitor, and strengthen accountability in all matters concerning children’s rights and welfare in Malawi.',
                'bullets' => [
                    'To promote and protect the rights and welfare of children in Malawi.',
                    'To safeguard the rights and welfare of children in Malawi.',
                    'To promote, protect and uphold children’s rights and welfare through coordination and monitoring of child service providers.',
                    'To make childcare and protection a national priority through coordinated efforts that ensure every child’s safety and well-being.',
                ],
            ],
            'our-objectives' => [
                'title' => 'Our Objectives',
                'category' => 'Mandate, Mission & Objectives',
                'legacy_slugs' => ['our-vision'],
                'legacy_title' => 'Our Vision',
                'legacy_description' => 'A Malawi where every child grows up safe, empowered, supported, and able to reach their full potential.',
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
    }
};