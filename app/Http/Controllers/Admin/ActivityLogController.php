<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        // Filtre section
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        // Filtre événement
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filtre utilisateur
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtre date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Recherche texte (description)
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('description', 'like', "%{$q}%")
                    ->orWhere('section', 'like', "%{$q}%");
            });
        }

        $logs     = $query->paginate(50)->withQueryString();
        $sections = ActivityLog::distinct()->orderBy('section')->pluck('section');
        $users    = User::orderBy('name')->get(['id', 'name']);

        return view('admin.activity-logs.index', compact('logs', 'sections', 'users'));
    }

    public function destroy(Request $request)
    {
        // Purge complète (super_admin uniquement — déjà garanti par la route)
        ActivityLog::truncate();
        return back()->with('success', 'Journal purgé avec succès.');
    }
}
