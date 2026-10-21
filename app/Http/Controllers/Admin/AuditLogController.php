<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()->can('audit.view'), 403);

        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->when($request->string('action')->toString(), fn ($query, $action) => $query->where('action', $action))
            ->when($request->integer('user_id'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($request->string('q')->toString(), function ($query, $term) {
                $query->where(function ($builder) use ($term) {
                    $builder->where('action', 'like', "%{$term}%")
                        ->orWhere('ip_address', 'like', "%{$term}%")
                        ->orWhereHas('actor', fn ($actor) => $actor
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%"));
                });
            })
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => AuditAction::tryFrom($log->action)?->label() ?? $log->action,
                'entity' => class_basename((string) $log->entity_type),
                'entity_id' => $log->entity_id,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->format('Y-m-d H:i'),
                'actor' => $log->actor?->only(['id', 'name', 'email']),
            ]);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['q', 'action', 'user_id', 'from', 'to']),
            'actions' => collect(AuditAction::cases())->map(fn (AuditAction $action) => [
                'value' => $action->value,
                'label' => $action->label(),
            ]),
            'actors' => User::query()
                ->whereHas('roles', fn ($query) => $query->whereNotIn('name', ['customer']))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
