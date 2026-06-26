import { defineConfig } from 'vite';
import fs from 'node:fs';

const hotFile = 'public/hot';

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

export default defineConfig(({ command }) => ({
    base: command === 'build' ? '/build/' : '/',

    plugins: [phpDevServer()],

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
            input: {
                app: 'resources/js/app.js',
                tasks: 'resources/js/tasks.js',
            },
        },
    },
}));
