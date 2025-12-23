<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public static function log(
        string $action,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null,
        ?Request $request = null
    ): AuditLog {
        $user = $user ?? auth()->user();
        $request = $request ?? request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'clinic_id' => $user?->clinic_id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public static function logLogin(User $user, Request $request): AuditLog
    {
        return self::log(
            'login',
            $user,
            null,
            null,
            $user,
            $request
        );
    }

    public static function logLogout(User $user, Request $request): AuditLog
    {
        return self::log(
            'logout',
            $user,
            null,
            null,
            $user,
            $request
        );
    }

    public static function logCreate(Model $model, ?User $user = null, ?Request $request = null): AuditLog
    {
        return self::log(
            'create',
            $model,
            null,
            $model->toArray(),
            $user,
            $request
        );
    }

    public static function logUpdate(Model $model, array $oldValues, array $newValues, ?User $user = null, ?Request $request = null): AuditLog
    {
        return self::log(
            'update',
            $model,
            $oldValues,
            $newValues,
            $user,
            $request
        );
    }

    public static function logDelete(Model $model, ?User $user = null, ?Request $request = null): AuditLog
    {
        return self::log(
            'delete',
            $model,
            $model->toArray(),
            null,
            $user,
            $request
        );
    }
}
