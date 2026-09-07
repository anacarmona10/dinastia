/**
 * ============================================================================
 * Dinastía AMV - Node.js Express Router: /api/planes (backend/routes/planes.js)
 * ============================================================================
 */

const express = require('express');
const router = express.Router();
const planesController = require('../controllers/planesController');

// GET /api/planes - Catálogo público con filtros
router.get('/', planesController.obtenerPlanes);

// GET /api/planes/:id - Detalle de un plan específico
router.get('/:id', planesController.obtenerPlanPorId);

module.exports = router;
