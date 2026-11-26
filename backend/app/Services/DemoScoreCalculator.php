<?php

namespace App\Services;

use App\Models\PerformanceContract;

class DemoScoreCalculator
{
    private const MIN_VALID_WEIGHT = 0.7;

    private const CAP_A = 100.0;

    public function recalculate(PerformanceContract $contract): array
    {
        return $this->calculate($contract, true);
    }

    public function calculate(PerformanceContract $contract, bool $persist = false): array
    {
        abort_unless($contract->demo_only, 409, 'Rumus ini hanya berlaku untuk kontrak penilaian.');

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
            $message = 'Total bobot penilaian harus 100%.';
        } elseif ($pendingCount > 0) {
            $message = $pendingCount.' KPI belum lengkap.';
        } elseif ($validWeight < self::MIN_VALID_WEIGHT) {
            $message = 'Bobot data valid kurang dari 70%.';
        } elseif ($validWeight > 0) {
            $kpiScore = round($weightedScore / $validWeight, 3);
        }

        $ratings = data_get($contract->behavior_evaluation, 'ratings', []);
        $behaviorKeys = ['integrity', 'service', 'teamwork', 'compliance', 'responsibility'];
        $behaviorComplete = collect($behaviorKeys)->every(
            fn (string $key): bool => isset($ratings[$key]) && (int) $ratings[$key] >= 1 && (int) $ratings[$key] <= 5,
        );
        $behaviorScore = $behaviorComplete
            ? round(array_sum(array_map(fn (string $key): int => (int) $ratings[$key], $behaviorKeys)) / (count($behaviorKeys) * 5) * 100, 2)
            : null;
        $finalScore = $kpiScore !== null && $behaviorScore !== null
            ? round($kpiScore * 0.8 + $behaviorScore * 0.2, 2)
            : null;

        return [
            'kpi_score' => $kpiScore,
            'behavior_score' => $behaviorScore,
            'final_score' => $finalScore,
            'rating' => null,
            'valid_weight' => round($validWeight, 4),
            'total_weight' => round($totalWeight, 4),
            'pending_items' => $pendingCount,
            'formula_version' => $contract->cycle?->formula_version ?? 'demo-prototype-v1',
            'demo_only' => true,
            'message' => $message ?? ($behaviorScore === null
                ? 'Lima indikator perilaku belum lengkap.'
                : 'Nilai akhir memakai bobot KPI 80% dan perilaku 20%.'),
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
