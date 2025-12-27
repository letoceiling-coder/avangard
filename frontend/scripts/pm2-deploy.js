/**
 * PM2 Ecosystem для автоматического деплоя
 * Использование: pm2 start scripts/pm2-deploy.js
 * 
 * PM2 будет следить за изменениями и автоматически перезапускать приложение
 */

module.exports = {
  apps: [
    {
      name: 'avangard-react-deploy',
      script: 'npm',
      args: 'run build',
      cwd: '/home/user/trendagent.siteaccess.ru/public_html/avangard-react',
      watch: false,
      autorestart: false,
      instances: 1,
      exec_mode: 'fork',
      env: {
        NODE_ENV: 'production'
      },
      // Хук после успешной сборки
      post_update: [
        'npm ci',
        'npm run build',
        'cp -r dist/. ..'
      ],
      // Автоматический деплой при изменениях в git
      deploy: {
        production: {
          user: 'user',
          host: 'trendagent.siteaccess.ru',
          ref: 'origin/main',
          repo: 'https://github.com/letoceiling-coder/avangard-react.git',
          path: '/home/user/trendagent.siteaccess.ru/public_html/avangard-react',
          'post-deploy': 'npm ci && npm run build && cp -r dist/. ..',
          'pre-setup': ''
        }
      }
    }
  ]
};

