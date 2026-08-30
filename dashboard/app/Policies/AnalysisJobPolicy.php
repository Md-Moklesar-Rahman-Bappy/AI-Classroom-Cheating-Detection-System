<?php

namespace App\Policies;

use App\Models\AnalysisJob;
use App\Models\User;

class AnalysisJobPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator', 'reviewer', 'auditor']);
    }

    public function view(User $user, AnalysisJob $analysisJob): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator', 'reviewer', 'auditor']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin']);
    }

    public function update(User $user, AnalysisJob $analysisJob): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin']) && $analysisJob->status === 'pending';
    }

    public function delete(User $user, AnalysisJob $analysisJob): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin']);
    }

    public function cancel(User $user, AnalysisJob $analysisJob): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator']) && in_array($analysisJob->status, ['queued', 'processing', 'pending']);
    }

    public function retry(User $user, AnalysisJob $analysisJob): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin']) && in_array($analysisJob->status, ['failed', 'cancelled']);
    }

    public function report(User $user, AnalysisJob $analysisJob): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin', 'reviewer', 'auditor']) && $analysisJob->status === 'completed';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AnalysisJob $analysisJob): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AnalysisJob $analysisJob): bool
    {
        return false;
    }
}
