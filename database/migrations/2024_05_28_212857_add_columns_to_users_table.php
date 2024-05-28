<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add roles column with foreign key reference
            $table->unsignedBigInteger('role_id')->nullable()->after('remember_token');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            // Add country and city columns
            $table->string('country')->nullable()->after('role_id');
            $table->string('city')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop columns if exists
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'country', 'city']);
        });
    }
}
