import { spawn } from 'child_process';
import { writeFileSync, unlinkSync, existsSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const hotFile = resolve(__dirname, 'public/hot');

// Create hot file
writeFileSync(hotFile, 'http://localhost:5173');
console.log('Created hot file');

// Start Vite
const vite = spawn('npx', ['vite'], {
  stdio: 'inherit',
  shell: true
});

// Cleanup function
const cleanup = () => {
  if (existsSync(hotFile)) {
    unlinkSync(hotFile);
    console.log('Cleaned up hot file');
  }
};

// Handle various exit scenarios
process.on('SIGINT', () => {
  cleanup();
  process.exit(0);
});

process.on('SIGTERM', () => {
  cleanup();
  process.exit(0);
});

process.on('exit', cleanup);
process.on('beforeExit', cleanup);
process.on('uncaughtException', () => {
  cleanup();
  process.exit(1);
});

vite.on('close', (code) => {
  cleanup();
  process.exit(code);
});

vite.on('error', () => {
  cleanup();
  process.exit(1);
});
