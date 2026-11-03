<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Remove the legacy reporting submissions that stored case data in the CMS
     * table and evidence on the public disk. This is test data; the new case
     * domain starts from an empty, private store.
     */
    public function up(): void
    {
        $submissions = DB::table('site_contents')
            ->where('section', 'reporting')
            ->where('type', 'submission')
            ->get();

        foreach ($submissions as $submission) {
            $data = json_decode((string) $submission->data, true) ?: [];

            foreach ((array) ($data['evidence'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (! empty($item['media_asset_id'])) {
                    DB::table('media_assets')->where('id', $item['media_asset_id'])->delete();
                }

                if (! empty($item['path'])) {
                    Storage::disk('public')->delete((string) $item['path']);
                }
            }
        }

        DB::table('site_contents')
            ->where('section', 'reporting')
            ->where('type', 'submission')
            ->delete();

        // Catch any orphaned evidence assets left behind by earlier uploads.
        $orphans = DB::table('media_assets')->where('path', 'like', 'evidence/%')->get();

        foreach ($orphans as $asset) {
            Storage::disk('public')->delete((string) $asset->path);
            DB::table('media_assets')->where('id', $asset->id)->delete();
        }

        Storage::disk('public')->deleteDirectory('evidence');
    }

    public function down(): void
    {
        // Removed test data is intentionally not restored.
    }
};
