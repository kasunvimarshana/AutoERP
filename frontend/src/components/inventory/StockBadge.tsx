import React from 'react';
import clsx from 'clsx';
import type { Product, StockStatus } from '@/types';
import { getStockStatus } from '@/types/product';

interface StockBadgeProps {
  product: Product;
  showQuantity?: boolean;
}

const stockConfig: Record<StockStatus, { label: string; classes: string; dotClass: string }> = {
  in_stock: {
    label: 'In Stock',
    classes: 'bg-green-100 text-green-700 border-green-200',
    dotClass: 'bg-green-500',
  },
  low_stock: {
    label: 'Low Stock',
    classes: 'bg-yellow-100 text-yellow-700 border-yellow-200',
    dotClass: 'bg-yellow-500',
  },
  out_of_stock: {
    label: 'Out of Stock',
    classes: 'bg-red-100 text-red-700 border-red-200',
    dotClass: 'bg-red-500',
  },
};

const StockBadge: React.FC<StockBadgeProps> = ({ product, showQuantity = true }) => {
  const status = getStockStatus(product);
  const config = stockConfig[status];

  return (
    <span
      className={clsx(
        'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border',
        config.classes,
      )}
    >
      <span className={clsx('w-1.5 h-1.5 rounded-full', config.dotClass)} />
      {config.label}
      {showQuantity && (
        <span className="font-normal opacity-80">({product.available_quantity})</span>
      )}
    </span>
  );
};

export default StockBadge;
