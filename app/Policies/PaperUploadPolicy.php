<?php

namespace App\Policies;

use App\Models\PaperUploads;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PaperUploadPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'campus_admin']);
    }

    public function update(User $user, PaperUploads $paper): bool
    {
        if ($user->role === 'super_admin') return true;
        return $user->role === 'campus_admin' && $user->campus_id === $paper->campus_id;
    }

    public function delete(User $user, PaperUploads $paper): bool
    {
        return $this->update($user, $paper);
    }

    public function viewMetadata(?User $user, PaperUploads $paper): bool
    {   
        if (!$paper->campus?->policy){
            return true;
        }
        return $this->checkAccess($user, $paper, 'guest_can_view_metadata', 'student_view_metadata_scope');
    }

    public function viewFile(?User $user, PaperUploads $paper): bool
    {
        return $this->checkAccess($user, $paper, 'guest_can_view_file', 'student_view_file_scope');
    }

    public function download(?User $user, PaperUploads $paper): bool
    {
        return $this->checkAccess($user, $paper, 'guest_can_download', 'student_download_scope');
    }

    private function checkAccess(?User $user, PaperUploads $paper, string $guestField, string $studentScopeField): bool
    {
        $policy = $paper->campus?->policy;

        if (!$user) {
            return $policy?->{$guestField} ?? false;
        }

        if (in_array($user->role, ['super_admin', 'campus_admin'])) {
            return true;
        }

        if ($user->role === 'student') {
            return match ($policy?->{$studentScopeField}) {
                'all_campuses' => true,
                'same_campus' => $user->campus_id === $paper->campus_id,
                default => false, // 'none' or missing policy
            };
        }

        return false;
    }
}
