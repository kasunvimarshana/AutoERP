import * as yup from 'yup';

export const productSchema = yup.object({
  name:       yup.string().required('Name is required').min(2).max(200),
  sku:        yup.string().required('SKU is required').max(100),
  price:      yup.number().required('Price is required').min(0),
  category:   yup.string().required('Category is required'),
  description: yup.string().max(1000),
});

export const orderSchema = yup.object({
  customer_name:  yup.string().required('Customer name is required'),
  customer_email: yup.string().email('Invalid email').required('Email is required'),
  items:          yup.array().of(
    yup.object({
      product_id: yup.string().required('Product is required'),
      quantity:   yup.number().required('Quantity is required').min(1),
    })
  ).min(1, 'At least one item is required'),
});

export const stockAdjustSchema = yup.object({
  quantity: yup.number().required('Quantity is required').integer().not([0], 'Cannot be zero'),
  type:     yup.string().oneOf(['receipt', 'adjustment', 'sale', 'return']).required(),
  notes:    yup.string().max(500),
});

export const profileSchema = yup.object({
  first_name: yup.string().required('First name is required'),
  last_name:  yup.string().required('Last name is required'),
  email:      yup.string().email('Invalid email').required('Email is required'),
});
