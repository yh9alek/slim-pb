import fs from 'node:fs';
import { createLogger } from 'vite';

const PLUGIN_VERSION = '1.0.0';
const FALLBACK_URL = 'http://localhost:8000';

// Colores de marca en truecolor ANSI.
const VITE_COLOR = '\x1b[38;2;113;138;218m'; // #718ADA
const SLIM_COLOR = '\x1b[38;2;92;206;167m'; // #5CCEA7

// --- Color ANSI, sin dependencias ---
const RESET = '\x1b[0m';
const FG_RESET = '\x1b[39m';
const bold = (s) => `\x1b[1m${s}\x1b[22m`;
const dim = (s) => `\x1b[2m${s}\x1b[22m`;
const green = (s) => `\x1b[32m${s}${FG_RESET}`;
const cyan = (s) => `\x1b[36m${s}${RESET}`;

function slimVersion() {
    try {
        const lock = JSON.parse(fs.readFileSync('composer.lock', 'utf8'));
        const packages = [...(lock.packages ?? []), ...(lock['packages-dev'] ?? [])];
        const slim = packages.find((p) => p.name === 'slim/slim');
        return (slim?.version ?? '?').replace(/^v/, '');
    } catch {
        return '?';
    }
}

function appUrl() {
    try {
        const env = fs.readFileSync('.env', 'utf8');
        const match = env.match(/^\s*APP_URL\s*=\s*(.*)$/m);
        const value = match?.[1]?.trim().replace(/^["']|["']$/g, '');
        if (value) return value;
    } catch {
        /* sin .env: fallback */
    }
    return FALLBACK_URL;
}

// Pone en negrita el puerto (:NNNN) de una URL, si lo tiene.
function boldPort(url) {
    return url.replace(/:(\d+)(?=$|\/)/, (_, port) => `:${bold(port)}`);
}

// Logger de Vite que recolorea "VITE vX" (verde) a #6685F5. Se pasa como
// `customLogger` en vite.config.js.
export function createSlimLogger() {
    const logger = createLogger();
    const rawInfo = logger.info.bind(logger);

    logger.info = (msg, options) => {
        if (typeof msg === 'string') {
            msg = msg.replace(/\x1b\[32m(?=\x1b\[1mVITE)/, VITE_COLOR);
        }
        rawInfo(msg, options);
    };

    return logger;
}

function slimName() {
    return `${SLIM_COLOR}${bold('SLIM')} v${slimVersion()}${FG_RESET}`;
}

export function slimBanner() {
    return {
        name: 'slim-banner',
        apply: 'serve',
        configureServer(server) {
            const printUrls = server.printUrls.bind(server);

            server.printUrls = () => {
                printUrls(); // primero, las URLs propias de Vite (Local/Network)

                const framework = `${slimName()}  ${dim('plugin')} v${PLUGIN_VERSION}`;
                const url = `${green('→')}  ${bold('APP_URL')}: ${cyan(boldPort(appUrl()))}`;

                console.log('');
                console.log(`  ${framework}`);
                console.log('');
                console.log(`  ${url}`);
            };
        },
    };
}
