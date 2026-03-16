const express = require('express');
const taskController = require('../controllers/taskController');

const router = express.Router();

router.get('/tasks', taskController.index);
router.get('/create', taskController.create);
router.post('/create', taskController.store);

module.exports = router;