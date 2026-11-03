<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Services\EInvoiceService;
use App\Models\ActivityLog;
use App\Models\ApiLog;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Blade::if('einvoice', function () {
            return app(EInvoiceService::class)->isEnabled();
        });

        Blade::if('noeinvoice', function () {
            return !app(EInvoiceService::class)->isEnabled();
        });

        // Audit trail: record every create/update/delete done by a logged-in web
        // admin into activity_logs (who, what model/row, old -> new). Driver API
        // traffic has no web Auth user and is already covered by api_logs, so it is
        // naturally excluded — as are console/cron runs.
        Event::listen(['eloquent.created: *', 'eloquent.updated: *', 'eloquent.deleted: *'], function ($event, $data) {
            $model = $data[0] ?? null;
            if (!$model || $model instanceof ActivityLog || $model instanceof ApiLog || !Auth::check()) {
                return;
            }

            $action = preg_match('/^eloquent\.(\w+)/', $event, $m) ? $m[1] : 'unknown';

            if ($action === 'updated') {
                $changes = [];
                foreach ($model->getChanges() as $field => $new) {
                    if ($field === 'updated_at') {
                        continue;
                    }
                    $changes[$field] = ['old' => $model->getOriginal($field), 'new' => $new];
                }
                if (empty($changes)) {
                    return;
                }
            } elseif ($action === 'deleted') {
                // full row snapshot, so a hard-deleted record can be reconstructed
                $changes = $model->getOriginal();
            } else {
                $changes = $model->getAttributes();
            }

            try {
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'user' => Auth::user()->name . ' (' . Auth::user()->email . ')',
                    'action' => $action,
                    'model' => class_basename($model),
                    'model_id' => $model->getKey(),
                    'changes' => json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // auditing must never break the action itself
                Log::warning('activity log write failed: ' . $e->getMessage());
            }
        });
    }
}
