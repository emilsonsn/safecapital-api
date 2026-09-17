<?php

namespace App\Http\Controllers;

use App\Services\Promotion\PromotionService;

class PromotionController extends Controller
{
    public function index(PromotionService $service)
    {
        return response()->json([
            'status' => true,
            'data' => $service->listActive(),
        ]);
    }
}
