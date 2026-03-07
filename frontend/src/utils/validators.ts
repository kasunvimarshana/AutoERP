import { z } from 'zod';

export const loginSchema = z.object({
  email: z.string().email('Invalid email address'),
  password: z.string().min(6, 'Password must be at least 6 characters'),
  tenant_id: z.number().int().positive('Tenant ID must be a positive number'),
});

export const registerSchema = z
  .object({
    name: z.string().min(2, 'Name must be at least 2 characters'),
    email: z.string().email('Invalid email address'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    password_confirmation: z.string(),
    tenant_name: z.string().min(2, 'Tenant name must be at least 2 characters').optional(),
    tenant_slug: z.string().min(2, 'Tenant slug must be at least 2 characters').optional(),
  })
  .refine((d) => d.password === d.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  });

export const userSchema = z.object({
  name: z.string().min(2, 'Name is required'),
  email: z.string().email('Invalid email'),
  role: z.string().min(1, 'Role is required'),
  password: z.string().min(8, 'Password must be at least 8 characters').optional(),
});

export const productSchema = z.object({
  name: z.string().min(1, 'Product name is required'),
  sku: z.string().min(1, 'SKU is required'),
  description: z.string().optional(),
  price: z.number().positive('Price must be positive'),
  category_id: z.number().int().positive('Category is required'),
  status: z.enum(['active', 'inactive']),
});

export const inventoryAdjustSchema = z.object({
  type: z.enum(['in', 'out', 'adjustment']),
  quantity: z.number().int().positive('Quantity must be a positive integer'),
  reference: z.string().optional(),
  notes: z.string().optional(),
});

export const orderSchema = z.object({
  customer_name: z.string().min(1, 'Customer name is required'),
  customer_email: z.string().email('Invalid customer email'),
  items: z
    .array(
      z.object({
        product_id: z.number().int().positive('Product is required'),
        quantity: z.number().int().positive('Quantity must be at least 1'),
        unit_price: z.number().positive('Price must be positive'),
      })
    )
    .min(1, 'At least one item is required'),
});

export const webhookSchema = z.object({
  name: z.string().min(1, 'Webhook name is required'),
  url: z.string().url('Must be a valid URL'),
  events: z.array(z.string()).min(1, 'At least one event is required'),
  is_active: z.boolean(),
});
