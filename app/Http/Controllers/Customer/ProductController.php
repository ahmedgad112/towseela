<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function options(Product $product): JsonResponse
    {
        $product->loadMissing('business:id,is_active');

        abort_unless($product->is_active && $product->is_available && $product->business?->is_active, 404);

        $product->load([
            'options' => fn ($query) => $query->orderBy('id')->with(['values' => fn ($values) => $values->orderBy('id')]),
        ]);

        return response()->json([
            'options' => $product->options->map(fn ($option) => [
                'id' => $option->id,
                'name_ar' => $option->name_ar,
                'is_required' => $option->is_required,
                'max_select' => $option->max_select,
                'values' => $option->values->map(fn ($value) => [
                    'id' => $value->id,
                    'name_ar' => $value->name_ar,
                    'price_delta' => $value->price_delta,
                ])->values(),
            ])->values(),
        ]);
    }
}
