<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit trail of web admin actions (created/updated/deleted per model row),
 * written automatically by the global listener in AppServiceProvider.
 * Storage only — no UI page; query it directly when investigating.
 */
class ActivityLog extends Model
{
    public $table = 'activity_logs';

    const UPDATED_AT = null;

    protected $guarded = [];
}
