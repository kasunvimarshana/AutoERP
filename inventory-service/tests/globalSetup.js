'use strict';

const { MongoMemoryServer } = require('mongodb-memory-server');
const mongoose = require('mongoose');

let mongod;

/**
 * Jest global setup — starts an in-memory MongoDB server before all tests.
 * This avoids the need for a real MongoDB installation during CI/local testing.
 */
module.exports = async () => {
  mongod = await MongoMemoryServer.create();
  const uri = mongod.getUri();
  process.env.MONGO_URI = uri;
  // Store the server instance on global so teardown can access it
  global.__MONGOD__ = mongod;
};
