<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *    name="Products",
 *    description="Endpoints for managing products"
 * )
 */
class ProductController extends Controller
{
    public function __construct()
    {
        // Require authentication for all routes including index
        $this->middleware("auth:api");
    }

    /**
     * @OA\Get(
     *   path="/api/v1/products",
     *   summary="Get product list",
     *   description="Returns a paginated list of products. Supports search, filtering, and sorting.",
     *   operationId="getProducts",
     *   tags={"Products"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(name="search", in="query", description="Search by name or description", @OA\Schema(type="string")),
     *   @OA\Parameter(name="status", in="query", description="Filter by active/inactive", @OA\Schema(type="string", enum={"active","inactive"})),
     *   @OA\Parameter(name="min_price", in="query", description="Filter by minimum price", @OA\Schema(type="number")),
     *   @OA\Parameter(name="max_price", in="query", description="Filter by maximum price", @OA\Schema(type="number")),
     *   @OA\Parameter(name="sort", in="query", description="Sort field (default: created_at)", @OA\Schema(type="string")),
     *   @OA\Parameter(name="direction", in="query", description="Sort direction", @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer")),
     *
     *   @OA\Response(response=200, description="Successful operation"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->when(
                $request->search,
                fn($q, $v) => $q->where("name", "like", "%{$v}%")->orWhere("description", "like", "%{$v}%"),
            )
            ->when($request->status, fn($q, $v) => $q->where("active", $v === "active"))
            ->when($request->min_price, fn($q, $v) => $q->where("price", ">=", $v))
            ->when($request->max_price, fn($q, $v) => $q->where("price", "<=", $v))
            ->orderBy($request->input("sort", "created_at"), $request->input("direction", "desc"));

        $perPage = (int) $request->input("per_page", 10);
        $products = $query->paginate($perPage);

        return response()->json([
            "status" => "success",
            "data" => $products,
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/products/{id}",
     *   summary="Get product detail",
     *   description="Return product detail by ID",
     *   operationId="showProduct",
     *   tags={"Products"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(name="id", in="path", required=true, description="Product ID", @OA\Schema(type="integer")),
     *
     *   @OA\Response(response=200, description="Successful operation"),
     *   @OA\Response(response=404, description="Product not found"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(["createdBy:id,first_name,last_name", "updatedBy:id,first_name,last_name"]);

        return response()->json([
            "status" => "success",
            "data" => $product,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/products",
     *   summary="Create new product",
     *   description="Admin only. Upload multiple images to Cloudflare R2.",
     *   operationId="createProduct",
     *   tags={"Products"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"name","price","stock","active"},
     *               @OA\Property(property="name", type="string", example="MacBook Pro 16"),
     *               @OA\Property(property="description", type="string", example="M3 Chip, 32GB RAM, 1TB SSD"),
     *               @OA\Property(property="price", type="number", format="float", example=3499.99),
     *               @OA\Property(property="stock", type="integer", example=10),
     *               @OA\Property(property="active", type="boolean", example=true),
     *               @OA\Property(
     *                   property="images[]",
     *                   type="array",
     *                   @OA\Items(type="string", format="binary")
     *               )
     *           )
     *       )
     *   ),
     *
     *   @OA\Response(response=201, description="Product created successfully"),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize("create", Product::class);

        $validated = $request->validate([
            "name" => "required|string|max:255",
            "description" => "nullable|string",
            "price" => "required|numeric|min:0",
            "stock" => "required|integer|min:0",
            "active" => "required|boolean",
            "images.*" => "nullable|image|max:2048",
        ]);

        $paths = [];
        if ($request->hasFile("images")) {
            foreach ($request->file("images") as $file) {
                $filename =
                    "products/" .
                    now()->format("Ymd_His") .
                    "_" .
                    Str::random(8) .
                    "." .
                    $file->getClientOriginalExtension();
                Storage::disk("r2")->put($filename, file_get_contents($file));
                $paths[] = $filename;
            }
        }

        $validated["images"] = $paths;
        $product = Product::create($validated);

        return response()->json(
            [
                "status" => "success",
                "message" => "Product created successfully",
                "data" => $product,
            ],
            201,
        );
    }

    /**
     * @OA\Put(
     *   path="/api/v1/products/{id}",
     *   summary="Update product",
     *   description="Admin only. Update product information and images.",
     *   operationId="updateProduct",
     *   tags={"Products"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(name="id", in="path", required=true, description="Product ID", @OA\Schema(type="integer")),
     *
     *   @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               @OA\Property(property="name", type="string", example="MacBook Pro Updated"),
     *               @OA\Property(property="description", type="string", example="Updated description"),
     *               @OA\Property(property="price", type="number", format="float", example=2999.99),
     *               @OA\Property(property="stock", type="integer", example=5),
     *               @OA\Property(property="active", type="boolean", example=false),
     *               @OA\Property(
     *                   property="images[]",
     *                   type="array",
     *                   @OA\Items(type="string", format="binary")
     *               )
     *           )
     *       )
     *   ),
     *
     *   @OA\Response(response=200, description="Product updated successfully"),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorize("update", $product);

        $validated = $request->validate([
            "name" => "sometimes|required|string|max:255",
            "description" => "nullable|string",
            "price" => "sometimes|required|numeric|min:0",
            "stock" => "sometimes|required|integer|min:0",
            "active" => "sometimes|required|boolean",
            "images.*" => "nullable|image|max:2048",
        ]);

        $paths = $product->images ?? [];

        if ($request->hasFile("images")) {
            foreach ($request->file("images") as $file) {
                $filename =
                    "products/" .
                    now()->format("Ymd_His") .
                    "_" .
                    Str::random(8) .
                    "." .
                    $file->getClientOriginalExtension();
                Storage::disk("r2")->put($filename, file_get_contents($file));
                $paths[] = $filename;
            }
            $validated["images"] = $paths;
        }

        $product->update($validated);

        return response()->json([
            "status" => "success",
            "message" => "Product updated successfully",
            "data" => $product,
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/products/{id}",
     *   summary="Delete product",
     *   description="Soft delete product by ID.",
     *   operationId="deleteProduct",
     *   tags={"Products"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(name="id", in="path", required=true, description="Product ID", @OA\Schema(type="integer")),
     *
     *   @OA\Response(response=200, description="Product deleted successfully"),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize("delete", $product);
        $product->delete();

        return response()->json([
            "status" => "success",
            "message" => "Product deleted successfully",
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/products/{id}/restore",
     *   summary="Restore product",
     *   description="Restore a soft-deleted product.",
     *   operationId="restoreProduct",
     *   tags={"Products"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(name="id", in="path", required=true, description="Product ID", @OA\Schema(type="integer")),
     *
     *   @OA\Response(response=200, description="Product restored successfully"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function restore($id): JsonResponse
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return response()->json([
            "status" => "success",
            "message" => "Product restored successfully",
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/products/{id}/status",
     *   summary="Change product status",
     *   description="Activate or deactivate a product.",
     *   operationId="changeProductStatus",
     *   tags={"Products"},
     *   security={{"bearerAuth":{}}},
     *
     *   @OA\Parameter(name="id", in="path", required=true, description="Product ID", @OA\Schema(type="integer")),
     *
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *           required={"active"},
     *           @OA\Property(property="active", type="boolean", example=true)
     *       )
     *   ),
     *
     *   @OA\Response(response=200, description="Product status updated"),
     *   @OA\Response(response=403, description="Forbidden"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function changeStatus(Request $request, Product $product): JsonResponse
    {
        $this->authorize("update", $product);

        $validated = $request->validate([
            "active" => "required|boolean",
        ]);

        $product->update(["active" => $validated["active"]]);

        return response()->json([
            "status" => "success",
            "message" => "Product status updated",
            "data" => ["active" => $product->active],
        ]);
    }
}
