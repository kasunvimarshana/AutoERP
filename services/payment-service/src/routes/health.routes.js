'use strict';
const { Router } = require('express');
const HealthController = require('../controllers/health.controller');

const router = Router();
const ctrl   = new HealthController();

router.get('/',     ctrl.health.bind(ctrl));
router.get('/ping', ctrl.ping.bind(ctrl));

module.exports = router;
