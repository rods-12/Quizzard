<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller
{
    const MAX_ATTEMPTS = 5;
    const LOCKOUT_MINUTES = 15;

    // ─── LOGIN ───────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // Account activation rule:
        // - pending: registered from mobile app but still waiting for admin approval
        // - deactivated: blocked by admin and cannot log in
        // Only active accounts are allowed to proceed to login
        if ($user->status === 'deactivated') {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact the administrator.'
            ], 403);
        }

        if ($user->status === 'pending') {
            return response()->json([
                'message' => 'Your account is pending approval. Please wait for an administrator to activate your account.'
            ], 403);
        }

        if ($user->locked_until && Carbon::now()->lessThan($user->locked_until)) {
            $minutesLeft = Carbon::now()->diffInMinutes($user->locked_until, false);

            return response()->json([
                'message' => "Your account is locked. Please try again in {$minutesLeft} minute(s)."
            ], 423);
        }

        if (!Hash::check($request->password, $user->password)) {
            $user->failed_login_attempts += 1;

            if ($user->failed_login_attempts >= self::MAX_ATTEMPTS) {
                $user->locked_until = Carbon::now()->addMinutes(self::LOCKOUT_MINUTES);
                $user->save();

                return response()->json([
                    'message' => 'Too many failed attempts. Your account has been locked for 15 minutes.'
                ], 423);
            }

            $user->save();

            $remaining = self::MAX_ATTEMPTS - $user->failed_login_attempts;

            return response()->json([
                'message' => "Invalid credentials. {$remaining} attempt(s) remaining before lockout."
            ], 401);
        }

        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => [
                'id'     => $user->id,
                'name'   => $user->name,
                'first_name' => $user->first_name,
                'middle_initial' => $user->middle_initial,
                'surname' => $user->surname,
                'email'  => $user->email,
                'role'   => $user->role,
                'status' => $user->status,
            ]
        ], 200);
    }

    // ─── REGISTER ────────────────────────────────────────────
    public function register(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-\.]+$/u'],
            'middle_initial' => 'nullable|string|size:1|alpha',
            'surname' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\-\.]+$/u'],
            'email' => 'required|email|max:30|unique:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:50',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
            'role' => 'required|in:teacher,student',
        ], [
            'first_name.regex' => 'First name must not contain emojis or special characters.',
            'surname.regex' => 'Last name must not contain emojis or special characters.',
            'password.regex' => 'Password must contain uppercase, lowercase, number, and special character (@$!%*#?&).',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 50 characters.',
            'password.confirmed' => 'Passwords do not match.',
            'email.unique' => 'This email is already registered.',
            'email.max' => 'Email must not exceed 30 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $fullName = trim(sprintf('%s%s %s',
            $request->first_name,
            $request->middle_initial ? ' ' . strtoupper(substr($request->middle_initial, 0, 1)) . '.' : '',
            $request->surname
        ));

        $user = User::create([
            'name' => $fullName,
            'first_name' => $request->first_name,
            'middle_initial' => $request->middle_initial,
            'surname' => $request->surname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Registration successful! Please wait for an administrator to approve your account.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'middle_initial' => $user->middle_initial,
                'surname' => $user->surname,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ]
        ], 201);
    }

    // ─── LOGOUT ──────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.'], 200);
    }

    // ─── ME ──────────────────────────────────────────────────
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    // ─── UPDATE PROFILE ──────────────────────────────────────
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'first_name'       => 'sometimes|required|string|max:255',
            'middle_initial'   => 'nullable|string|size:1',
            'surname'          => 'sometimes|required|string|max:255',
            'current_password' => 'required_with:new_password|string',
            'new_password'     => 'sometimes|string|min:8|max:50|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&]).+$/',
            'profile_picture'  => 'sometimes|string',
        ], [
            'new_password.regex' => 'Password must contain uppercase, lowercase, number, and special character (@$!%*#?&).',
            'new_password.min'   => 'Password must be at least 8 characters.',
            'new_password.max'   => 'Password must not exceed 50 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if ($request->has('first_name')) {
            $user->first_name = $request->first_name;
        }

        if ($request->has('middle_initial')) {
            $user->middle_initial = strtoupper(substr($request->middle_initial, 0, 1));
        }

        if ($request->has('surname')) {
            $user->surname = $request->surname;
        }

        if ($request->hasAny(['first_name', 'middle_initial', 'surname'])) {
            $user->name = trim(sprintf('%s%s %s',
                $user->first_name ?? '',
                $user->middle_initial ? ' ' . strtoupper(substr($user->middle_initial, 0, 1)) . '.' : '',
                $user->surname ?? ''
            ));
        }

        if ($request->has('profile_picture')) {
            $user->profile_picture = $request->profile_picture;
        }

        if ($request->has('new_password')) {
            if (!\Illuminate\Support\Facades\Hash::check(
                $request->current_password,
                $user->password
            )) {
                return response()->json([
                    'message' => 'Current password is incorrect.',
                ], 422);
            }

            if (\Illuminate\Support\Facades\Hash::check(
                $request->new_password,
                $user->password
            )) {
                return response()->json([
                    'message' => 'New password must be different from your current password.',
                ], 422);
            }

            $user->password = \Illuminate\Support\Facades\Hash::make(
                $request->new_password
            );
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => [
                'id'              => $user->id,
                'name'            => $user->name,
                'first_name'      => $user->first_name,
                'middle_initial'  => $user->middle_initial,
                'surname'         => $user->surname,
                'email'           => $user->email,
                'role'            => $user->role,
                'profile_picture' => $user->profile_picture,
            ],
        ]);
    }
}
