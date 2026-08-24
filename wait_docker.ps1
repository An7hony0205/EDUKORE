$maxRetries = 20
$retryCount = 0

while ($retryCount -lt $maxRetries) {
    docker ps -a 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Docker is up!"
        docker-compose up -d
        exit 0
    }
    Write-Host "Waiting for Docker..."
    Start-Sleep -Seconds 5
    $retryCount++
}
Write-Host "Docker failed to start in time."
exit 1
