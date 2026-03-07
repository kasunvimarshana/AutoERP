'use strict';

const express   = require('express');
const rateLimit = require('express-rate-limit');
const router    = express.Router();

const {
  index,
  store,
  show,
  update,
  destroy,
  storeValidationRules,
  updateValidationRules,
} = require('../controllers/inventoryController');

/*
|--------------------------------------------------------------------------
| Inventory Routes
|--------------------------------------------------------------------------
|
| GET    /api/v1/inventory            - List inventory (supports filtering)
| POST   /api/v1/inventory            - Create inventory record
| GET    /api/v1/inventory/:id        - Get single record
| PUT    /api/v1/inventory/:id        - Update record
| DELETE /api/v1/inventory/:id        - Delete record
|
| Cross-service query params:
|   ?product_name=X   - Filter by product name (used by Product Service)
|   ?product_id=X     - Filter by product ID
|
*/

// Rate limiter for read operations (GET): 200 requests per minute per IP
const readLimiter = rateLimit({
  windowMs:   60 * 1000,
  max:        200,
  standardHeaders: true,
  legacyHeaders:   false,
  message: { success: false, message: 'Too many requests. Please try again later.' },
});

// Rate limiter for write operations (POST/PUT/DELETE): 60 requests per minute per IP
const writeLimiter = rateLimit({
  windowMs:   60 * 1000,
  max:        60,
  standardHeaders: true,
  legacyHeaders:   false,
  message: { success: false, message: 'Too many requests. Please try again later.' },
});

router.get('/',    readLimiter,  index);
router.post('/',   writeLimiter, storeValidationRules, store);
router.get('/:id', readLimiter,  show);
router.put('/:id', writeLimiter, updateValidationRules, update);
router.delete('/:id', writeLimiter, destroy);

module.exports = router;

