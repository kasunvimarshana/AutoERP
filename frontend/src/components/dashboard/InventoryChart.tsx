import React from 'react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Cell,
} from 'recharts';
import type { Product } from '@/types';

interface InventoryChartProps {
  products: Product[];
  maxItems?: number;
}

const stockColor = (available: number, reorder: number): string => {
  if (available === 0) return '#ef4444';
  if (available <= reorder) return '#f59e0b';
  return '#10b981';
};

const InventoryChart: React.FC<InventoryChartProps> = ({ products, maxItems = 10 }) => {
  const chartData = products
    .slice(0, maxItems)
    .map((p) => ({
      name: p.name.length > 14 ? p.name.slice(0, 12) + '…' : p.name,
      available: p.available_quantity,
      reserved: p.reserved_quantity,
      reorder: p.reorder_point,
      fill: stockColor(p.available_quantity, p.reorder_point),
    }));

  return (
    <div className="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-base font-semibold text-gray-800">Stock Levels</h3>
        <div className="flex items-center gap-3 text-xs text-gray-500">
          <span className="flex items-center gap-1">
            <span className="w-3 h-3 rounded-sm bg-emerald-500 inline-block" />
            OK
          </span>
          <span className="flex items-center gap-1">
            <span className="w-3 h-3 rounded-sm bg-amber-400 inline-block" />
            Low
          </span>
          <span className="flex items-center gap-1">
            <span className="w-3 h-3 rounded-sm bg-red-500 inline-block" />
            Out
          </span>
        </div>
      </div>
      {chartData.length === 0 ? (
        <div className="h-48 flex items-center justify-center text-gray-400 text-sm">
          No product data available
        </div>
      ) : (
        <ResponsiveContainer width="100%" height={240}>
          <BarChart data={chartData} margin={{ top: 5, right: 5, left: -10, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
            <XAxis
              dataKey="name"
              tick={{ fontSize: 11, fill: '#6b7280' }}
              axisLine={false}
              tickLine={false}
            />
            <YAxis
              tick={{ fontSize: 11, fill: '#6b7280' }}
              axisLine={false}
              tickLine={false}
            />
            <Tooltip
              contentStyle={{
                borderRadius: '10px',
                border: '1px solid #e5e7eb',
                fontSize: 12,
              }}
              formatter={(value, name) => [value, name === 'available' ? 'Available' : 'Reserved']}
            />
            <Bar dataKey="available" radius={[4, 4, 0, 0]}>
              {chartData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={entry.fill} />
              ))}
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      )}
    </div>
  );
};

export default InventoryChart;
