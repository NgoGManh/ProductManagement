import React, { useEffect, useState } from 'react';

import { AdminGuard } from '@/components/AdminGuard';
import { Layout } from '@/components/Layout';
import { ProductTable } from '@/components/products/ProductTable';
import { Card, CardContent } from '@/components/ui/card';
import api from '@/lib/api';
import type { Product, ProductsResponse } from '@/types/product';

export default function Dashboard() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
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

  return (
    <AdminGuard>
      <Layout>
        <div className="space-y-6">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Products</h1>
            <p className="text-muted-foreground">Manage your products</p>
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
