<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Countries;
use App\Models\Cities;
use App\Traits\HandleResponse;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    use HandleResponse;

    public function createCountries(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:countries,name'
        ]);

        if ($validator->fails()) {
            return $this->fail(422, "Invalid credentials", $validator->errors()->first());
        }

        $country = Countries::create([
            'name' => $request->name
        ]);

        return $this->successMessage("Country created successfully.");
    }

    public function getCountries()
    {
        $countries = Countries::all();
        return $this->successWithData($countries, "Fetch countries", 200);
    }

    public function createCities(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'countryId' => 'required',
            'name' => 'required|string|unique:cities,name'
        ]);

        if ($validator->fails()) {
            return $this->fail(422, "Invalid credentials", $validator->errors()->first());
        }

        $cities = Cities::create([
            'country_id' => $request->countryId,
            'name' => $request->name
        ]);

        return $this->successMessage("City created successfully.");
    }

    public function getCities($country_id = null)
    {
        if ($country_id === null) {
            $cities = Cities::all();
        } else {
            $cities = Cities::where('country_id', $country_id)->get();
        }
    
        return $this->successWithData($cities, "Fetch cities", 200);
    }
}
