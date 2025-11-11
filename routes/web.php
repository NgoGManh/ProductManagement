<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get("/login", function () {
    return Inertia::render("Login");
})->name("login");

Route::get("/unauthorized", function () {
    return Inertia::render("Unauthorized");
})->name("unauthorized");

Route::get("/", function () {
    return Inertia::render("Dashboard");
})->name("dashboard");

Route::get("/{any}", function () {
    return Inertia::render("Dashboard");
})->where("any", ".*");
