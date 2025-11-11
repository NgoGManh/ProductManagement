<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Controllers\Api\V1\Product\ProductController;
use App\Http\Controllers\Api\V1\Product\ProductImageController;

Route::prefix("v1")
    ->name("api.v1.")
    ->group(function () {
        // Auth routes
        Route::prefix("auth")
            ->name("auth.")
            ->group(function () {
                Route::post("register", [AuthController::class, "register"])->name("register");
                Route::post("login", [AuthController::class, "login"])->name("login");

                Route::middleware("auth:api")->group(function () {
                    Route::get("me", [AuthController::class, "me"])->name("me");
                    Route::post("logout", [AuthController::class, "logout"])->name("logout");
                    Route::post("refresh", [AuthController::class, "refresh"])->name("refresh");
                    Route::put("profile", [AuthController::class, "updateProfile"])->name("profile.update");
                    Route::put("change-password", [AuthController::class, "changePassword"])->name("change-password");
                });
            });

        // User management routes
        Route::middleware(["auth:api", "role:admin"])
            ->prefix("users")
            ->name("users.")
            ->group(function () {
                Route::apiResource("/", UserController::class)->parameters(["" => "user"]);

                Route::post("{user}/status", [UserController::class, "changeStatus"])->name("change-status");
                Route::post("restore/{id}", [UserController::class, "restore"])->name("restore");
            });

        // Product image proxy route (public, no auth required)
        Route::match(["GET", "OPTIONS"], "products/images/{path}", [ProductImageController::class, "show"])
            ->where("path", ".+")
            ->name("products.images.show");

        // Product management routes
        // All authenticated users can view products
        Route::middleware(["auth:api"])->group(function () {
            Route::apiResource("products", ProductController::class)
                ->only(["index", "show"])
                ->names([
                    "index" => "products.index",
                    "show" => "products.show",
                ]);
        });

        // Admin-only routes for product management
        Route::middleware(["auth:api", "role:admin"])->group(function () {
            // Admin-only routes
            Route::apiResource("products", ProductController::class)
                ->only(["store", "update", "destroy"])
                ->names([
                    "store" => "products.store",
                    "update" => "products.update",
                    "destroy" => "products.destroy",
                ]);

            // Custom actions
            Route::post("products/{product}/status", [ProductController::class, "changeStatus"])->name(
                "products.change-status",
            );
            Route::post("products/{id}/restore", [ProductController::class, "restore"])->name("products.restore");
        });
    });
