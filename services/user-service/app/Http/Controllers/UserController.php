<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserProfile::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->input('email') . '%');
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data'    => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'auth_user_id' => ['required', 'integer', 'unique:user_profiles,auth_user_id'],
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:user_profiles,email'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:500'],
            'role'         => ['sometimes', 'string', Rule::in(['admin', 'user'])],
        ]);

        $profile = UserProfile::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'User profile created successfully',
            'data'    => $profile,
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $profile = UserProfile::find($id);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'User profile not found',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully',
            'data'    => $profile,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $profile = UserProfile::find($id);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'User profile not found',
                'data'    => null,
            ], 404);
        }

        $validated = $request->validate([
            'name'    => ['sometimes', 'string', 'max:255'],
            'email'   => ['sometimes', 'email', Rule::unique('user_profiles', 'email')->ignore($profile->id)],
            'phone'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'role'    => ['sometimes', 'string', Rule::in(['admin', 'user'])],
        ]);

        $profile->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User profile updated successfully',
            'data'    => $profile->fresh(),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $authUser = $request->input('auth_user');

        if (($authUser['role'] ?? '') !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: admin access required',
                'data'    => null,
            ], 403);
        }

        $profile = UserProfile::find($id);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'User profile not found',
                'data'    => null,
            ], 404);
        }

        $profile->delete();

        return response()->json([
            'success' => true,
            'message' => 'User profile deleted successfully',
            'data'    => null,
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $authUser = $request->input('auth_user');
        $authUserId = $authUser['id'] ?? null;

        if (!$authUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to determine authenticated user',
                'data'    => null,
            ], 401);
        }

        $profile = UserProfile::where('auth_user_id', $authUserId)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found for this user',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data'    => $profile,
        ]);
    }
}
