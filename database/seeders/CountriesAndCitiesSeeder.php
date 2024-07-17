<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CountriesAndCitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $json = File::get(database_path('seeders/countries_and_cities.json'));
        $data = json_decode($json, true);

        foreach ($data as $country => $cities) {
            $countryId = DB::table('countries')->insertGetId([
                'name' => $country,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($cities as $city) {
                DB::table('cities')->insert([
                    'name' => $city,
                    'country_id' => $countryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
