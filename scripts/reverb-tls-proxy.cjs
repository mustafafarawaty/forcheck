const fs = require('fs');
const https = require('https');
const path = require('path');
const httpProxy = require('http-proxy');

const appData = process.env.APPDATA || '';
const pfxPath = process.env.REVERB_PROXY_PFX
    || path.join(appData, 'symfony-cli', 'certs', 'default.p12');
const passphrase = process.env.REVERB_PROXY_PASSPHRASE || '';
const listenHost = process.env.REVERB_PROXY_HOST || '0.0.0.0';
const listenPort = Number(process.env.REVERB_PROXY_PORT || 8080);
const targetHost = process.env.REVERB_PROXY_TARGET_HOST || '127.0.0.1';
const targetPort = Number(process.env.REVERB_PROXY_TARGET_PORT || 8081);

if (!fs.existsSync(pfxPath)) {
    console.error(`Reverb TLS proxy could not find the Symfony certificate at: ${pfxPath}`);
    process.exit(1);
}

const pfx = fs.readFileSync(pfxPath);

const proxy = httpProxy.createProxyServer({
    target: `http://${targetHost}:${targetPort}`,
    ws: true,
    xfwd: true,
    changeOrigin: true,
    secure: false,
});

proxy.on('error', (error, request, responseOrSocket) => {
    const message = `Reverb TLS proxy failed: ${error.message}`;

    if (responseOrSocket?.writeHead) {
        responseOrSocket.writeHead(502, { 'content-type': 'text/plain' });
        responseOrSocket.end(message);
        return;
    }

    if (responseOrSocket?.write) {
        responseOrSocket.write('HTTP/1.1 502 Bad Gateway\r\nContent-Type: text/plain\r\n\r\n');
        responseOrSocket.write(message);
    }

    responseOrSocket?.destroy?.();
});

const server = https.createServer({ pfx, passphrase }, (request, response) => {
    proxy.web(request, response);
});

server.on('upgrade', (request, socket, head) => {
    proxy.ws(request, socket, head);
});

server.listen(listenPort, listenHost, () => {
    console.log(`Reverb TLS proxy listening on https://${listenHost}:${listenPort} -> http://${targetHost}:${targetPort}`);
});
