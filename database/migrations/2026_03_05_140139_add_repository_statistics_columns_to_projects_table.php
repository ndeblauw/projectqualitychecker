<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('repository_statistics')->nullable()->after('github_url');
            $table->string('repository_statistics_latest_commit_identifier')->nullable()->after('repository_statistics');
            $table->timestamp('repository_statistics_refreshed_at')->nullable()->after('repository_statistics_latest_commit_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'repository_statistics',
                'repository_statistics_latest_commit_identifier',
                'repository_statistics_refreshed_at',
            ]);
        });
    }
};
