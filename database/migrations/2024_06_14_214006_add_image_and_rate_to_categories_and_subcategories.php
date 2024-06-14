<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageAndRateToCategoriesAndSubcategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
            $table->decimal('rate', 8, 2)->nullable()->after('image');
        });

        Schema::table('subcategories', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
            $table->decimal('rate', 8, 2)->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['image', 'rate']);
        });

        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropColumn(['image', 'rate']);
        });
    }
}
