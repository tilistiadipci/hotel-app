<?php

namespace App\Http\Controllers;

use App\Repositories\MovieCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MovieCategoryController extends Controller
{
    protected $categoryRepository;

    public function __construct(MovieCategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        // Ensure unique slug
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $existing = $this->categoryRepository->findBySlug($data['slug']);
        if ($existing) {
            throw ValidationException::withMessages([
                'name' => 'Kategori sudah ada.',
            ]);
        }

        $category = $this->categoryRepository->create($data);

        return response()->json([
            'status' => true,
            'message' => 'Kategori berhasil ditambahkan',
            'data' => $category,
        ]);
    }

    private function validateRequest(Request $request): array
    {
        $rules = [
            'name' => 'required|max:100',
            'description' => 'nullable|string',
        ];

        return $request->validate($rules);
    }
}
