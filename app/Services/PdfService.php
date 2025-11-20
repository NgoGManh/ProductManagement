<?php

namespace App\Services;

use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    /**
     * Render a Blade template into a PDF and store it on the public disk.
     *
     * @param  string  $view
     * @param  array<string, mixed>  $data
     * @param  string  $path
     * @param  array<string, mixed>  $options
     * @return array{path: string, url: string}
     */
    public function generate(string $view, array $data, string $path, array $options = []): array
    {
        $html = view($view, $data)->render();

        $pdf = SnappyPdf::loadHTML($html);
        $pdf->setPaper($options["paper"] ?? "a4");
        $pdf->setOrientation($options["orientation"] ?? "portrait");
        $pdf->setOption("encoding", "UTF-8");

        if (!empty($options["title"])) {
            $pdf->setOption("title", $options["title"]);
        }

        if (!empty($options["options"]) && is_array($options["options"])) {
            foreach ($options["options"] as $key => $value) {
                $pdf->setOption($key, $value);
            }
        }

        $disk = Storage::disk("public");
        $directory = dirname($path);

        if ($directory !== ".") {
            $disk->makeDirectory($directory);
        }

        $pdfContent = $pdf->output();

        if (empty($pdfContent)) {
            throw new \RuntimeException("PDF generation failed: empty output");
        }

        $disk->put($path, $pdfContent);

        // Verify file was created
        if (!$disk->exists($path)) {
            throw new \RuntimeException("PDF file was not created at path: {$path}");
        }

        return [
            "path" => $path,
            "url" => $disk->url($path),
        ];
    }
}
