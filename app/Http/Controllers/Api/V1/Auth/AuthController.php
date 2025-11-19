<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Info(
 *    title="Product Management API",
 *    version="1.0.0",
 *    description="API for product management with user authentication and admin features. Supports CRUD operations, image upload, filtering, pagination, and JWT authentication.",
 * )
 *
 * @OA\Server(
 *    url=L5_SWAGGER_CONST_HOST,
 *    description="Production Server"
 * )
 *
 * @OA\Server(
 *    url="http://localhost:8000",
 *    description="Local Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *   name="Auth",
 *   description="User authentication and account management"
 * )
 */
class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth:api", ["except" => ["login", "register"]]);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/auth/register",
     *   tags={"Auth"},
     *   summary="Register a new user",
     *   description="Register a new user and return JWT token",
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *           required={"first_name","email","password","password_confirmation"},
     *           @OA\Property(property="first_name", type="string", example="John"),
     *           @OA\Property(property="last_name", type="string", example="Doe"),
     *           @OA\Property(property="email", type="string", example="john@example.com"),
     *           @OA\Property(property="password", type="string", format="password", example="12345678"),
     *           @OA\Property(property="password_confirmation", type="string", example="12345678")
     *       )
     *   ),
     *   @OA\Response(response=201, description="Registration successful"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "first_name" => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            "last_name" => 'nullable|string|max:255|regex:/^[\pL\s\-]+$/u',
            "email" => "required|email|max:255|unique:users,email",
            "password" => "required|string|min:8|confirmed",
        ]);

        $user = User::create([
            "first_name" => $validated["first_name"],
            "last_name" => $validated["last_name"] ?? "",
            "email" => $validated["email"],
            "password" => Hash::make($validated["password"]),
            "status" => "ACTIVE",
        ]);

        $user->assignRole("user");

        $token = JWTAuth::fromUser($user);

        return response()->json(
            [
                "status" => "success",
                "message" => "Registration successful",
                "user" => $user,
                "token" => $token,
            ],
            Response::HTTP_CREATED,
        );
    }

    /**
     * @OA\Post(
     *   path="/api/v1/auth/login",
     *   tags={"Auth"},
     *   summary="User login",
     *   description="Authenticate user and return JWT token",
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *           required={"email","password"},
     *           @OA\Property(property="email", type="string", example="john@example.com"),
     *           @OA\Property(property="password", type="string", format="password", example="12345678")
     *       )
     *   ),
     *   @OA\Response(response=200, description="Login successful"),
     *   @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            "email" => "required|email",
            "password" => "required|string|min:6",
        ]);

        if (!($token = auth("api")->attempt($credentials))) {
            return response()->json(
                [
                    "status" => "error",
                    "message" => "Invalid email or password",
                ],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return $this->respondWithToken($token);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/auth/me",
     *   tags={"Auth"},
     *   summary="Get authenticated user info",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = auth("api")->user();

        return response()->json([
            "status" => "success",
            "user" => $user->load("roles"),
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/auth/refresh",
     *   tags={"Auth"},
     *   summary="Refresh JWT token",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Token refreshed")
     * )
     */
    public function refresh(): JsonResponse
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return $this->respondWithToken($token);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/auth/logout",
     *   tags={"Auth"},
     *   summary="Logout user (invalidate token)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Successfully logged out")
     * )
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            "status" => "success",
            "message" => "Successfully logged out",
        ]);
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        /** @var User $user */
        $user = JWTAuth::setToken($token)->toUser();

        return response()->json([
            "status" => "success",
            "access_token" => $token,
            "token_type" => "bearer",
            "expires_in" => config("jwt.ttl") * 60, // default 1 hour
            "user" => $user->load("roles"),
        ]);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/auth/profile",
     *   tags={"Auth"},
     *   summary="Update current user profile",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *       required=false,
     *       @OA\JsonContent(
     *           @OA\Property(property="first_name", type="string", example="John"),
     *           @OA\Property(property="last_name", type="string", example="Doe"),
     *           @OA\Property(property="mobile", type="string", example="+84987654321")
     *       )
     *   ),
     *   @OA\Response(response=200, description="Profile updated successfully")
     * )
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth("api")->user();

        $validated = $request->validate([
            "first_name" => 'sometimes|string|max:255|regex:/^[\pL\s\-]+$/u',
            "last_name" => 'sometimes|string|max:255|regex:/^[\pL\s\-]+$/u',
            "mobile" => "sometimes|string|max:15|unique:users,mobile," . $user->id,
        ]);

        $user->update($validated);

        return response()->json([
            "status" => "success",
            "message" => "Profile updated successfully",
            "user" => $user->load("roles"),
        ]);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/auth/change-password",
     *   tags={"Auth"},
     *   summary="Change password",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *           required={"current_password","password","password_confirmation"},
     *           @OA\Property(property="current_password", type="string", example="oldpassword"),
     *           @OA\Property(property="password", type="string", example="newpassword"),
     *           @OA\Property(property="password_confirmation", type="string", example="newpassword")
     *       )
     *   ),
     *   @OA\Response(response=200, description="Password changed successfully"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "current_password" => "required|string",
            "password" => "required|string|min:8|confirmed",
        ]);

        /** @var User $user */
        $user = auth("api")->user();

        if (!Hash::check($validated["current_password"], $user->password)) {
            return response()->json(
                [
                    "status" => "error",
                    "message" => "Current password is incorrect",
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user->update([
            "password" => Hash::make($validated["password"]),
        ]);

        return response()->json([
            "status" => "success",
            "message" => "Password changed successfully",
        ]);
    }
}
