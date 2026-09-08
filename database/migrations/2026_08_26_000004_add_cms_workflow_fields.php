<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 30)->default('editor')->after('is_admin');
                $table->index('role');
            }
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('alt_text')->nullable();
            $table->timestamps();

            $table->index(['disk', 'path']);
        });

        Schema::table('site_contents', function (Blueprint $table) {
            if (! Schema::hasColumn('site_contents', 'media_asset_id')) {
                $table->foreignId('media_asset_id')->nullable()->after('file_path')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('site_contents', 'alt_text')) {
                $table->string('alt_text')->nullable()->after('image_path');
            }

            if (! Schema::hasColumn('site_contents', 'data')) {
                $table->json('data')->nullable()->after('description');
            }

            if (! Schema::hasColumn('site_contents', 'preview_token')) {
                $table->string('preview_token', 64)->nullable()->after('published_on');
                $table->index('preview_token');
            }
        });

        Schema::create('content_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_content_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50);
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index(['site_content_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_audits');

        Schema::table('site_contents', function (Blueprint $table) {
            if (Schema::hasColumn('site_contents', 'media_asset_id')) {
                $table->dropConstrainedForeignId('media_asset_id');
            }

            foreach (['alt_text', 'data', 'preview_token'] as $column) {
                if (Schema::hasColumn('site_contents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('media_assets');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropIndex(['role']);
                $table->dropColumn('role');
            }
        });
    }
};
