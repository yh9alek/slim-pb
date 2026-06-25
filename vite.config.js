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

    build: {
        manifest: true,
        outDir: 'public/build',
        emptyOutDir: true,
        rollupOptions: {
            // Una entrada por "área": 'app' es común a todo; el resto, por vista.
            input: {
                app: 'resources/js/app.js',
                tasks: 'resources/js/tasks.js',
                others: 'resources/js/others.js',
            },
        },
    },
}));
