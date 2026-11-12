import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import * as packages from './package.json';
import fsExtra from 'fs-extra'; // Import fs-extra as a default import
import { join } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/storefront.css',
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/storefront.js',
                'resources/css/admin-ui.css',
                'resources/js/admin-shell.js',

                // Resources assets js file paths
                'resources/assets/js/add-products',
                'resources/assets/js/alerts',
                'resources/assets/js/analytics-dashboard',
                'resources/assets/js/apexcharts-area',
                'resources/assets/js/apexcharts-bar',
                'resources/assets/js/apexcharts-boxplot',
                'resources/assets/js/apexcharts-bubble',
                'resources/assets/js/apexcharts-candlestick',
                'resources/assets/js/apexcharts-column',
                'resources/assets/js/apexcharts-dayjs',
                'resources/assets/js/apexcharts-funnel',
                'resources/assets/js/apexcharts-heatmap',
                'resources/assets/js/apexcharts-line',
                'resources/assets/js/apexcharts-mixed',
                'resources/assets/js/apexcharts-pie',
                'resources/assets/js/apexcharts-polararea',
                'resources/assets/js/apexcharts-radar',
                'resources/assets/js/apexcharts-radialbar',
                'resources/assets/js/apexcharts-rangearea',
                'resources/assets/js/apexcharts-scatter',
                'resources/assets/js/apexcharts-timeline',
                'resources/assets/js/apexcharts-treemap',
                'resources/assets/js/authentication-main',
                'resources/assets/js/blog-create',
                'resources/assets/js/blog-details',
                'resources/assets/js/canada',
                'resources/assets/js/chartjs-charts',
                'resources/assets/js/chat',
                'resources/assets/js/choices',
                'resources/assets/js/color-picker',
                'resources/assets/js/coming-soon',
                'resources/assets/js/courses-dashboard',
                'resources/assets/js/create-invoice',
                'resources/assets/js/create-project',
                'resources/assets/js/crm-companies',
                'resources/assets/js/crm-contacts',
                'resources/assets/js/crm-dashboard',
                'resources/assets/js/crm-deals',
                'resources/assets/js/crm-leads',
                'resources/assets/js/crypto-buy_sell',
                'resources/assets/js/crypto-currency-exchange',
                'resources/assets/js/crypto-dashboard',
                'resources/assets/js/crypto-marketcap',
                'resources/assets/js/custom-switcher',
                'resources/assets/js/custom',
                'resources/assets/js/customers-list',
                'resources/assets/js/datatables',
                'resources/assets/js/date&time_pickers',
                'resources/assets/js/defaultmenu',
                'resources/assets/js/draggable-cards',
                'resources/assets/js/echarts',
                'resources/assets/js/ecommerce-dashboard',
                'resources/assets/js/file-manager',
                'resources/assets/js/fileupload',
                'resources/assets/js/form-advanced',
                'resources/assets/js/form-input-mask',
                'resources/assets/js/form-wizard-init',
                'resources/assets/js/fullcalendar',
                'resources/assets/js/gallery',
                'resources/assets/js/google-maps',
                'resources/assets/js/grid',
                'resources/assets/js/hrm-dashboard',
                'resources/assets/js/invoice-list',
                'resources/assets/js/job-candidate-details',
                'resources/assets/js/job-details',
                'resources/assets/js/job-list',
                'resources/assets/js/job-search-candidate',
                'resources/assets/js/job-search',
                'resources/assets/js/jobs-dashboard',
                'resources/assets/js/jobs-post',
                'resources/assets/js/jsvectormap',
                'resources/assets/js/landing',
                'resources/assets/js/leaflet',
                'resources/assets/js/mail-settings',
                'resources/assets/js/mail',
                'resources/assets/js/main',
                'resources/assets/js/media-player',
                'resources/assets/js/medical-dashboard',
                'resources/assets/js/modal',
                'resources/assets/js/nft-create',
                'resources/assets/js/nft-dashboard',
                'resources/assets/js/nft-details',
                'resources/assets/js/nouislider',
                'resources/assets/js/orders',
                'resources/assets/js/pos-dashboard',
                'resources/assets/js/prism-custom',
                'resources/assets/js/product-details',
                'resources/assets/js/products',
                'resources/assets/js/profile',
                'resources/assets/js/project-overview',
                'resources/assets/js/projects-dashboard',
                'resources/assets/js/quill-editor',
                'resources/assets/js/ratings',
                'resources/assets/js/reviews',
                'resources/assets/js/russia',
                'resources/assets/js/sales-dashboard',
                'resources/assets/js/school-dashboard',
                'resources/assets/js/search-results',
                'resources/assets/js/select2',
                'resources/assets/js/simplebar',
                'resources/assets/js/social-media-dashboard',
                'resources/assets/js/sortable',
                'resources/assets/js/spain',
                'resources/assets/js/sticky',
                'resources/assets/js/stocks-dashboard',
                'resources/assets/js/sweet-alerts',
                'resources/assets/js/swiper',
                'resources/assets/js/tagify',
                'resources/assets/js/task-kanban-board',
                'resources/assets/js/task-list',
                'resources/assets/js/team',
                'resources/assets/js/toasts',
                'resources/assets/js/todolist',
                'resources/assets/js/tour',
                'resources/assets/js/us-merc-en',
                'resources/assets/js/validation',
                'resources/assets/js/widgets',
                
            ],
            refresh: true,
        }),
        tailwindcss(),

        viteStaticCopy({
            targets: [
              {
                src: ([
                  'resources/assets/images/',
                  'resources/assets/audio/',
                  'resources/assets/video/',
                  'resources/assets/icon-fonts/',
                  'resources/assets/js/main.js',
                  'resources/assets/js/sticky.js',
                  'resources/assets/js/chat.js',
                  'resources/assets/js/show-password.js',
                  'resources/assets/js/coming-soon.js',
                  'resources/assets/js/two-step-verification.js',
                  'resources/assets/js/authentication-main.js',
                  'resources/assets/js/apex-github-data.js',
                  'resources/assets/js/apexcharts-candlestick-seriesdata.js',
                  'resources/assets/js/apexcharts-irregulardata.js',
                  'resources/assets/js/apexcharts-stock-prices.js',
                  'resources/assets/js/apexcharts-dayjs.js',
                  'resources/assets/js/dataseries.js',
                  'resources/assets/js/form-wizard.js',
                ]),
                dest: 'assets/'
              }
            ]
        }),
  
        {
          // Use a custom plugin for copying distribution files
          name: 'copy-dist-files',
          writeBundle: async () => {
            const destDir = 'public/build/assets/libs';  // Update the destination directory
    
            for (const dep of Object.keys(packages.dependencies)) {
              const srcPath = join('node_modules', dep, 'dist');
              const destPath = join(destDir, dep);
    
              // Check if the 'dist' directory exists for the dependency
              if (await fsExtra.pathExists(srcPath)) {
                // Copy the distribution files (contents of 'dist') to the destination directory
                await fsExtra.copy(srcPath, destPath, {
                  overwrite: true,
                  recursive: true,
                });
    
                // Remove the 'dist' directory from the destination
                await fsExtra.remove(join(destPath, 'dist'));
              } else {
                // If 'dist' folder doesn't exist, check if the package itself exists and copy it.
                const packageSrcPath = join('node_modules', dep);
                const packageDestPath = join(destDir, dep);
    
                if (await fsExtra.pathExists(packageSrcPath)) {
                  await fsExtra.copy(packageSrcPath, packageDestPath, {
                    overwrite: true,
                    recursive: true,
                  });
                }
              }
            }
          },
        },
  
        {
          name: 'blade',
          handleHotUpdate({ file, server }) {
            if (file.endsWith('.blade.php')) {
            server.ws.send({
                type: 'full-reload',
                path: '*',
            });
            }
          },
        }

    ],
    build: {
        chunkSizeWarningLimit: 1600,
        outDir: 'public/build',
        emptyOutDir: true,
    },
});
