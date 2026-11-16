<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DemoAccessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentUserController extends Controller
{
    public function __invoke(Request $request, DemoAccessContext $context): JsonResponse
    {
        return response()->json(['data' => $context->for($request->user())]);
    }
}
