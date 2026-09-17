<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Services\Promotion\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPromotionController extends Controller
{
    public function index(Request $request, PromotionService $service)
    {
        return response()->json([
            'status' => true,
            'data' => $service->listForAdmin((int) $request->input('per_page', 15)),
        ]);
    }

    public function store(Request $request, PromotionService $service)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'text' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $promotion = $service->create($validated, $request->file('image'), Auth::user());

        return response()->json([
            'status' => true,
            'message' => 'Promoção criada com sucesso.',
            'data' => $promotion,
        ], 201);
    }

    public function update(Request $request, Promotion $promotion, PromotionService $service)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'text' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $promotion = $service->update($promotion, $validated, $request->file('image'));

        return response()->json([
            'status' => true,
            'message' => 'Promoção atualizada com sucesso.',
            'data' => $promotion,
        ]);
    }

    public function destroy(Promotion $promotion, PromotionService $service)
    {
        $service->delete($promotion);

        return response()->json([
            'status' => true,
            'message' => 'Promoção removida com sucesso.',
        ]);
    }

    public function reorder(Request $request, PromotionService $service)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:promotions,id'],
        ]);

        $service->reorder($validated['ids']);

        return response()->json([
            'status' => true,
            'message' => 'Ordem das promoções atualizada.',
        ]);
    }
}
