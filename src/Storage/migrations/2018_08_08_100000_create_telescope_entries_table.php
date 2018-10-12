<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTelescopeEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telescope_entries', function (Blueprint $table) {
            $table->bigIncrements('sequence');
            $table->uuid('uuid')->unique();
            $table->uuid('batch_id')->index();
            $table->string('family_hash')->nullable()->index();
            $table->boolean('should_display_on_index')->index()->default(true);
            $table->string('type', 20)->unique()->index();
            $table->json('content');
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('telescope_entries_tags', function (Blueprint $table) {
            $table->uuid('entry_uuid')->index();
            $table->string('tag')->index();

            $table->foreign('entry_uuid')
                  ->references('uuid')
                  ->on('telescope_entries')
                  ->onDelete('cascade');
        });

        Schema::create('telescope_monitoring', function (Blueprint $table) {
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
        Schema::dropIfExists('telescope_entries_tags');
        Schema::dropIfExists('telescope_entries');
        Schema::dropIfExists('telescope_monitoring');
    }
}
