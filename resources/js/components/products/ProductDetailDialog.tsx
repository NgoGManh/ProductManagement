import React from 'react';

import { X } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import type { Product } from '@/types/product';

interface ProductDetailDialogProps {
  product: Product;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function ProductDetailDialog({ product, open, onOpenChange }: ProductDetailDialogProps) {
  if (!product) return null;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{product.name}</DialogTitle>
          <DialogDescription>Product Details</DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Images */}
          {product.image_urls && product.image_urls.length > 0 && (
            <div>
              <h3 className="text-sm font-semibold mb-2">Images</h3>
              <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                {product.image_urls.map((url, index) => (
                  <div key={index} className="aspect-video rounded-md overflow-hidden border">
                    <img
                      src={url}
                      alt={`${product.name} - Image ${index + 1}`}
                      className="w-full h-full object-cover"
                    />
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Basic Info */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <h3 className="text-sm font-semibold mb-1">Price</h3>
              <p className="text-lg font-bold text-primary">${product.price}</p>
            </div>
            <div>
              <h3 className="text-sm font-semibold mb-1">Stock</h3>
              <p
                className={`text-lg font-semibold ${
                  product.in_stock ? 'text-green-600' : 'text-red-600'
                }`}
              >
                {product.stock} {product.in_stock ? '(In Stock)' : '(Out of Stock)'}
              </p>
            </div>
            <div>
              <h3 className="text-sm font-semibold mb-1">Status</h3>
              <Badge
                variant={product.active ? 'default' : 'secondary'}
                className={
                  product.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                }
              >
                {product.status_label}
              </Badge>
            </div>
            <div>
              <h3 className="text-sm font-semibold mb-1">Slug</h3>
              <p className="text-sm text-muted-foreground">{product.slug}</p>
            </div>
          </div>

          {/* Description */}
          <div>
            <h3 className="text-sm font-semibold mb-2">Description</h3>
            <p className="text-sm text-muted-foreground whitespace-pre-wrap">
              {product.description || 'No description provided'}
            </p>
          </div>

          {/* Metadata */}
          <div className="grid grid-cols-2 gap-4 pt-4 border-t">
            <div>
              <h3 className="text-sm font-semibold mb-1">Created At</h3>
              <p className="text-sm text-muted-foreground">
                {new Date(product.created_at).toLocaleString()}
              </p>
            </div>
            <div>
              <h3 className="text-sm font-semibold mb-1">Updated At</h3>
              <p className="text-sm text-muted-foreground">
                {new Date(product.updated_at).toLocaleString()}
              </p>
            </div>
            {product.created_by && (
              <div>
                <h3 className="text-sm font-semibold mb-1">Created By</h3>
                <p className="text-sm text-muted-foreground">
                  {product.created_by.first_name} {product.created_by.last_name}
                </p>
              </div>
            )}
            {product.updated_by && (
              <div>
                <h3 className="text-sm font-semibold mb-1">Updated By</h3>
                <p className="text-sm text-muted-foreground">
                  {product.updated_by.first_name} {product.updated_by.last_name}
                </p>
              </div>
            )}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
