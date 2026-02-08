<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return config('telescope.storage.database.connection');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());

        $schema->create('telescope_entries', function (Blueprint $table) {
            $table->bigIncrements('sequence');
            $table->uuid('uuid');
            $table->uuid('batch_id');
            $table->string('family_hash')->nullable();
            $table->boolean('should_display_on_index')->default(true);
            $table->string('type', 20);
            $table->longText('content');
            $table->dateTime('created_at')->nullable();

            $table->unique('uuid');
            $table->index('batch_id');
            $table->index('family_hash');
            $table->index('created_at');
            $table->index(['type', 'should_display_on_index']);
        });

        $schema->create('telescope_entries_tags', function (Blueprint $table) {
            $table->uuid('entry_uuid');
            $table->string('tag');

            $table->primary(['entry_uuid', 'tag']);
            $table->index('tag');

            $table->foreign('entry_uuid')
                ->references('uuid')
                ->on('telescope_entries')
                ->onDelete('cascade');
        });

        $schema->create('telescope_monitoring', function (Blueprint $table) {
            $table->string('tag')->primary();
        });

        $schema->create('telescope_security_whitelist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('path_pattern');
            $table->string('method')->nullable();
            $table->json('path_params_rules')->nullable();
            $table->json('query_rules')->nullable();
            $table->json('payload_rules')->nullable();
            $table->json('header_rules')->nullable();
            $table->boolean('is_regex')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('path_pattern');
            $table->index('enabled');
        });

        $schema->create('telescope_global_security_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('category'); // path_traversal, sql_injection, xss, command_injection, etc.
            $table->json('patterns'); // Array of patterns to check for
            $table->json('exclude_paths')->nullable(); // Paths to exclude from this rule
            $table->boolean('enabled')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection($this->getConnection());

        $schema->dropIfExists('telescope_entries_tags');
        $schema->dropIfExists('telescope_entries');
        $schema->dropIfExists('telescope_monitoring');
        $schema->dropIfExists('telescope_security_whitelist');
        $schema->dropIfExists('telescope_global_security_rules');
    }
};
