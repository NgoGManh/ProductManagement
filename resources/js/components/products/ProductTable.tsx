import React, { useEffect, useMemo, useState } from 'react';

import {
  type ColumnDef,
  type ColumnFiltersState,
  type SortingState,
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { ChevronDown, ChevronUp, Eye, Pencil, Plus, Trash2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useAuthStore } from '@/stores/authStore';
import type { Product } from '@/types/product';

import { ProductDeleteDialog } from './ProductDeleteDialog';
import { ProductDetailDialog } from './ProductDetailDialog';
import { ProductEditDialog } from './ProductEditDialog';

interface ProductTableProps {
  products: Product[];
  loading?: boolean;
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
  onPageChange?: (page: number) => void;
  onPerPageChange?: (perPage: number) => void;
  onRefresh?: () => void;
}

export function ProductTable({
  products,
  loading = false,
  pagination,
  onPageChange,
  onPerPageChange,
  onRefresh,
}: ProductTableProps) {
  const { user } = useAuthStore();
  const isAdmin = user?.roles?.some((role) => role.name === 'admin') ?? false;

  // Dialog states
  const [showDetail, setShowDetail] = useState(false);
  const [showEdit, setShowEdit] = useState(false);
  const [showDelete, setShowDelete] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
  const [isCreating, setIsCreating] = useState(false);

  // Table states
  const [sorting, setSorting] = useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [globalFilter, setGlobalFilter] = useState('');

  // Handle product actions
  const handleViewProduct = (product: Product) => {
    setSelectedProduct(product);
    setShowDetail(true);
  };

  const handleEditProduct = (product: Product) => {
    setSelectedProduct(product);
    setIsCreating(false);
    setShowEdit(true);
  };

  const handleDeleteProduct = (product: Product) => {
    setSelectedProduct(product);
    setShowDelete(true);
  };

  const handleCreateProduct = () => {
    setSelectedProduct(null);
    setIsCreating(true);
    setShowEdit(true);
  };

  const handleCloseDetail = () => {
    setShowDetail(false);
    setSelectedProduct(null);
  };

  const handleCloseEdit = () => {
    setShowEdit(false);
    setSelectedProduct(null);
    setIsCreating(false);
  };

  const handleCloseDelete = () => {
    setShowDelete(false);
    setSelectedProduct(null);
  };

  const handleEditSuccess = () => {
    handleCloseEdit();
    onRefresh?.();
  };

  const handleDeleteSuccess = () => {
    handleCloseDelete();
    onRefresh?.();
  };

  // Define columns
  const columns = useMemo<ColumnDef<Product>[]>(
    () => [
      {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => (
          <div className="font-medium max-w-[200px] truncate">{row.original.name}</div>
        ),
      },
      {
        accessorKey: 'description',
        header: 'Description',
        cell: ({ row }) => (
          <div className="max-w-[300px] truncate text-muted-foreground">
            {row.original.description || '-'}
          </div>
        ),
      },
      {
        accessorKey: 'price',
        header: 'Price',
        cell: ({ row }) => <div className="font-semibold">${row.original.price}</div>,
      },
      {
        accessorKey: 'stock',
        header: 'Stock',
        cell: ({ row }) => (
          <div className={row.original.in_stock ? 'text-green-600' : 'text-red-600'}>
            {row.original.stock}
          </div>
        ),
      },
      {
        accessorKey: 'active',
        header: 'Status',
        cell: ({ row }) => (
          <Badge
            variant={row.original.active ? 'default' : 'secondary'}
            className={
              row.original.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
            }
          >
            {row.original.status_label}
          </Badge>
        ),
      },
      {
        id: 'actions',
        header: () => <div className="text-right">Actions</div>,
        cell: ({ row }) => {
          const product = row.original;
          return (
            <div className="flex justify-end gap-2">
              <Button
                variant="ghost"
                size="icon"
                onClick={(e) => {
                  e.stopPropagation();
                  handleViewProduct(product);
                }}
              >
                <Eye className="h-4 w-4" />
              </Button>
              {isAdmin && (
                <>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleEditProduct(product);
                    }}
                  >
                    <Pencil className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleDeleteProduct(product);
                    }}
                  >
                    <Trash2 className="h-4 w-4 text-destructive" />
                  </Button>
                </>
              )}
            </div>
          );
        },
      },
    ],
    [isAdmin]
  );

  // Table instance
  const table = useReactTable({
    data: products,
    columns,
    state: {
      sorting,
      columnFilters,
      globalFilter,
    },
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onGlobalFilterChange: setGlobalFilter,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: {
      pagination: {
        pageSize: pagination?.per_page ?? 10,
      },
    },
    manualPagination: !!pagination,
    manualSorting: false,
    manualFiltering: false,
  });

  // Update page size when pagination changes
  useEffect(() => {
    if (pagination?.per_page) {
      table.setPageSize(pagination.per_page);
    }
  }, [pagination?.per_page, table]);

  return (
    <div className="space-y-4">
      {/* Header with search and create button */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4 flex-1 max-w-sm">
          <Input
            placeholder="Search products..."
            value={globalFilter ?? ''}
            onChange={(e) => setGlobalFilter(e.target.value)}
            className="max-w-sm"
          />
        </div>
        {isAdmin && (
          <Button onClick={handleCreateProduct}>
            <Plus className="h-4 w-4 mr-2" />
            Add Product
          </Button>
        )}
      </div>

      {/* Table */}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => (
                  <TableHead key={header.id}>
                    {header.isPlaceholder ? null : (
                      <div
                        className={
                          header.column.getCanSort()
                            ? 'cursor-pointer select-none flex items-center gap-2'
                            : 'flex items-center gap-2'
                        }
                        onClick={header.column.getToggleSortingHandler()}
                      >
                        {flexRender(header.column.columnDef.header, header.getContext())}
                        {header.column.getCanSort() && (
                          <span className="flex flex-col">
                            {header.column.getIsSorted() === 'asc' ? (
                              <ChevronUp className="h-3 w-3" />
                            ) : header.column.getIsSorted() === 'desc' ? (
                              <ChevronDown className="h-3 w-3" />
                            ) : (
                              <ChevronDown className="h-3 w-3 opacity-30" />
                            )}
                          </span>
                        )}
                      </div>
                    )}
                  </TableHead>
                ))}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow>
                <TableCell colSpan={columns.length} className="h-24 text-center">
                  Loading...
                </TableCell>
              </TableRow>
            ) : table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && 'selected'}
                  className="cursor-pointer"
                  onClick={() => handleViewProduct(row.original)}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell colSpan={columns.length} className="h-24 text-center">
                  No products found.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      {/* Pagination */}
      {pagination && (
        <div className="flex items-center justify-between">
          <div className="text-sm text-muted-foreground">
            Showing {pagination.from} to {pagination.to} of {pagination.total} products
          </div>
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <span className="text-sm">Rows per page:</span>
              <Select
                value={pagination.per_page.toString()}
                onValueChange={(value) => onPerPageChange?.(Number(value))}
              >
                <SelectTrigger className="w-[70px]">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="10">10</SelectItem>
                  <SelectItem value="20">20</SelectItem>
                  <SelectItem value="50">50</SelectItem>
                  <SelectItem value="100">100</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => onPageChange?.(pagination.current_page - 1)}
                disabled={pagination.current_page === 1}
              >
                Previous
              </Button>
              <div className="text-sm">
                Page {pagination.current_page} of {pagination.last_page}
              </div>
              <Button
                variant="outline"
                size="sm"
                onClick={() => onPageChange?.(pagination.current_page + 1)}
                disabled={pagination.current_page >= pagination.last_page}
              >
                Next
              </Button>
            </div>
          </div>
        </div>
      )}

      {/* Dialogs */}
      {selectedProduct && (
        <>
          <ProductDetailDialog
            product={selectedProduct}
            open={showDetail}
            onOpenChange={setShowDetail}
          />
          {isAdmin && (
            <ProductDeleteDialog
              product={selectedProduct}
              open={showDelete}
              onOpenChange={setShowDelete}
              onSuccess={handleDeleteSuccess}
            />
          )}
        </>
      )}

      {isAdmin && (
        <ProductEditDialog
          product={isCreating ? null : selectedProduct}
          open={showEdit}
          onOpenChange={setShowEdit}
          onSuccess={handleEditSuccess}
        />
      )}
    </div>
  );
}
