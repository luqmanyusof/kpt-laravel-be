<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // Use the correct User model import
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource (GET /api/users).
     */
    public function index()
    {
        // Retrieve all users, but only select safe public fields
        $users = User::all(['id', 'name', 'email', 'created_at']);

        // Return the collection using the Laravel JSON helper for correct headers (HTTP 200 OK)
        return response()->json([
            'status' => 'success',
            'data' => $users
        ], 200);
    }

    /**
     * Store a newly created resource in storage (POST /api/users - Registration).
     */
    public function store(Request $request)
    {
        try {
            // 1. Validate the incoming request data
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed', // 'confirmed' requires password_confirmation field
            ]);

            // 2. Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password), // Hash the password securely
            ]);

            // 3. Return a successful response (HTTP 201 Created)
            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully.',
                'user' => $user->only(['id', 'name', 'email']),
            ], 201);

        } catch (ValidationException $e) {
            // Return 422 Unprocessable Entity on validation failure
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified resource (GET /api/users/{id}).
     */
    public function show(string $id)
    {
        try {
            // Find the user or throw a 404 exception if not found
            $user = User::findOrFail($id, ['id', 'name', 'email', 'created_at']);

            return response()->json([
                'status' => 'success',
                'data' => $user
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage (PUT/PATCH /api/users/{id}).
     */
    public function update(Request $request, string $id)
    {
        try {
            // Find the user first
            $user = User::findOrFail($id);

            // 1. Define validation rules (email unique rule must ignore the current user)
            $rules = [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,'.$id,
                'password' => 'sometimes|nullable|string|min:8|confirmed',
            ];
            $request->validate($rules);

            // 2. Prepare data for update
            $data = $request->only(['name', 'email']);

            if ($request->filled('password')) {
                // Only update the password if it was provided
                $data['password'] = Hash::make($request->password);
            }

            // 3. Update the user record
            $user->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully.',
                'user' => $user->only(['id', 'name', 'email']),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found for update.'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage (DELETE /api/users/{id}).
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            // HTTP 204 No Content is standard for successful deletion
            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully.'
            ], 204);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found for deletion.'
            ], 404);
        }
    }

    /**
     * Creates and returns a single fake user (GET /api/user/insert-fake).
     */
    public function insertFake()
    {
        // Use the factory to create a user and save it to the database
        $createdUser = User::factory()->create();

        return response()->json([
            'status' => 'success',
            'message' => 'Fake user created successfully.',
            'user' => $createdUser->only(['id', 'name', 'email']),
        ], 201);
    }
}
