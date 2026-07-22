import { defineConfig } from 'vite';
import { slimBanner, createSlimLogger } from './slim-banner.js';
import { slimWelcome } from './slim-welcome.js';
import tailwindcss from '@tailwindcss/vite';

import fs from 'node:fs';
import path from 'node:path';

const hotFile = 'public/hot';
const jsDir = 'templates/js';

function phpDevServer() {
    return {
        name: 'php-dev-server',
        apply: 'serve',
        configureServer(server) {
            server.httpServer?.once('listening', () => {
                const address = server.httpServer.address();
                if (address && typeof address === 'object') {
                    fs.writeFileSync(hotFile, `http://localhost:${address.port}`);
                }
            });

            const clean = () => {
                try {
                    fs.unlinkSync(hotFile);
                } catch {
                    /* ya no existe */
                }
            };

            for (const signal of ['SIGINT', 'SIGTERM', 'SIGHUP']) {
                process.once(signal, () => {
                    clean();
                    process.exit();
                });
            }
            process.once('exit', clean);
        },
    };
}

function findPageEntries() {
    const entries = [];

    for (const module of fs.readdirSync(jsDir, { withFileTypes: true })) {
        if (!module.isDirectory()) continue;

        const presentation = path.posix.join(jsDir, module.name, 'presentation');
        if (!fs.existsSync(presentation)) continue;

        for (const file of fs.readdirSync(presentation)) {
            if (file.endsWith('.js')) {
                entries.push(path.posix.join(presentation, file));
            }
        }
    }

    return entries;
}

export default defineConfig(({ command }) => ({
    base: command === 'build' ? '/build/' : '/',

    plugins: [tailwindcss(), phpDevServer(), slimBanner(), slimWelcome()],

    // Recolorea el token "VITE vX" a #4E69FB en la salida de dev.
    customLogger: createSlimLogger(),

    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173',
  },

  // Desactiva la "publicDir" de Vite: en un proyecto PHP, public/ es el
  // document root, no la carpeta de estáticos de Vite. Evita el warning
  // y el choque de nombres.
  publicDir: false,

  build: {
    manifest: true,

        // DENTRO de public: los assets quedan bajo el document root.
        outDir: 'public/build',
        emptyOutDir: true,
        rollupOptions: {
            input: ['templates/js/app.js', ...findPageEntries()],
        },
    },
}));
