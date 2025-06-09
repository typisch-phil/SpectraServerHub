const { spawn } = require('child_process');
const path = require('path');

console.log('Starting SpectraHost PHP Server...');

const phpServer = spawn('php', ['-S', '0.0.0.0:5000', '-t', 'public'], {
  cwd: __dirname,
  stdio: 'inherit'
});

phpServer.on('error', (err) => {
  console.error('Failed to start PHP server:', err);
});

phpServer.on('close', (code) => {
  console.log(`PHP server exited with code ${code}`);
});

process.on('SIGINT', () => {
  console.log('Shutting down...');
  phpServer.kill();
  process.exit();
});