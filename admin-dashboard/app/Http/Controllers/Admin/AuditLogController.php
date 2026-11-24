<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:60'],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return view('admin.audit-logs', [
            'logs' => AuditLog::with('user:id,name')
                ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
                ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
                ->when($filters['from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', $date.' 00:00:00'))
                ->when($filters['to'] ?? null, fn ($q, $date) => $q->where('created_at', '<=', $date.' 23:59:59'))
                ->latest('id')
                ->paginate(50)
                ->withQueryString(),
            'actions' => AuditLog::distinct()->orderBy('action')->pluck('action', 'action'),
            'users' => User::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
