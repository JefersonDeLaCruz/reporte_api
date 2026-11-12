<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->when($request->boolean('active_only'), fn ($q) => $q->where('active', true))
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    public function show(Category $category)
    {
        return response()->json([
            'success' => true,
            'category' => $category,
        ]);
    }
}
