<?php

namespace App\Services;

use App\Models\Enrollment;
use Illuminate\Support\Collection;

class GradeCalculationService
{
    /**
     * Calculate the weighted average for a specific student in a specific course assignment,
     * optionally filtered by a specific academic period.
     */
    public function calculateStudentAverage(string $enrollmentId, ?string $academicPeriodId = null): float|null
    {
        $enrollment = Enrollment::with(['grades.evaluation'])->find($enrollmentId);
        if (!$enrollment) {
            return null;
        }

        $grades = $enrollment->grades->filter(function ($grade) use ($academicPeriodId) {
            $evaluation = $grade->evaluation;
            if (!$evaluation || !$evaluation->is_published) {
                return false;
            }
            if ($academicPeriodId && $evaluation->academic_period_id !== $academicPeriodId) {
                return false;
            }
            return true;
        });

        if ($grades->isEmpty()) {
            return null;
        }

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($grades as $grade) {
            $weight = (float) ($grade->evaluation->weight ?? 100);
            $score = (float) $grade->score;
            $totalScore += ($score * ($weight / 100));
            $totalWeight += $weight;
        }

        if ($totalWeight == 0) {
            return null;
        }

        // Normalize back to 0-100 or 0-20 scale based on weights
        return round($totalScore / ($totalWeight / 100), 2);
    }

    /**
     * Get a detailed breakdown of a student's performance in an enrollment.
     */
    public function getStudentBreakdown(string $enrollmentId, ?string $academicPeriodId = null): array
    {
        $average = $this->calculateStudentAverage($enrollmentId, $academicPeriodId);
        
        $enrollment = Enrollment::with(['grades.evaluation' => function ($query) use ($academicPeriodId) {
            $query->where('is_published', true);
            if ($academicPeriodId) {
                $query->where('academic_period_id', $academicPeriodId);
            }
        }])->find($enrollmentId);

        return [
            'enrollment_id' => $enrollmentId,
            'average' => $average,
            'grades' => $enrollment ? $enrollment->grades : []
        ];
    }
}
