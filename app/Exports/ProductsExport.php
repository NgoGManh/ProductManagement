<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * @var \Illuminate\Support\Collection<int, Product>
     */
    protected Collection $products;

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    public function __construct(Collection $products)
    {
        $this->products = $products;
    }

    public function collection(): Collection
    {
        return $this->products->map(function (Product $product) {
            return [
                "ID" => $product->id,
                "Name" => $product->name,
                "Slug" => $product->slug,
                "Price" => $product->price,
                "Stock" => $product->stock,
                "Status" => $product->status_label,
                "Active" => $product->active ? "Yes" : "No",
                "Created At" => optional($product->created_at)->timezone(config("app.timezone"))->toDateTimeString(),
            ];
        });
    }

    public function headings(): array
    {
        return ["ID", "Name", "Slug", "Price", "Stock", "Status", "Active", "Created At"];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ["font" => ["bold" => true]],
        ];
    }
}
