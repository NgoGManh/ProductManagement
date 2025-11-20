import React, { useEffect, useState } from 'react';

import { FileDown, FileSpreadsheet } from 'lucide-react';
import { toast } from 'sonner';

import { AdminGuard } from '@/components/AdminGuard';
import { Layout } from '@/components/Layout';
import { ProductTable } from '@/components/products/ProductTable';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import api from '@/lib/api';
import type { Product, ProductsResponse } from '@/types/product';

export default function Dashboard() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [exporting, setExporting] = useState<{ pdf: boolean; excel: boolean }>({
    pdf: false,
    excel: false,
  });
  const [exportLinks, setExportLinks] = useState<{ pdf?: string; excel?: string }>({});
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0,
  });

  const fetchProducts = async (page: number = 1, perPage: number = 10) => {
    setLoading(true);
    setError(null);

    try {
      const response = await api.get<ProductsResponse>('/products', {
        params: {
          per_page: perPage,
          page,
        },
      });

      if (response.data.status === 'success') {
        setProducts(response.data.data.data);
        setPagination({
          current_page: response.data.data.current_page,
          last_page: response.data.data.last_page,
          per_page: response.data.data.per_page,
          total: response.data.data.total,
          from: response.data.data.from ?? 0,
          to: response.data.data.to ?? 0,
        });
      } else {
        setError('Failed to load products');
      }
    } catch (err: any) {
      setError('Error loading products: ' + (err.response?.data?.message || err.message));
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProducts(pagination.current_page, pagination.per_page);
  }, []);

  const handlePageChange = (page: number) => {
    fetchProducts(page, pagination.per_page);
  };

  const handlePerPageChange = (perPage: number) => {
    fetchProducts(1, perPage);
  };

  const handleRefresh = () => {
    fetchProducts(pagination.current_page, pagination.per_page);
  };

  const handleExport = async (type: 'pdf' | 'excel') => {
    setExporting((prev) => ({ ...prev, [type]: true }));

    try {
      const endpoint = type === 'pdf' ? '/products/export/pdf' : '/products/export/excel';
      const response = await api.get(endpoint);
      const { url, message } = response.data;

      if (!url) {
        throw new Error('Missing export URL');
      }

      setExportLinks((prev) => ({ ...prev, [type]: url }));
      toast.success(message ?? 'Export completed');

      // For PDF, open in new tab
      if (type === 'pdf') {
        window.open(url, '_blank', 'noopener');
      } else {
        // For Excel, force download
        const link = document.createElement('a');
        link.href = url;
        link.download = url.split('/').pop() || 'products.xlsx';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }
    } catch (err: any) {
      const message = err.response?.data?.message ?? 'Export failed. Please try again.';
      toast.error(message);
    } finally {
      setExporting((prev) => ({ ...prev, [type]: false }));
    }
  };

  return (
    <AdminGuard>
      <Layout>
        <div className="space-y-6">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h1 className="text-3xl font-bold tracking-tight">Products</h1>
              <p className="text-muted-foreground">Manage your products</p>
            </div>
            <div className="flex flex-wrap gap-3">
              <Button
                variant="outline"
                onClick={() => handleExport('pdf')}
                disabled={exporting.pdf}
              >
                <FileDown className="mr-2 h-4 w-4" />
                Export PDF
              </Button>
              <Button
                variant="outline"
                onClick={() => handleExport('excel')}
                disabled={exporting.excel}
              >
                <FileSpreadsheet className="mr-2 h-4 w-4" />
                Export Excel
              </Button>
            </div>
          </div>

          {error && (
            <Card>
              <CardContent className="pt-6">
                <div className="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-md text-sm">
                  {error}
                </div>
              </CardContent>
            </Card>
          )}

          {(exportLinks.pdf || exportLinks.excel) && (
            <Card>
              <CardContent className="pt-6">
                <div className="flex flex-col gap-2">
                  <h3 className="text-sm font-semibold">Latest exports</h3>
                  <div className="flex flex-wrap gap-4 text-sm">
                    {exportLinks.pdf && (
                      <a
                        href={exportLinks.pdf}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-primary underline"
                      >
                        Download PDF
                      </a>
                    )}
                    {exportLinks.excel && (
                      <a
                        href={exportLinks.excel}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-primary underline"
                      >
                        Download Excel
                      </a>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>
          )}

          <ProductTable
            products={products}
            loading={loading}
            pagination={pagination}
            onPageChange={handlePageChange}
            onPerPageChange={handlePerPageChange}
            onRefresh={handleRefresh}
          />
        </div>
      </Layout>
    </AdminGuard>
  );
}
