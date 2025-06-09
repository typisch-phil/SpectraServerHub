#!/usr/bin/env node

// Start the JavaScript server directly
process.env.NODE_ENV = 'development';

import('./server/index.js')
  .then(() => {
    console.log('JavaScript server started successfully');
  })
  .catch((error) => {
    console.error('Failed to start server:', error);
    process.exit(1);
  });