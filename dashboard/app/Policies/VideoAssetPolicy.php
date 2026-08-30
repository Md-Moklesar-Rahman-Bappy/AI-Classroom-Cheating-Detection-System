<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VideoAsset;

class VideoAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator', 'reviewer', 'auditor']);
    }

    public function view(User $user, VideoAsset $videoAsset): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator', 'reviewer', 'auditor']);
    }

    public function edit(User $user, VideoAsset $videoAsset): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin']);
    }

    public function delete(User $user, VideoAsset $videoAsset): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin', 'invigilator']);
    }

    public function update(User $user, VideoAsset $videoAsset): bool
    {
        return $user->hasAnyRole(['system_admin', 'exam_admin']);
    }
}
