<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Traits\HandleResponse;
use App\Models\Category;
use App\Models\Subcategory;

class CategoriesController extends Controller
{
    use HandleResponse;

    public function getAllCategories($data = '')
    {
        if($data == 'all'){
            $categories = Category::all();
        }else{
            $categories = Category::select('id', 'name')->get();
        }
        return $this->successWithData($categories, "Categories get successfully" , 200 );
    }

    public function getSubcategoriesByCategory($id)
    {
        $category = Category::with('subcategories')->find($id);

        if ($category) {
            $subcategories = $category->subcategories()->select('id', 'name')->get();
            return $this->successWithData($subcategories, "Sub Categories get successfully" , 200 );
        } else {
            return $this->badRequestResponse('Sub Categories not found');
        }
    }
}
