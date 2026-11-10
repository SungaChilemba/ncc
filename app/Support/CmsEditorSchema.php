<?php

namespace App\Support;

/**
 * Resolves the admin editor schema for a CMS section, type, and category.
 *
 * The schema is intentionally read-only: it only reads config/cms.php and
 * never persists anything. This keeps the rules testable and lets the editor
 * and controller share one source of truth.
 */
final class CmsEditorSchema
{
    /**
     * The complete editor schema for a section, keyed by type.
     *
     * @return array{
     *     section: string,
     *     label: string,
     *     description: string,
     *     route: string|null,
     *     types: array<string, array<string, mixed>>
     * }
     */
    public static function forSection(string $section): array
    {
        $sectionConfig = config("cms.sections.{$section}");

        if (! is_array($sectionConfig)) {
            return [];
        }

        $types = [];

        foreach ($sectionConfig['types'] ?? [] as $type) {
            $types[$type] = self::for($section, $type);
        }

        return [
            'section' => $section,
            'label' => $sectionConfig['label'] ?? self::headline($section),
            'description' => $sectionConfig['description'] ?? '',
            'route' => $sectionConfig['route'] ?? null,
            'types' => $types,
        ];
    }

    /**
     * Resolve one section + type + category combination.
     *
     * Unknown types fall back to the section's first allowed type. Unknown
     * sections or empty type lists return an empty array.
     *
     * @return array<string, mixed>
     */
    public static function for(string $section, string $type, ?string $category = null): array
    {
        $sectionConfig = config("cms.sections.{$section}");

        if (! is_array($sectionConfig)) {
            return [];
        }

        $type = self::resolveType($sectionConfig, $type);

        if ($type === null) {
            return [];
        }

        $typeConfig = $sectionConfig['type_options'][$type] ?? [];
        $categoryConfig = self::categoryConfig($sectionConfig, $typeConfig, $category);
        $blocks = self::resolveBlocks($sectionConfig, $typeConfig, $category);
        $fields = self::expandBlocks($blocks);

        $fieldMeta = [];

        foreach ($fields as $field) {
            $fieldMeta[$field] = self::resolveFieldMeta($field, $sectionConfig, $typeConfig, $categoryConfig);
        }

        $categoryOptions = self::categoryOptions($section, $type);
        $categoryAllowCustom = (bool) ($typeConfig['category_allow_custom'] ?? false);

        if ($categoryOptions !== null && isset($fieldMeta['category'])) {
            $fieldMeta['category']['options'] = $categoryOptions;
            $fieldMeta['category']['control'] = $categoryAllowCustom ? 'datalist' : 'select';
        }

        $locationOptions = self::resolveOptions($typeConfig['location_options'] ?? null);
        $locationAllowCustom = (bool) ($typeConfig['location_allow_custom'] ?? false);

        if ($locationOptions !== null && isset($fieldMeta['location'])) {
            $fieldMeta['location']['options'] = $locationOptions;
            $fieldMeta['location']['control'] = $locationAllowCustom ? 'datalist' : 'select';
        }

        return [
            'section' => $section,
            'type' => $type,
            'type_label' => $typeConfig['label'] ?? config("cms.types.{$type}", self::headline($type)),
            'summary' => config("cms.type_summaries.{$section}.{$type}"),
            'category' => $category,
            'blocks' => $blocks,
            'fields' => $fields,
            'field_meta' => $fieldMeta,
            'labels' => array_column($fieldMeta, 'label', 'key'),
            'category_options' => $categoryOptions,
            'category_allow_custom' => $categoryAllowCustom,
            'category_blocks' => $typeConfig['category_blocks'] ?? [],
            'category_labels' => $typeConfig['category_labels'] ?? [],
            'required' => array_values($typeConfig['required'] ?? []),
            'can_publish' => (bool) ($typeConfig['can_publish'] ?? $type !== 'submission'),
        ];
    }

    /**
     * Flat list of field keys for a section/type/category.
     *
     * @return list<string>
     */
    public static function fieldsFor(string $section, string $type, ?string $category = null): array
    {
        return self::for($section, $type, $category)['fields'] ?? [];
    }

    /**
     * Default metadata for a field, with any string option path resolved.
     *
     * @return array<string, mixed>
     */
    public static function fieldMeta(string $field): array
    {
        $meta = config("cms.field_meta.{$field}", []);

        if (! is_array($meta)) {
            return [];
        }

        if (isset($meta['options'])) {
            $meta['options'] = self::resolveOptions($meta['options']) ?? [];
        }

        return $meta;
    }

