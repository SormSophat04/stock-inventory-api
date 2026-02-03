<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    use ApiResponse;

    // List all categories (all roles can view)
    public function index()
    {
        $categories = Category::orderBy('category_id', 'desc')->get();
        return $this->success($categories, 'Categories retrieved successfully.');
    }

    // Create a new category (admin + manager)
    public function store(StoreCategoryRequest $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $category = Category::create($request->validated());

        return $this->success($category, 'Category created successfully', 201);
    }

    // Show single category
    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) return $this->error('Category not found', 404);

        return $this->success($category, 'Category retrieved successfully.');
    }

    // Update category (admin + manager)
    public function update(UpdateCategoryRequest $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $category = Category::find($id);
        if (!$category) return $this->error('Category not found', 404);

        $category->update($request->validated());

        return $this->success($category, 'Category updated successfully');
    }

    // Delete category (admin only)
    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        $category = Category::find($id);
        if (!$category) return $this->error('Category not found', 404);

        $category->delete();

        return $this->success([], 'Category deleted successfully');
    }
}
