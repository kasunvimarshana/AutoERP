'use strict';
const { body, param, validationResult } = require('express-validator');
const validateSagaRequest = [
  body('saga_id').isUUID().withMessage('saga_id must be a valid UUID'),
  body('tenant_id').optional().isString(),
  body('customer_id').notEmpty().withMessage('customer_id is required'),
  body('amount').isFloat({ min: 0.01 }).withMessage('amount must be a positive number'),
  body('currency').optional().isLength({ min: 3, max: 3 }).withMessage('currency must be 3 characters'),
  body('payment_method').notEmpty().withMessage('payment_method is required'),
  handleValidationErrors,
];
const validateRefund = [
  param('paymentId').isUUID().withMessage('paymentId must be a valid UUID'),
  body('saga_id').optional().isUUID(),
  body('reason').optional().isString(),
  handleValidationErrors,
];
const validateProcessPayment = [
  body('tenant_id').notEmpty().withMessage('tenant_id is required'),
  body('customer_id').notEmpty().withMessage('customer_id is required'),
  body('amount').isFloat({ min: 0.01 }).withMessage('amount must be positive'),
  body('payment_method').notEmpty().withMessage('payment_method is required'),
  handleValidationErrors,
];
function handleValidationErrors(req, res, next) {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(422).json({ success: false, message: 'Validation failed.', errors: errors.array().reduce((acc, e) => { acc[e.path] = e.msg; return acc; }, {}) });
  }
  next();
}
module.exports = { validateProcessPayment, validateRefund, validateSagaRequest };
