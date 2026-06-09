$env:REVERB_PROXY_HOST = '0.0.0.0'
$env:REVERB_PROXY_PORT = '8080'
$env:REVERB_PROXY_TARGET_HOST = '127.0.0.1'
$env:REVERB_PROXY_TARGET_PORT = '8081'
Set-Location 'C:\Users\ASUS\Desktop\Ba3eedLearn'
node scripts/reverb-tls-proxy.cjs
