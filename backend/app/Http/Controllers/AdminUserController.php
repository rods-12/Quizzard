<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    private function allowedRoles(): array
    {
        $user = Auth::user();

        if ($user && $user->role === 'superadmin') {
            return ['teacher', 'student', 'admin'];
        }

        return ['teacher', 'student'];
    }

    private function ensureManageableUser(User $user): void
    {
        abort_if(!in_array($user->role, $this->allowedRoles()), 404);
    }

    private function hasProtectedActivity(User $user): bool
    {
        if ($user->role === 'teacher') {
            return $user->quizzes()->exists() || $user->taughtClasses()->exists();
        }

        if ($user->role === 'student') {
            return $user->enrolledClasses()->exists() || $user->quizAttempts()->exists();
        }

        return false;
    }

    private function accountValidationMessages(): array
    {
        return [
            'first_name.regex' => 'First name must not contain emojis or special characters.',
            'surname.regex' => 'Last name must not contain emojis or special characters.',
            'password.regex' => 'Password must contain uppercase, lowercase, number, and special character (@$!%*#?&).',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 50 characters.',
            'password.confirmed' => 'Passwords do not match.',
            'email.unique' => 'This email is already registered.',
            'email.max' => 'Email must not exceed 30 characters.',
        ];
    }

    public function index(Request $request)
    {
        return redirect()->route('admin.dashboard', $request->only('type', 'search'));
    }

    public function create(Request $request)
    {
        $allowedRoles = $this->allowedRoles();
        $type = $request->get('type', 'teacher');

        if (!in_array($type, $allowedRoles)) {
            $type = 'teacher';
        }

        return view('admin.users.create', compact('type'));
    }

    public function store(Request $request)
    {
        $allowedRoles = $this->allowedRoles();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-\.]+$/u'],
            'middle_initial' => ['nullable', 'string', 'size:1', 'alpha'],
            'surname' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-\.]+$/u'],
            'email' => ['required', 'email', 'max:30', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:50',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&]).+$/',
            ],
            'role' => ['required', Rule::in($allowedRoles)],
            'status' => ['required', Rule::in(['pending', 'active', 'deactivated'])],
        ], $this->accountValidationMessages());

        $middleInitial = $validated['middle_initial']
            ? strtoupper(substr($validated['middle_initial'], 0, 1))
            : null;

        $fullName = trim(sprintf(
            '%s%s %s',
            $validated['first_name'],
            $middleInitial ? ' ' . $middleInitial . '.' : '',
            $validated['surname']
        ));

        $user = User::create([
            'name' => $fullName,
            'first_name' => $validated['first_name'],
            'middle_initial' => $middleInitial,
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => ucfirst($user->role) . ' account created successfully.',
                'redirect' => route('admin.dashboard', ['type' => $user->role]),
            ]);
        }

        return redirect()
            ->route('admin.dashboard', ['type' => $validated['role']])
            ->with('success', ucfirst($validated['role']) . ' account created successfully.');
    }

    public function show(User $user)
    {
        $this->ensureManageableUser($user);

        if (request()->ajax()) {
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'password' => 'Hidden for security',
                'created_at' => optional($user->created_at)->format('F d, Y h:i A'),
                'updated_at' => optional($user->updated_at)->format('F d, Y h:i A'),
            ]);
        }

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->ensureManageableUser($user);

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureManageableUser($user);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-\.]+$/u'],
            'middle_initial' => ['nullable', 'string', 'size:1', 'alpha'],
            'surname' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-\.]+$/u'],
            'email' => [
                'required',
                'email',
                'max:30',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'status' => ['required', Rule::in(['pending', 'active', 'deactivated'])],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'max:50',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&]).+$/',
            ],
        ], $this->accountValidationMessages());

        $middleInitial = $validated['middle_initial']
            ? strtoupper(substr($validated['middle_initial'], 0, 1))
            : null;

        $fullName = trim(sprintf(
            '%s%s %s',
            $validated['first_name'],
            $middleInitial ? ' ' . $middleInitial . '.' : '',
            $validated['surname']
        ));

        $user->name = $fullName;
        $user->first_name = $validated['first_name'];
        $user->middle_initial = $middleInitial;
        $user->surname = $validated['surname'];
        $user->email = $validated['email'];
        $user->status = $validated['status'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => ucfirst($user->role) . ' account updated successfully',
                'redirect' => route('admin.dashboard', ['type' => $user->role])
            ]);
        }

        return redirect()
            ->route('admin.dashboard', ['type' => $user->role])
            ->with('success', ucfirst($user->role) . ' account updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->ensureManageableUser($user);

        if ($this->hasProtectedActivity($user)) {
            return redirect()
                ->route('admin.dashboard', ['type' => $user->role])
                ->withErrors([
                    'delete' => ucfirst($user->role) . ' accounts with existing activity cannot be deleted. Deactivate the account instead.',
                ]);
        }

        $role = $user->role;
        $user->delete();

        return redirect()
            ->route('admin.dashboard', ['type' => $role])
            ->with('success', ucfirst($role) . ' account deleted successfully.');
    }
}
