<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action to the audit trail.
     */
    public static function log(
        string $action,
        ?string $modelType = null,
        ?string $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): AuditTrail {
        return AuditTrail::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * Log a login event.
     */
    public static function logLogin(): void
    {
        self::log('LOGIN', 'User', Auth::id(), null, null, 'User logged in');
    }

    /**
     * Log a logout event.
     */
    public static function logLogout(): void
    {
        self::log('LOGOUT', 'User', Auth::id(), null, null, 'User logged out');
    }

    /**
     * Log a model creation.
     */
    public static function logCreate($model, ?string $description = null): void
    {
        self::log(
            'CREATE',
            get_class($model),
            $model->id,
            null,
            $model->toArray(),
            $description ?? 'Record created'
        );
    }

    /**
     * Log a model update.
     */
    public static function logUpdate($model, array $oldValues, ?string $description = null): void
    {
        self::log(
            'UPDATE',
            get_class($model),
            $model->id,
            $oldValues,
            $model->toArray(),
            $description ?? 'Record updated'
        );
    }

    /**
     * Log a model deletion.
     */
    public static function logDelete($model, ?string $description = null): void
    {
        self::log(
            'DELETE',
            get_class($model),
            $model->id,
            $model->toArray(),
            null,
            $description ?? 'Record deleted'
        );
    }
}
