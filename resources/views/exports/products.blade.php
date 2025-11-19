<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Products Report</title>
        <style>
            body {
                font-family: "DejaVu Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
                font-size: 12px;
                color: #111827;
            }
            .heading {
                margin-bottom: 10px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th,
            td {
                border: 1px solid #d1d5db;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f3f4f6;
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: 0.04em;
            }
            .text-muted {
                color: #6b7280;
                font-size: 11px;
            }
        </style>
    </head>
    <body>
        @php use Illuminate\Support\Str; @endphp
        <h1 class="heading">Products Report</h1>
        <p class="text-muted">Generated at {{ $generatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Active</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $index => $product)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->slug }}</td>
                    <td>{{ Str::limit($product->description, 60) }}</td>
                    <td>${{ number_format($product->price, 2) }}</td>
                    <td>{{ $product->stock }}</td>
                    <td>{{ $product->status_label }}</td>
                    <td>{{ $product->active ? 'Yes' : 'No' }}</td>
                    <td>{{ optional($product->created_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
