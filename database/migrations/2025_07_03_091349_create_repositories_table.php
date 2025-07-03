<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path')->unique(); // The absolute path on the local machine
            $table->string('default_branch');
            $table->string('last_reviewed_commit_sha')->nullable();
            $table->string('target_commit_reference')->default('latest'); // Can be a SHA, tag, or 'latest'
            $table->unsignedInteger('ideal_review_pace')->default(20); // Renamed for clarity
            $table->string('actual_pace_period')->default('30-days');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};