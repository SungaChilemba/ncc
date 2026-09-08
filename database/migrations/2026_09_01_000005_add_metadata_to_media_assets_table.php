<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            if (! Schema::hasColumn('media_assets', 'caption')) {
                $table->string('caption')->nullable()->after('alt_text');
            }

            if (! Schema::hasColumn('media_assets', 'description')) {
                $table->text('description')->nullable()->after('caption');
            }
        });
    }

    public function down(): void
    {
        Schema::table('media_assets', function (Blueprint $table) {
            foreach (['caption', 'description'] as $column) {
                if (Schema::hasColumn('media_assets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
