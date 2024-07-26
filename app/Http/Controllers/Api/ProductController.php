<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Traits\HandleResponse;
use App\Models\Products;

class ProductController extends Controller
{
    use HandleResponse;

    // Get all products
    public function index()
    {
        $products = Products::all();
        return $this->successWithData($products, 'Products retrieved successfully.');
    }

    // Create a new product
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'short_desc' => 'required|string|max:255',
            'desc' => 'required|string',
            'amount' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:subcategories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->badRequestResponse('Validation failed.', $validator->errors());
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('asset/uploads/product_images'), $imagePath);
            $imagePath = 'asset/uploads/product_images/' . $imagePath;
        }

        $product = Products::create(array_merge(
            $request->except('image'),
            ['image' => $imagePath]
        ));

        return $this->successWithData($product, 'Product created successfully.');
    }

    // Get a single product
    public function show($id)
    {
        $product = Products::find($id);
        return $this->successWithData($product, 'Product retrieved successfully.');
    }

    // Update a product
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'short_desc' => 'sometimes|required|string|max:255',
            'desc' => 'sometimes|required|string',
            'amount' => 'sometimes|required|numeric',
            'category_id' => 'sometimes|required|exists:categories,id',
            'subcategory_id' => 'sometimes|required|exists:subcategories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->badRequestResponse('Validation failed.', $validator->errors());
        }

        $product = Products::find($id);

        if ($request->hasFile('image')) {

            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }

            $image = $request->file('image');
            $imagePath = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('asset/uploads/product_images'), $imagePath);
            $product->image = 'asset/uploads/product_images/' . $imagePath;
        }

        $product->update($request->except('image'));

        return $this->successWithData($product, 'Product updated successfully.');
    }

    // Delete a product
    public function destroy($id)
    {
        Products::find($id)->delete();
        return $this->successMessage('Product deleted successfully.');
    }
}
