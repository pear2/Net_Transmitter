$certArea = 'C:\usr\local\ssl'
if (!(Test-Path $certArea)) {
    mkdir $certArea
    Push-Location $certArea
    appveyor-retry appveyor DownloadFile https://curl.haxx.se/ca/cacert.pem -FileName cert.pem
    Pop-Location
}
