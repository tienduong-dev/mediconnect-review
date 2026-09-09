<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Audit trail for admin actions that change or remove data.
 *
 * Appointments already keep their own history in appointment_histories,
 * but everything else an admin can do - deactivating an account, hiding a
 * doctor from search, deleting master data - used to leave no trace at
 * all. In a clinic system "who did this, and when" has to be answerable.
 *
 * This writes to the normal Laravel log (storage/logs) instead of a new
 * table, so it needs no migration and no coordination with the rest of the
 * team. Every line carries the acting admin, the target row and the source
 * IP address.
 */
trait LogsAdminActions
{
    protected function logAdminAction(string $action, array $context = []): void
    {
        Log::info('[admin] ' . $action, array_merge([
            'admin_id' => auth()->id(),
            'admin'    => auth()->user()?->email,
            'ip'       => request()->ip(),
        ], $context));
    }
}