    /**
     * Category options for a section + type, or null when free text is used.
     *
     * @return array<string, string>|null
     */
    public static function categoryOptions(string $section, string $type): ?array
    {
        $typeConfig = config("cms.sections.{$section}.type_options.{$type}", []);

        if (! is_array($typeConfig)) {
            return null;
        }

        return self::resolveOptions($typeConfig['category_options'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $sectionConfig
     */
    private static function resolveType(array $sectionConfig, string $type): ?string
    {
        $types = $sectionConfig['types'] ?? [];

        if (in_array($type, $types, true)) {
            return $type;
        }

        return $types[0] ?? null;
    }

    /**
     * @param  array<string, mixed>  $sectionConfig
     * @param  array<string, mixed>  $typeConfig
     * @return list<string>
     */
    private static function resolveBlocks(array $sectionConfig, array $typeConfig, ?string $category): array
    {
        $blocks = $sectionConfig['blocks'] ?? [];

        if (! empty($typeConfig['blocks'])) {
            $blocks = $typeConfig['blocks'];
        }

        if ($category !== null && ! empty($typeConfig['category_blocks'])) {
            $key = self::matchCategoryKey($typeConfig['category_blocks'], $category);

            if ($key !== null) {
                $blocks = $typeConfig['category_blocks'][$key];
            }
        }

        return array_values(array_unique($blocks));
    }

    /**
     * @param  array<string, mixed>  $typeConfig
     * @param  array<string, mixed>  $categoryConfig
     * @return array<string, mixed>
     */
    private static function resolveFieldMeta(
        string $field,
        array $sectionConfig,
        array $typeConfig,
        array $categoryConfig
    ): array {
        $meta = self::fieldMeta($field);
        $meta['key'] = $field;

        foreach ([
            [$sectionConfig, 'field_meta'],
            [$typeConfig, 'field_meta'],
            [$categoryConfig, 'field_meta'],
        ] as [$source, $key]) {
            if (! isset($source[$key][$field]) || ! is_array($source[$key][$field])) {
                continue;
            }

            $meta = array_replace($meta, $source[$key][$field]);
        }

        foreach ([
            [$sectionConfig, 'labels', 'label'],
            [$sectionConfig, 'help', 'help'],
            [$typeConfig, 'labels', 'label'],
            [$typeConfig, 'help', 'help'],
            [$categoryConfig, 'labels', 'label'],
            [$categoryConfig, 'help', 'help'],
        ] as [$source, $key, $attribute]) {
            if (isset($source[$key][$field])) {
                $meta[$attribute] = $source[$key][$field];
            }
        }

        if (isset($meta['options'])) {
            $meta['options'] = self::resolveOptions($meta['options']) ?? [];
        }

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $sectionConfig
     * @param  array<string, mixed>  $typeConfig
     * @return array<string, mixed>
     */
    private static function categoryConfig(array $sectionConfig, array $typeConfig, ?string $category): array
    {
        if ($category === null) {
            return [];
        }

        $config = [];

        if (! empty($typeConfig['category_blocks'])) {
            $key = self::matchCategoryKey($typeConfig['category_blocks'], $category);

            if ($key !== null) {
                $config['blocks'] = $typeConfig['category_blocks'][$key];
            }
        }

        if (! empty($typeConfig['category_labels'])) {
            $key = self::matchCategoryKey($typeConfig['category_labels'], $category);

            if ($key !== null) {
                $config['labels'] = $typeConfig['category_labels'][$key];
            }
        }

        if (! empty($typeConfig['category_help'])) {
            $key = self::matchCategoryKey($typeConfig['category_help'], $category);

            if ($key !== null) {
                $config['help'] = $typeConfig['category_help'][$key];
            }
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private static function matchCategoryKey(array $map, string $category): ?string
    {
        if (array_key_exists($category, $map)) {
            return $category;
        }

        $normalised = mb_strtolower(trim($category));

        foreach (array_keys($map) as $key) {
            if (mb_strtolower(trim((string) $key)) === $normalised) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $blocks
     * @return list<string>
     */
    private static function expandBlocks(array $blocks): array
    {
        $catalogue = config('cms.blocks', []);
        $fields = [];

        foreach ($blocks as $block) {
            foreach ($catalogue[$block] ?? [] as $field) {
                if (! in_array($field, $fields, true)) {
                    $fields[] = $field;
                }
            }
        }

        return $fields;
    }

    /**
     * @return array<string, string>|null
     */
    private static function resolveOptions(mixed $options): ?array
    {
        if (is_string($options)) {
            $options = config($options);
        }

        if (! is_array($options)) {
            return null;
        }

        return $options;
    }

    private static function headline(string $value): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $value));
    }
}
