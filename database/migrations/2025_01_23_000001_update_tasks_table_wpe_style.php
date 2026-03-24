<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Make group_id nullable (tasks can exist without a group)
            $table->uuid('group_id')->nullable()->change();

            // WPE-style columns
            $table->string('task_type_key')->nullable()->after('user_id');
            $table->timestamp('deadline_at')->nullable()->after('priority');
            $table->json('content')->nullable()->after('description');
            $table->json('meta')->nullable()->after('content');
            $table->uuid('created_by')->nullable()->after('reminder_sent');

            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // Add position column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable()->after('rank');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['task_type_key', 'deadline_at', 'content', 'meta', 'created_by']);
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
