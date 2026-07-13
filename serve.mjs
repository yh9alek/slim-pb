import { spawn } from 'node:child_process';

// Lanza el servidor embebido de PHP (php -S) pero imprime la salida al
// estilo de `artisan serve` de Laravel, reemplazando el banner por defecto
// de PHP y ocultando el ruido de conexiones (Accepted/Closing).
//
// Se invoca desde composer: "serve": "node serve.mjs".

const HOST = process.env.SERVE_HOST ?? 'localhost';
const PORT = process.env.SERVE_PORT ?? '8000';
const PHP = process.env.PHP_BIN ?? 'php';
const url = `http://${HOST}:${PORT}`;

// --- Color ANSI ---
const dim = (s) => `\x1b[2m${s}\x1b[22m`;

function banner() {
    console.log('');
    console.log(`   INFO  Server running on [${url}].`);
    console.log('');
    console.log(`   ${dim('Press Ctrl+C to stop the server')}`);
    console.log('');
}

const php = spawn(PHP, ['-S', `${HOST}:${PORT}`, '-t', 'public', 'server.php'], {
    stdio: ['inherit', 'inherit', 'pipe'],
});

let announced = false;
let buffer = '';

php.stderr.on('data', (chunk) => {
    buffer += chunk.toString();
    const lines = buffer.split(/\r?\n/);
    buffer = lines.pop() ?? '';

    for (const line of lines) {
        // El "started" de PHP dispara nuestro banner (una sola vez).
        if (/Development Server \(.*\) started/.test(line)) {
            if (!announced) {
                banner();
                announced = true;
            }
            continue;
        }
        // Ruido que Laravel no muestra.
        if (/Development Server \(.*\) stopped/.test(line)) continue;
        if (/\b(Accepted|Closing)\s*$/.test(line)) continue;
        if (line.trim() === '') continue;

        process.stderr.write(`${line}\n`);
    }
});

const stop = () => php.kill('SIGTERM');
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
php.on('exit', (code) => process.exit(code ?? 0));
