'use strict';

const Joi = require('joi');

/**
 * Returns an Express middleware that validates `req.body` against `schema`.
 * Responds 422 with detailed error messages on validation failure.
 *
 * @param {Joi.Schema} schema
 * @returns {import('express').RequestHandler}
 */
function validateBody(schema) {
  return (req, res, next) => {
    const { error, value } = schema.validate(req.body, {
      abortEarly: false,
      stripUnknown: true,
    });

    if (error) {
      return res.status(422).json({
        error: 'Validation failed',
        details: error.details.map((d) => ({
          field: d.path.join('.'),
          message: d.message,
        })),
      });
    }

    req.body = value;
    next();
  };
}

/**
 * Returns an Express middleware that validates `req.query` against `schema`.
 *
 * @param {Joi.Schema} schema
 * @returns {import('express').RequestHandler}
 */
function validateQuery(schema) {
  return (req, res, next) => {
    const { error, value } = schema.validate(req.query, {
      abortEarly: false,
      stripUnknown: true,
    });

    if (error) {
      return res.status(422).json({
        error: 'Validation failed',
        details: error.details.map((d) => ({
          field: d.path.join('.'),
          message: d.message,
        })),
      });
    }

    req.query = value;
    next();
  };
}

// ── Reusable schemas ──────────────────────────────────────────────────────────

const paymentMethodSchema = Joi.object({
  type: Joi.string().valid('card', 'bank_transfer', 'wallet').required(),
  last4: Joi.string().length(4).pattern(/^\d+$/).optional(),
  brand: Joi.string().max(32).optional(),
});

const createPaymentSchema = Joi.object({
  sagaId: Joi.string().uuid().required(),
  orderId: Joi.string().max(64).required(),
  amount: Joi.number().positive().precision(2).required(),
  currency: Joi.string().length(3).uppercase().default('USD'),
  paymentMethod: paymentMethodSchema.required(),
  metadata: Joi.object().optional(),
});

const listPaymentsSchema = Joi.object({
  page: Joi.number().integer().min(1).default(1),
  limit: Joi.number().integer().min(1).max(100).default(20),
  status: Joi.string()
    .valid('pending', 'processing', 'completed', 'failed', 'refunded')
    .optional(),
});

module.exports = {
  validateBody,
  validateQuery,
  createPaymentSchema,
  listPaymentsSchema,
};
