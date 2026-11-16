<?php

namespace App\Services;

use App\Models\PerformanceContract;

class DemoScoreCalculator
{
    private const MIN_VALID_WEIGHT = 0.7;

    private const CAP_A = 120.0;

    public function recalculate(PerformanceContract $contract): array
    {
        return $this->calculate($contract, true);
    }

    public function calculate(PerformanceContract $contract, bool $persist = false): array
    {
        abort_unless($contract->demo_only, 409, 'Rumus demo hanya dapat dipakai pada kontrak demo.');

        $contract->loadMissing('items');
        $items = $contract->items;
        $totalWeight = 0.0;
        $validWeight = 0.0;
        $weightedScore = 0.0;
        $pendingCount = 0;
        $itemScores = [];

        foreach ($items as $item) {
            $weight = (float) $item->weight;
            $totalWeight += $weight;
            $score = null;
            $isComplete = false;

            if ($item->state === 'NOT_APPLICABLE' && filled($item->reason)) {
                $isComplete = true;
            } elseif ($item->state === 'VALID' && $item->actual !== null && $this->hasEvidence($item->evidence)) {
                $score = $this->scoreItem(
                    $item->score_type,
                    (float) $item->target,
                    (float) $item->actual,
                );
                $isComplete = $score !== null;
            }

            if ($isComplete && $score !== null) {
                $validWeight += $weight;
                $weightedScore += $score * $weight;
            } elseif (! $isComplete) {
                $pendingCount++;
            }

            $itemScores[$item->id] = $score;
            if ($persist && $item->score !== $score) {
                $item->forceFill(['score' => $score])->save();
            }
        }

        $validWeightsSumToOne = abs($totalWeight - 1.0) < 0.000001;
        $kpiScore = null;
        $message = null;

        if (! $validWeightsSumToOne) {
            $message = 'Total bobot kontrak demo harus 100%.';
        } elseif ($pendingCount > 0) {
            $message = $pendingCount.' KPI belum lengkap.';
        } elseif ($validWeight < self::MIN_VALID_WEIGHT) {
            $message = 'Bobot data valid kurang dari 70%.';
        } elseif ($validWeight > 0) {
            $kpiScore = round($weightedScore / $validWeight, 3);
        }

        return [
            'kpi_score' => $kpiScore,
            'behavior_score' => null,
            'final_score' => null,
            'rating' => null,
            'valid_weight' => round($validWeight, 4),
            'total_weight' => round($totalWeight, 4),
            'pending_items' => $pendingCount,
            'formula_version' => $contract->cycle?->formula_version ?? 'demo-prototype-v1',
            'demo_only' => true,
            'message' => $message,
            'item_scores' => $itemScores,
        ];
    }

    private function hasEvidence(?array $evidence): bool
    {
        return is_array($evidence) && collect($evidence)->contains(
            fn ($item) => is_array($item) && filled($item['label'] ?? null) && filled($item['reference'] ?? null),
        );
    }

    private function scoreItem(string $type, float $target, float $actual): ?float
    {
        if (! in_array($type, ['A', 'Q', 'L', 'Z'], true)) {
            return null;
        }

        if ($type === 'Z') {
            if ($target !== 0.0 || $actual < 0 || floor($actual) !== $actual) {
                return null;
            }

            return $actual === 0.0 ? 100.0 : 0.0;
        }

        if ($target <= 0) {
            return null;
        }

        if (in_array($type, ['Q', 'L'], true) && ($actual < 0 || $actual > 100 || $target > 100)) {
            return null;
        }

        $raw = $type === 'L'
            ? 100 * (2 - $actual / $target)
            : 100 * $actual / $target;
        $cap = $type === 'A' ? self::CAP_A : 100.0;

        return round(max(0.0, min($cap, $raw)), 3);
    }
}
