<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('tags')->nullOnDelete()->after('id');

            // Drop global unique constraint — slug uniqueness is now scoped per parent
            // and enforced at application level via SlugGenerator.
            $table->dropUnique('tags_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->unique('slug');
        });
    }
};
