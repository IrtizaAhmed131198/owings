<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Subcategory;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                'name' => 'Electronics',
                'image' => 'uploads/category/electronics.jpg',
                'rate' => 4.5,
                'subcategories' => [
                    ['name' => 'Mobiles', 'image' => 'uploads/subcategory/mobiles.jpg', 'rate' => 4.3],
                    ['name' => 'Laptops', 'image' => 'uploads/subcategory/laptops.jpg', 'rate' => 4.6],
                    ['name' => 'Tablets', 'image' => 'uploads/subcategory/tablets.jpg', 'rate' => 4.2],
                ]
            ],
            [
                'name' => 'Fashion',
                'image' => 'uploads/category/fashion.jpg',
                'rate' => 4.0,
                'subcategories' => [
                    ['name' => 'Men', 'image' => 'uploads/subcategory/men.jpg', 'rate' => 4.1],
                    ['name' => 'Women', 'image' => 'uploads/subcategory/women.jpg', 'rate' => 4.4],
                    ['name' => 'Kids', 'image' => 'uploads/subcategory/kids.jpg', 'rate' => 3.9],
                ]
            ],
            [
                'name' => 'Home & Kitchen',
                'image' => 'uploads/category/home_kitchen.jpg',
                'rate' => 4.3,
                'subcategories' => [
                    ['name' => 'Furniture', 'image' => 'uploads/subcategory/furniture.jpg', 'rate' => 4.5],
                    ['name' => 'Decor', 'image' => 'uploads/subcategory/decor.jpg', 'rate' => 4.2],
                    ['name' => 'Appliances', 'image' => 'uploads/subcategory/appliances.jpg', 'rate' => 4.6],
                ]
            ]
        ];

        foreach ($categories as $category) {
            $categoryModel = Category::create([
                'name' => $category['name'],
                'image' => $category['image'],
                'rate' => $category['rate'],
            ]);

            foreach ($category['subcategories'] as $subcategory) {
                Subcategory::create([
                    'name' => $subcategory['name'],
                    'category_id' => $categoryModel->id,
                    'image' => $subcategory['image'],
                    'rate' => $subcategory['rate'],
                ]);
            }
        }
    }
}
