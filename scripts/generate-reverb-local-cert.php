<?php

declare(strict_types=1);

if (! extension_loaded('openssl')) {
    fwrite(STDERR, "The OpenSSL PHP extension is required.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);
$outputDir = $projectRoot.DIRECTORY_SEPARATOR.'.certs';
$certificatePath = $outputDir.DIRECTORY_SEPARATOR.'reverb-local.crt';
$privateKeyPath = $outputDir.DIRECTORY_SEPARATOR.'reverb-local.key';

$appData = getenv('APPDATA') ?: '';
$home = getenv('USERPROFILE') ?: getenv('HOME') ?: '';

$caCertificateCandidates = array_filter([
    $appData ? $appData.DIRECTORY_SEPARATOR.'symfony-cli'.DIRECTORY_SEPARATOR.'certs'.DIRECTORY_SEPARATOR.'rootCA.pem' : null,
    $home ? $home.DIRECTORY_SEPARATOR.'.symfony5'.DIRECTORY_SEPARATOR.'certs'.DIRECTORY_SEPARATOR.'rootCA.pem' : null,
]);

$caKeyCandidates = array_filter([
    $appData ? $appData.DIRECTORY_SEPARATOR.'symfony-cli'.DIRECTORY_SEPARATOR.'certs'.DIRECTORY_SEPARATOR.'rootCA-key.pem' : null,
    $home ? $home.DIRECTORY_SEPARATOR.'.symfony5'.DIRECTORY_SEPARATOR.'certs'.DIRECTORY_SEPARATOR.'rootCA-key.pem' : null,
]);

$caCertificatePath = firstExistingPath($caCertificateCandidates);
$caKeyPath = firstExistingPath($caKeyCandidates);

if (! $caCertificatePath || ! $caKeyPath) {
    fwrite(STDERR, "Symfony local CA files were not found. Run `symfony local:server:ca:install` first.\n");
    exit(1);
}

$hosts = array_values(array_unique(array_filter([
    '127.0.0.1',
    'localhost',
    'ba3eedlearn.wip',
    ...array_slice($argv, 1),
])));

if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
    fwrite(STDERR, "Unable to create output directory: {$outputDir}\n");
    exit(1);
}

$distinguishedName = [
    'commonName' => $hosts[0],
    'organizationName' => 'Ba3eedLearn Local',
];

$altNames = [];
$dnsIndex = 1;
$ipIndex = 1;

foreach ($hosts as $host) {
    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
        $altNames[] = sprintf('IP.%d = %s', $ipIndex++, $host);
        continue;
    }

    $altNames[] = sprintf('DNS.%d = %s', $dnsIndex++, $host);
}

$opensslConfig = <<<CFG
[ req ]
distinguished_name = req_distinguished_name
prompt = no
default_md = sha256
req_extensions = v3_req

[ req_distinguished_name ]
CN = {$distinguishedName['commonName']}
O = {$distinguishedName['organizationName']}

[ v3_req ]
subjectAltName = @alt_names
basicConstraints = CA:FALSE
keyUsage = digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth

[ alt_names ]
%s
CFG;

$configContents = sprintf($opensslConfig, implode(PHP_EOL, $altNames));
$configPath = tempnam(sys_get_temp_dir(), 'reverb-tls-');

if ($configPath === false) {
    fwrite(STDERR, "Unable to create temporary OpenSSL config.\n");
    exit(1);
}

file_put_contents($configPath, $configContents);

$privateKey = openssl_pkey_new([
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
    'config' => $configPath,
]);

if ($privateKey === false) {
    cleanupTempConfig($configPath);
    failWithOpenSslError('Unable to generate a private key.');
}

$csr = openssl_csr_new($distinguishedName, $privateKey, [
    'digest_alg' => 'sha256',
    'config' => $configPath,
    'req_extensions' => 'v3_req',
]);

if ($csr === false) {
    cleanupTempConfig($configPath);
    failWithOpenSslError('Unable to generate a certificate signing request.');
}

$caCertificate = file_get_contents($caCertificatePath);
$caKey = openssl_pkey_get_private('file://'.$caKeyPath);

if ($caCertificate === false || $caKey === false) {
    cleanupTempConfig($configPath);
    failWithOpenSslError('Unable to read Symfony CA files.');
}

$serial = random_int(1, PHP_INT_MAX);
$signedCertificate = openssl_csr_sign(
    $csr,
    $caCertificate,
    $caKey,
    825,
    [
        'digest_alg' => 'sha256',
        'config' => $configPath,
        'x509_extensions' => 'v3_req',
    ],
    $serial
);

if ($signedCertificate === false) {
    cleanupTempConfig($configPath);
    failWithOpenSslError('Unable to sign the Reverb certificate.');
}

$privateKeyPem = '';
$certificatePem = '';

if (! openssl_pkey_export($privateKey, $privateKeyPem) || ! openssl_x509_export($signedCertificate, $certificatePem)) {
    cleanupTempConfig($configPath);
    failWithOpenSslError('Unable to export the generated certificate files.');
}

file_put_contents($privateKeyPath, $privateKeyPem);
file_put_contents($certificatePath, $certificatePem);

cleanupTempConfig($configPath);

fwrite(STDOUT, "Generated Reverb TLS certificate:\n");
fwrite(STDOUT, "  Certificate: {$certificatePath}\n");
fwrite(STDOUT, "  Private key: {$privateKeyPath}\n");
fwrite(STDOUT, "  SANs: ".implode(', ', $hosts)."\n");

function firstExistingPath(array $paths): ?string
{
    foreach ($paths as $path) {
        if ($path && file_exists($path)) {
            return $path;
        }
    }

    return null;
}

function cleanupTempConfig(string $configPath): void
{
    if (is_file($configPath)) {
        @unlink($configPath);
    }
}

function failWithOpenSslError(string $message): void
{
    $details = [];

    while ($error = openssl_error_string()) {
        $details[] = $error;
    }

    fwrite(STDERR, $message."\n");

    if ($details !== []) {
        fwrite(STDERR, implode("\n", $details)."\n");
    }

    exit(1);
}
