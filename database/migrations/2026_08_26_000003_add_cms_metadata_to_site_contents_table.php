<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('type');
            $table->string('category')->nullable()->after('subtitle');
            $table->string('location')->nullable()->after('category');
            $table->date('published_on')->nullable()->after('published_at');
            $table->string('file_path')->nullable()->after('image_path');

            $table->index(['section', 'slug']);
            $table->index(['section', 'category']);
            $table->index(['published_on']);
        });
    }

    public function down(): void
    {
        Schema::table('site_contents', function (Blueprint $table) {
            $table->dropIndex(['section', 'slug']);
            $table->dropIndex(['section', 'category']);
            $table->dropIndex(['published_on']);
            $table->dropColumn(['slug', 'category', 'location', 'published_on', 'file_path']);
        });
    }
};
