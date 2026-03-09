'use strict';
const { Sequelize } = require('sequelize');
const { logger } = require('../utils/logger');
const sequelize = new Sequelize({
  dialect: 'mysql',
  host: process.env.DB_HOST || 'mysql_payment',
  port: parseInt(process.env.DB_PORT, 10) || 3306,
  database: process.env.DB_DATABASE || 'saas_payments',
  username: process.env.DB_USER || 'payment_user',
  password: process.env.DB_PASSWORD || 'payment_secret',
  pool: {
    max: parseInt(process.env.DB_POOL_MAX, 10) || 10,
    min: parseInt(process.env.DB_POOL_MIN, 10) || 2,
    acquire: 30000,
    idle: 10000,
  },
  logging: (msg) => logger.debug(msg),
  define: { underscored: true, timestamps: true },
});
async function connectDatabase() {
  await sequelize.authenticate();
  await sequelize.sync({ alter: false });
}
module.exports = { sequelize, connectDatabase };
