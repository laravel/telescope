<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database schema.
     *
     * @var \Illuminate\Database\Schema\Builder
     */
    protected $schema;

    protected $telescope_entries;
    protected $telescope_entries_tags;
    protected $telescope_monitoring;

    /**
     * Create a new migration instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->schema = Schema::connection($this->getConnection());

        $this->telescope_entries      = config('telescope.table_name.telescope_entries');
        $this->telescope_entries_tags = config('telescope.table_name.telescope_entries_tags');
        $this->telescope_monitoring   = config('telescope.table_name.telescope_monitoring');
    }

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    public function getConnection()
    {
        return config('telescope.storage.database.connection');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create($this->telescope_entries, function (Blueprint $table) {
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

        $this->schema->create($this->telescope_entries_tags, function (Blueprint $table) {
            $table->uuid('entry_uuid');
            $table->string('tag');

            $table->index(['entry_uuid', 'tag']);
            $table->index('tag');

            $table->foreign('entry_uuid')
                  ->references('uuid')
                  ->on($this->telescope_entries)
                  ->onDelete('cascade');
        });

        $this->schema->create($this->telescope_monitoring, function (Blueprint $table) {
            $table->string('tag');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->dropIfExists($this->telescope_entries_tags);
        $this->schema->dropIfExists($this->telescope_entries);
        $this->schema->dropIfExists($this->telescope_monitoring);
    }
};
