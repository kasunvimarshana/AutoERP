'use strict';

const mongoose = require('mongoose');

/**
 * Inventory Schema (MongoDB)
 *
 * Stores inventory records for products managed by the Product Service.
 * product_id and product_name are denormalized copies from the Product Service,
 * kept in sync via RabbitMQ events (product.updated for renames,
 * product.deleted for cascade deletes).
 *
 * MongoDB allows flexible schema per warehouse location,
 * demonstrating the multi-database microservices pattern.
 */
const inventorySchema = new mongoose.Schema(
  {
    product_id: {
      type: Number,
      required: true,
      index: true,
    },
    product_name: {
      type: String,
      required: true,
      trim: true,
      index: true,
    },
    product_sku: {
      type: String,
      trim: true,
      index: true,
    },
    quantity: {
      type: Number,
      required: true,
      min: 0,
      default: 0,
    },
    reserved_quantity: {
      type: Number,
      min: 0,
      default: 0,
      comment: 'Quantity reserved for pending orders',
    },
    warehouse_location: {
      type: String,
      trim: true,
      default: 'Main Warehouse',
    },
    reorder_threshold: {
      type: Number,
      min: 0,
      default: 10,
      comment: 'Alert when quantity falls below this value',
    },
    notes: {
      type: String,
      trim: true,
    },
    is_active: {
      type: Boolean,
      default: true,
      index: true,
    },
  },
  {
    timestamps: true,
    versionKey: false,
    toJSON: {
      virtuals: true,
      transform: (doc, ret) => {
        ret.id = ret._id;
        delete ret._id;
        return ret;
      },
    },
  }
);

/**
 * Virtual: available_quantity
 * Calculates the quantity available for new orders (total minus reserved).
 */
inventorySchema.virtual('available_quantity').get(function () {
  return Math.max(0, this.quantity - this.reserved_quantity);
});

/**
 * Virtual: needs_reorder
 * Returns true if available quantity is below the reorder threshold.
 */
inventorySchema.virtual('needs_reorder').get(function () {
  return this.available_quantity <= this.reorder_threshold;
});

/**
 * Index for efficient cross-service queries:
 * - Find all inventory by product_id (used on product.deleted event)
 * - Find all inventory by product_name (used for filtering + product.updated rename)
 */
inventorySchema.index({ product_id: 1, warehouse_location: 1 }, { unique: true });

const Inventory = mongoose.model('Inventory', inventorySchema);

module.exports = Inventory;
