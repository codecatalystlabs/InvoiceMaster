<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->q, fn ($q) => $q->where('action', 'like', '%'.$request->q.'%')->orWhere('entity_type', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
