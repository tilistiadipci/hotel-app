<?php

namespace App\Http\Controllers;

use App\Repositories\PlaceCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceCategoryController extends Controller
{
    protected $categoryRepository;

    public function __construct(PlaceCategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:places_categories,name',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        // ensure slug unique
        if ($this->categoryRepository->findBySlug($data['slug'])) {
            throw ValidationException::withMessages([
                'name' => 'Kategori dengan nama tersebut sudah ada.',
            ]);
        }

        $category = $this->categoryRepository->create($data);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
        ]);
    }
}
