export interface Product {
  id: number;
  name: string;
  slug: string;
  description: string;
  price: string;
  stock: number;
  active: boolean;
  images: string[];
  image_urls: string[];
  status_label: string;
  in_stock: boolean;
  created_at: string;
  updated_at: string;
  created_by?: {
    id: number;
    first_name: string;
    last_name: string;
  };
  updated_by?: {
    id: number;
    first_name: string;
    last_name: string;
  };
}

export interface ProductsResponse {
  status: string;
  data: {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
}

export interface ProductResponse {
  status: string;
  data: Product;
}

export interface CreateProductData {
  name: string;
  description?: string;
  price: number;
  stock: number;
  active: boolean;
  images?: File[];
}

export interface UpdateProductData {
  name?: string;
  description?: string;
  price?: number;
  stock?: number;
  active?: boolean;
  images?: File[];
}
