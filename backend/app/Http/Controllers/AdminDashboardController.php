<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $loggedInUser = Auth::user();
        $isSuperAdmin = $loggedInUser && $loggedInUser->role === 'superadmin';

        $type = $request->input('type', 'teacher');
$search = trim($request->input('search', ''));
$filterBy = $request->input('filter_by', 'all');
$status = $request->input('status', 'pending');
$viewUserId = $request->input('view_user');

        $allowedTypes = ['teacher', 'student'];

        if ($isSuperAdmin) {
            $allowedTypes[] = 'admin';
        }

        if (!in_array($type, $allowedTypes, true)) {
            $type = $allowedTypes[0];
        }

        $allowedStatuses = ['all', 'pending', 'active', 'deactivated'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $query = User::query()
            ->where('role', $type)
            ->when($status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($search !== '', function ($q) use ($search, $filterBy) {
                $q->where(function ($sub) use ($search, $filterBy) {
                    if ($filterBy === 'first_name') {
                        $sub->where('first_name', 'like', "%{$search}%");
                    } elseif ($filterBy === 'middle_initial') {
                        $sub->where('middle_initial', 'like', "%{$search}%");
                    } elseif ($filterBy === 'surname') {
                        $sub->where('surname', 'like', "%{$search}%");
                    } else {
                        $sub->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_initial', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    }
                });
            })
            ->orderByDesc('created_at');

        $users = $query->paginate(10)->withQueryString();

        $stats = [
            'teachers_count'    => User::where('role', 'teacher')->count(),
            'students_count'    => User::where('role', 'student')->count(),
            'admins_count'      => User::where('role', 'admin')->count(),
            'activated_count'   => User::where('status', 'active')->count(),
            'deactivated_count' => User::where('status', 'deactivated')->count(),
        ];

        $roleScopedStatsQuery = User::where('role', $type);

        $roleStats = [
            'total'        => (clone $roleScopedStatsQuery)->count(),
            'pending'      => (clone $roleScopedStatsQuery)->where('status', 'pending')->count(),
            'active'       => (clone $roleScopedStatsQuery)->where('status', 'active')->count(),
            'deactivated'  => (clone $roleScopedStatsQuery)->where('status', 'deactivated')->count(),
        ];

        $selectedUser = null;

        if (!empty($viewUserId) && ctype_digit((string) $viewUserId)) {
            $selectedUser = User::query()
                ->where('id', (int) $viewUserId)
                ->whereIn('role', $allowedTypes)
                ->first();

            if ($selectedUser && $selectedUser->role !== $type) {
                $selectedUser = null;
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.dashboard.partials.users_table', compact('users', 'type', 'isSuperAdmin'))->render(),
            ]);
        }

        return view('admin.dashboard.index', compact(
            'users',
            'stats',
            'roleStats',
            'type',
            'search',
            'filterBy',
            'status',
            'isSuperAdmin',
            'selectedUser'
        ));
    }
}