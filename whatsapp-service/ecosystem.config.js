module.exports = {
  apps: [
    {
      name: 'sanabil-whatsapp-service',
      script: './server.js',
      cwd: __dirname,
      instances: 1,
      exec_mode: 'fork',
      autorestart: true,
      watch: false,
      max_memory_restart: '512M',
      env: {
        NODE_ENV: 'production',
        PORT: 4000,
        HOST: '127.0.0.1',
        WA_SERVER_NODE_ID: 'VPS-1',
        WHATSAPP_POLL_INTERVAL_MS: 3000,
        WHATSAPP_BATCH_SIZE: 10,
        WHATSAPP_MAX_ATTEMPTS: 3,
        WHATSAPP_STALE_PROCESSING_MINUTES: 5,
        WHATSAPP_FAILURE_AUTOPAUSE_THRESHOLD: 5,
      },
    },
  ],
};
