<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImageController extends Controller
{
    /**
     * Handle OPTIONS request for CORS preflight
     *
     * @return Response
     */
    public function options(): Response
    {
        return response("", 200)
            ->header("Access-Control-Allow-Origin", "*")
            ->header("Access-Control-Allow-Methods", "GET, OPTIONS")
            ->header("Access-Control-Allow-Headers", "Content-Type");
    }

    /**
     * Proxy image from R2 storage with CORS headers
     *
     * @param Request $request
     * @param string $path
     * @return Response|StreamedResponse
     */
    public function show(Request $request, string $path): Response|StreamedResponse
    {
        $disk = Storage::disk("r2");

        // Check if file exists
        if (!$disk->exists($path)) {
            abort(404, "Image not found");
        }

        // Get file content
        try {
            $fileContent = $disk->get($path);
            $mimeType = $disk->mimeType($path) ?? "image/jpeg";
        } catch (\Exception $e) {
            abort(404, "Image not found");
        }

        // Return response with CORS headers
        return response($fileContent, 200)
            ->header("Content-Type", $mimeType)
            ->header("Access-Control-Allow-Origin", "*")
            ->header("Access-Control-Allow-Methods", "GET, OPTIONS")
            ->header("Access-Control-Allow-Headers", "Content-Type")
            ->header("Cache-Control", "public, max-age=31536000, immutable");
    }
}
