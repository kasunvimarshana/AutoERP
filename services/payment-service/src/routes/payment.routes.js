'use strict';
const { Router } = require('express');
const PaymentController = require('../controllers/payment.controller');
const { validateSagaRequest, validateRefund } = require('../middleware/validation');

const router = Router();
const ctrl   = new PaymentController();

// Saga participant endpoints (called by Saga Orchestrator)
router.post('/process',              validateSagaRequest, ctrl.process.bind(ctrl));
router.post('/:paymentId/refund',    validateRefund,      ctrl.refund.bind(ctrl));

// General endpoints
router.get('/',                ctrl.index.bind(ctrl));
router.get('/:paymentId',      ctrl.show.bind(ctrl));
router.get('/:paymentId/status', ctrl.status.bind(ctrl));

module.exports = router;
