<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->after('instagram', function (Blueprint $table) {
                $table->string('business_name')->nullable();
                $table->string('poc')->nullable();
                $table->string('poc_cell')->nullable();
            });
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
            $table->dropColumn('business_name');
            $table->dropColumn('poc');
            $table->dropColumn('poc_cell');
        });
    }
}
