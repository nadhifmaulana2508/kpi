<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\OrgUnit;
use App\Services\DemoAccessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OrgUnitController extends Controller
{
    public function index(Request $request, DemoAccessContext $context): JsonResponse
    {
        $validated = $request->validate([
            'tree' => ['sometimes', 'nullable', 'boolean'],
            'type' => ['sometimes', 'nullable', 'in:HEAD_OFFICE,DIVISION,DEPARTMENT,SUB_DEPARTMENT,REGION,BRANCH,SECTION'],
        ]);

        /** @var Employee $employee */
        $employee = $request->user();
        $scopeIds = $context->scopeIds($employee);
        $units = OrgUnit::query()
            ->where('demo_only', true)
            ->whereIn('id', $scopeIds)
            ->orderBy('code')
            ->get();

        $tree = filter_var($validated['tree'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $type = $validated['type'] ?? null;

        if (! $tree) {
            $flatUnits = $type ? $units->where('type', $type) : $units;

            return response()->json([
                'data' => $flatUnits->map(fn (OrgUnit $unit) => [
                    'id' => $unit->id,
                    'code' => $unit->code,
                    'name' => $unit->name,
                    'type' => $unit->type,
                    'parent_id' => $unit->parent_id,
                    'children' => [],
                ])->values(),
            ]);
        }

        $children = $units->groupBy('parent_id');
        $roots = $type
            ? $units->where('type', $type)
            : $units->filter(fn (OrgUnit $unit) => ! $unit->parent_id || ! in_array($unit->parent_id, $scopeIds, true));

        return response()->json([
            'data' => $roots->map(fn (OrgUnit $unit) => $this->serialize($unit, $children))->values(),
        ]);
    }

    private function serialize(OrgUnit $unit, Collection $children): array
    {
        $childUnits = $children->get($unit->id, collect());

        return [
            'id' => $unit->id,
            'code' => $unit->code,
            'name' => $unit->name,
            'type' => $unit->type,
            'parent_id' => $unit->parent_id,
            'children' => $childUnits
                ->map(fn (OrgUnit $child) => $this->serialize($child, $children))
                ->values(),
        ];
    }
}
