$envCertFilePath = $env:SSL_CERT_FILE
$certArea = Split-Path $envCertFilePath -Parent
$certFile = Split-Path $envCertFilePath -Leaf

if (!(Test-Path $certArea)) {
    mkdir $certArea
}
if (!(Test-Path $envCertFilePath -PathType Leaf)) {
    Remove-Item env:\SSL_CERT_FILE
}
Push-Location $certArea
curl -fsSL -o $certFile --time-cond $certFile https://curl.haxx.se/ca/cacert.pem
Pop-Location

$env:SSL_CERT_FILE = $envCertFilePath
