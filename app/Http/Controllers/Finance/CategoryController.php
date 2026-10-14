<?php

namespace App\Http\Controllers\Finance;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * List the user's categories.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $categories = $user->categories()
            ->withCount('transactions')
            ->orderBy('name')
            ->get();

        return Inertia::render('categories/Index', [
            'categories' => CategoryResource::collection($categories)->resolve(),
            'types' => TransactionType::options(),
        ]);
    }

    /**
     * Store a new category.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->categories()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    /**
     * Update a category. Its type cannot change once it has transactions.
     */
    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $attributes = $request->validated();

        if ($attributes['type'] !== $category->type->value && $category->transactions()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Categories with transactions cannot change type.'),
            ]);

            return back();
        }

        $category->update($attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    /**
     * Delete a category. Its transactions become uncategorized.
     */
    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }
}
