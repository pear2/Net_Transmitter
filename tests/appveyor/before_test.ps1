if ((Test-Path .\composer.lock)) {
    composer update --no-progress
} else {
    composer install --no-progress
}

if ((Test-Path .\vendor\bin)) {
    $env:PATH = ((Resolve-Path .\vendor\bin).ToString() + [IO.Path]::PathSeparator + $env:PATH)
    appveyor SetVariable -Name 'PATH' -Value $env:PATH
}
