<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:categories,slug',
                'icon' => 'required|string|max:255',
                'active' => 'boolean',
            ]);

            $category = Category::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada',
                'category' => $category,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, Category $category)
    {
        try {
            $data = $request->validate([
                'name' => 'sometimes|string|max:255',
                'slug' => 'sometimes|string|max:255|unique:categories,slug,' . $category->id,
                'icon' => 'sometimes|string|max:255',
                'active' => 'sometimes|boolean',
            ]);

            $category->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada',
                'category' => $category,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada',
        ]);
    }
}
