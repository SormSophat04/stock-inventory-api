<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse;

    // List all users
    public function index()
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $users = User::orderBy('user_id', 'desc')->get();
        return $this->success($users, 'Users retrieved successfully.');
    }

    // Create a new user
    public function store(StoreUserRequest $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $userData = $request->validated();
        $userData['password'] = Hash::make($request->password);

        try {
            $newUser = User::create($userData);
            return $this->success($newUser, 'User created successfully', 201);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Check which field caused the duplicate
            if (str_contains($e->getMessage(), 'email')) {
                return $this->error('A user with this email already exists.', 422);
            } elseif (str_contains($e->getMessage(), 'username')) {
                return $this->error('A user with this username already exists.', 422);
            }
            return $this->error('A user with these credentials already exists.', 422);
        } catch (\Exception $e) {
            return $this->error('Failed to create user: ' . $e->getMessage(), 500);
        }
    }

    // Show single user
    public function show($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $targetUser = User::find($id);
        if (!$targetUser) return $this->error('User not found', 404);

        return $this->success($targetUser, 'User retrieved successfully.');
    }

    // Update user
    public function update(UpdateUserRequest $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'manager'])) {
            return $this->error('Unauthorized', 403);
        }

        $targetUser = User::find($id);
        if (!$targetUser) return $this->error('User not found', 404);

        $userData = $request->validated();
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        } else {
            unset($userData['password']);
        }

        try {
            $targetUser->update($userData);
            return $this->success($targetUser, 'User updated successfully');
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            if (str_contains($e->getMessage(), 'email')) {
                return $this->error('A user with this email already exists.', 422);
            } elseif (str_contains($e->getMessage(), 'username')) {
                return $this->error('A user with this username already exists.', 422);
            }
            return $this->error('A user with these credentials already exists.', 422);
        } catch (\Exception $e) {
            return $this->error('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    // Delete user
    public function destroy($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return $this->error('Unauthorized', 403);
        }

        $targetUser = User::find($id);
        if (!$targetUser) return $this->error('User not found', 404);

        $targetUser->delete();

        return $this->success([], 'User deleted successfully');
    }
}
