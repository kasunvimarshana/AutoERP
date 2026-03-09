'use strict';
const { DataTypes } = require('sequelize');
const { sequelize } = require('../infrastructure/database');
const Payment = sequelize.define('Payment', {
  id: { type: DataTypes.UUID, primaryKey: true, defaultValue: DataTypes.UUIDV4 },
  tenant_id: { type: DataTypes.STRING(36), allowNull: false },
  customer_id: { type: DataTypes.STRING(36), allowNull: false },
  saga_id: { type: DataTypes.STRING(36), allowNull: true },
  amount: { type: DataTypes.DECIMAL(12, 4), allowNull: false },
  currency: { type: DataTypes.STRING(3), defaultValue: 'USD' },
  payment_method: { type: DataTypes.STRING(100), allowNull: false },
  status: { type: DataTypes.ENUM('pending', 'completed', 'failed', 'refunded'), defaultValue: 'pending' },
  gateway_transaction_id: { type: DataTypes.STRING(255), allowNull: true },
  gateway_response: { type: DataTypes.TEXT, allowNull: true },
  refund_reason: { type: DataTypes.TEXT, allowNull: true },
  refunded_at: { type: DataTypes.DATE, allowNull: true },
}, {
  tableName: 'payments', underscored: true, timestamps: true,
  indexes: [{ fields: ['tenant_id'] }, { fields: ['saga_id'] }, { fields: ['status'] }, { fields: ['customer_id'] }],
});
module.exports = Payment;
