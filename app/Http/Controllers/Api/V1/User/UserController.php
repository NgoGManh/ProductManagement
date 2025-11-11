<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
/**
 * @OA\Tag(
 *   name="Users",
 *   description="Admin user management endpoints"
 * )
 */

class UserController extends Controller
{
    public function __construct()
    {
        // Admin access only
        $this->middleware(["auth:api", "role:admin"]);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/users",
     *   tags={"Users"},
     *   summary="List users (Admin only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Successful operation")
     * )
     */

    public function index(Request $request): JsonResponse
    {
        $query = User::with("roles:id,name")->orderByDesc("created_at");

        // --- Filtering ---
        if ($search = $request->input("search")) {
            $query->where(function ($q) use ($search) {
                $q->where("first_name", "like", "%$search%")
                    ->orWhere("last_name", "like", "%$search%")
                    ->orWhere("email", "like", "%$search%");
            });
        }

        if ($status = $request->input("status")) {
            $query->where("status", $status);
        }

        // --- Pagination ---
        $perPage = (int) $request->input("per_page", 10);
        $users = $query->paginate($perPage);

        return response()->json([
            "status" => "success",
            "data" => $users,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/users",
     *   tags={"Users"},
     *   summary="Create new user (Admin only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *       @OA\JsonContent(
     *           required={"first_name","last_name","email","password","password_confirmation","roles"},
     *           @OA\Property(property="first_name", type="string", example="Jane"),
     *           @OA\Property(property="last_name", type="string", example="Smith"),
     *           @OA\Property(property="email", type="string", example="jane@company.com"),
     *           @OA\Property(property="password", type="string", example="12345678"),
     *           @OA\Property(property="password_confirmation", type="string", example="12345678"),
     *           @OA\Property(property="roles", type="array", @OA\Items(type="string", example="editor")),
     *           @OA\Property(property="status", type="string", example="ACTIVE")
     *       )
     *   ),
     *   @OA\Response(response=201, description="User created successfully")
     * )
     */

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "first_name" => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            "last_name" => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            "email" => "required|email|max:255|unique:users,email",
            "mobile" => 'nullable|string|max:15|regex:/^[0-9+\-\s]+$/|unique:users,mobile',
            "password" => "required|string|min:8|confirmed",
            "status" => "required|in:ACTIVE,INACTIVE",
            "roles" => "required|array|min:1",
            "roles.*" => "exists:roles,name",
            "avatar" => "nullable|image|mimes:jpeg,png,jpg,gif|max:2048",
        ]);

        // Upload avatar
        if ($request->hasFile("avatar")) {
            $validated["avatar"] = $request->file("avatar")->store("avatars", "public");
        }

        $validated["password"] = Hash::make($validated["password"]);
        $user = User::create($validated);

        // Assign roles and permissions
        $user->assignRole($validated["roles"]);

        return response()->json(
            [
                "status" => "success",
                "message" => "User created successfully",
                "data" => $user->load("roles:id,name"),
            ],
            Response::HTTP_CREATED,
        );
    }

    /**
     * @OA\Get(
     *   path="/api/v1/users/{id}",
     *   tags={"Users"},
     *   summary="Get user details (Admin only)",
     *   description="Get detailed information about a specific user including roles and audit information",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="User ID",
     *       @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful operation",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="string", example="success"),
     *           @OA\Property(property="data", type="object")
     *       )
     *   ),
     *   @OA\Response(response=404, description="User not found"),
     *   @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function show(User $user): JsonResponse
    {
        $user->load(["roles:id,name", "creator:id,first_name,last_name", "updator:id,first_name,last_name"]);

        return response()->json([
            "status" => "success",
            "data" => $user,
        ]);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/users/{id}",
     *   tags={"Users"},
     *   summary="Update user (Admin only)",
     *   description="Update user information including roles and profile data",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="User ID",
     *       @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"first_name","last_name","email","status","roles"},
     *               @OA\Property(property="first_name", type="string", example="Jane"),
     *               @OA\Property(property="last_name", type="string", example="Smith"),
     *               @OA\Property(property="email", type="string", example="jane@company.com"),
     *               @OA\Property(property="mobile", type="string", example="+84987654321"),
     *               @OA\Property(property="password", type="string", example="newpassword123"),
     *               @OA\Property(property="password_confirmation", type="string", example="newpassword123"),
     *               @OA\Property(property="status", type="string", enum={"ACTIVE","INACTIVE"}, example="ACTIVE"),
     *               @OA\Property(property="roles", type="array", @OA\Items(type="string", example="editor")),
     *               @OA\Property(property="avatar", type="string", format="binary")
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="User updated successfully",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="string", example="success"),
     *           @OA\Property(property="message", type="string", example="User updated successfully"),
     *           @OA\Property(property="data", type="object")
     *       )
     *   ),
     *   @OA\Response(response=404, description="User not found"),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            "first_name" => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            "last_name" => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            "email" => "required|email|max:255|unique:users,email," . $user->id,
            "mobile" => 'nullable|string|max:15|regex:/^[0-9+\-\s]+$/|unique:users,mobile,' . $user->id,
            "password" => "nullable|string|min:8|confirmed",
            "status" => "required|in:ACTIVE,INACTIVE",
            "roles" => "required|array|min:1",
            "roles.*" => "exists:roles,name",
            "avatar" => "nullable|image|mimes:jpeg,png,jpg,gif|max:2048",
        ]);

        // Upload new avatar
        if ($request->hasFile("avatar")) {
            if ($user->avatar && Storage::disk("public")->exists($user->avatar)) {
                Storage::disk("public")->delete($user->avatar);
            }
            $validated["avatar"] = $request->file("avatar")->store("avatars", "public");
        }

        // Hash password
        if (!empty($validated["password"])) {
            $validated["password"] = Hash::make($validated["password"]);
        } else {
            unset($validated["password"]);
        }

        // Update user
        $user->update($validated);

        // Update roles
        $user->syncRoles($validated["roles"]);

        return response()->json([
            "status" => "success",
            "message" => "User updated successfully",
            "data" => $user->load("roles:id,name"),
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/users/{id}",
     *   tags={"Users"},
     *   summary="Delete user (Admin only)",
     *   description="Soft delete a user (cannot delete admin users)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="User ID",
     *       @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="User deleted successfully",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="string", example="success"),
     *           @OA\Property(property="message", type="string", example="User deleted successfully")
     *       )
     *   ),
     *   @OA\Response(response=403, description="Cannot delete admin user"),
     *   @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->hasRole("admin")) {
            return response()->json(
                [
                    "status" => "error",
                    "message" => "Cannot delete admin user",
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        if ($user->avatar && Storage::disk("public")->exists($user->avatar)) {
            Storage::disk("public")->delete($user->avatar);
        }

        $user->delete();

        return response()->json([
            "status" => "success",
            "message" => "User deleted successfully",
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/users/restore/{id}",
     *   tags={"Users"},
     *   summary="Restore deleted user (Admin only)",
     *   description="Restore a soft-deleted user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="User ID",
     *       @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="User restored successfully",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="string", example="success"),
     *           @OA\Property(property="message", type="string", example="User restored successfully"),
     *           @OA\Property(property="data", type="object")
     *       )
     *   ),
     *   @OA\Response(response=404, description="User not found")
     * )
     */
    public function restore($id): JsonResponse
    {
        $user = User::onlyTrashed()->find($id);

        if (!$user) {
            return response()->json(
                [
                    "status" => "error",
                    "message" => "User not found",
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        $user->restore();

        return response()->json([
            "status" => "success",
            "message" => "User restored successfully",
            "data" => $user,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/users/{id}/status",
     *   tags={"Users"},
     *   summary="Change user status (Admin only)",
     *   description="Activate or deactivate a user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="User ID",
     *       @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *           required={"status"},
     *           @OA\Property(property="status", type="string", enum={"ACTIVE","INACTIVE"}, example="ACTIVE")
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="User status updated successfully",
     *       @OA\JsonContent(
     *           @OA\Property(property="status", type="string", example="success"),
     *           @OA\Property(property="message", type="string", example="User John Doe marked as ACTIVE"),
     *           @OA\Property(property="data", type="object",
     *               @OA\Property(property="id", type="integer", example=1),
     *               @OA\Property(property="status", type="string", example="ACTIVE")
     *           )
     *       )
     *   ),
     *   @OA\Response(response=404, description="User not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function changeStatus(Request $request, User $user): JsonResponse
    {
        $request->validate(["status" => "required|in:ACTIVE,INACTIVE"]);
        $user->status = $request->status;
        $user->save();

        return response()->json([
            "status" => "success",
            "message" => "User {$user->full_name} marked as {$request->status}",
            "data" => ["id" => $user->id, "status" => $user->status],
        ]);
    }
}
