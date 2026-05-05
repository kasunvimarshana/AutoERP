Set-Location "d:/projects/KVAutoERP"

function Get-RelPath([string]$fullPath) {
    $root = (Resolve-Path ".").Path
    $rel = $fullPath.Replace($root + "\", "").Replace("\", "/")
    return $rel
}

function Add-LinkLines([string[]]$lines, [System.Collections.Generic.List[string]]$target) {
    foreach ($line in $lines) {
        if ($line -and $line.Trim().Length -gt 0) {
            [void]$target.Add($line)
        }
    }
}

$modules = Get-ChildItem "app/Modules" -Directory | Sort-Object Name

foreach ($module in $modules) {
    $name = $module.Name
    $modulePath = $module.FullName
    $docPath = "docs/architecture/modules/$name.md"

    if (-not (Test-Path $docPath)) {
        continue
    }

    $docContent = Get-Content $docPath -Raw
    if ($docContent -match "## 11\. Concrete Source Map") {
        continue
    }

    $lines = New-Object 'System.Collections.Generic.List[string]'
    [void]$lines.Add("")
    [void]$lines.Add("## 11. Concrete Source Map")

    $moduleRel = Get-RelPath $modulePath
    [void]$lines.Add("- Module root: [$moduleRel]($moduleRel)")

    $routeFile = Join-Path $modulePath "routes/api.php"
    if (Test-Path $routeFile) {
        $routeRel = Get-RelPath $routeFile
        [void]$lines.Add("- Route source: [$routeRel]($routeRel)")
    }

    $providerFiles = @()
    $providerDir = Join-Path $modulePath "Infrastructure/Providers"
    if (Test-Path $providerDir) {
        $providerFiles = Get-ChildItem $providerDir -File -Filter "*.php" | Sort-Object Name | Select-Object -First 3
    }

    if ($providerFiles.Count -gt 0) {
        [void]$lines.Add("- Provider files:")
        foreach ($f in $providerFiles) {
            $rel = Get-RelPath $f.FullName
            [void]$lines.Add("  - [$rel]($rel)")
        }
    }

    $entityFiles = @()
    $entityDir = Join-Path $modulePath "Domain/Entities"
    if (Test-Path $entityDir) {
        $entityFiles = Get-ChildItem $entityDir -File -Filter "*.php" | Sort-Object Name | Select-Object -First 5
    }

    if ($entityFiles.Count -gt 0) {
        [void]$lines.Add("- Domain entities (representative):")
        foreach ($f in $entityFiles) {
            $rel = Get-RelPath $f.FullName
            [void]$lines.Add("  - [$rel]($rel)")
        }
    }

    $serviceFiles = @()
    $serviceDir = Join-Path $modulePath "Application/Services"
    if (Test-Path $serviceDir) {
        $serviceFiles = Get-ChildItem $serviceDir -File -Filter "*.php" | Sort-Object Name | Select-Object -First 5
    }

    if ($serviceFiles.Count -gt 0) {
        [void]$lines.Add("- Application services (representative):")
        foreach ($f in $serviceFiles) {
            $rel = Get-RelPath $f.FullName
            [void]$lines.Add("  - [$rel]($rel)")
        }
    }

    $repoFiles = @()
    $repoDir = Join-Path $modulePath "Infrastructure/Persistence/Eloquent/Repositories"
    if (Test-Path $repoDir) {
        $repoFiles = Get-ChildItem $repoDir -File -Filter "*.php" | Sort-Object Name | Select-Object -First 5
    }

    if ($repoFiles.Count -gt 0) {
        [void]$lines.Add("- Repository implementations (representative):")
        foreach ($f in $repoFiles) {
            $rel = Get-RelPath $f.FullName
            [void]$lines.Add("  - [$rel]($rel)")
        }
    }

    $migrationFiles = @()
    $migDir = Join-Path $modulePath "database/migrations"
    if (Test-Path $migDir) {
        $migrationFiles = Get-ChildItem $migDir -File -Filter "*.php" | Sort-Object Name | Select-Object -First 5
    }

    if ($migrationFiles.Count -gt 0) {
        [void]$lines.Add("- Migration files (representative):")
        foreach ($f in $migrationFiles) {
            $rel = Get-RelPath $f.FullName
            [void]$lines.Add("  - [$rel]($rel)")
        }
    }

    $testFiles = Get-ChildItem "tests" -Recurse -File -Filter "*.php" |
        Where-Object { $_.Name -like "*$name*Test.php" } |
        Sort-Object Name |
        Select-Object -First 8

    if ($testFiles.Count -gt 0) {
        [void]$lines.Add("- Test references:")
        foreach ($f in $testFiles) {
            $rel = Get-RelPath $f.FullName
            [void]$lines.Add("  - [$rel]($rel)")
        }
    }

    [void]$lines.Add("")
    [void]$lines.Add("## 12. Real-Time Sequence References")

    $eventFiles = @()
    $eventDir = Join-Path $modulePath "Domain/Events"
    if (Test-Path $eventDir) {
        $eventFiles = Get-ChildItem $eventDir -File -Filter "*.php" | Sort-Object Name | Select-Object -First 4
    }

    $listenerFiles = @()
    $listenerDir = Join-Path $modulePath "Infrastructure/Listeners"
    if (Test-Path $listenerDir) {
        $listenerFiles = Get-ChildItem $listenerDir -File -Filter "*.php" | Sort-Object Name | Select-Object -First 4
    }

    [void]$lines.Add("- Entry path: HTTP routes dispatch to thin controllers in module infrastructure.")
    [void]$lines.Add("- Orchestration path: Controllers delegate mutations/queries to application services and contracts.")
    [void]$lines.Add("- Persistence path: Services persist through module repositories implementing domain interfaces.")

    if ($eventFiles.Count -gt 0) {
        [void]$lines.Add("- Event publication sources:")
        foreach ($f in $eventFiles) {
            $rel = Get-RelPath $f.FullName
            [void]$lines.Add("  - [$rel]($rel)")
        }
    }

    if ($listenerFiles.Count -gt 0) {
        [void]$lines.Add("- Event consumption/listener sources:")
        foreach ($f in $listenerFiles) {
            $rel = Get-RelPath $f.FullName
            [void]$lines.Add("  - [$rel]($rel)")
        }
    }

    $appendBlock = [string]::Join([Environment]::NewLine, $lines)
    Set-Content -Path $docPath -Value ($docContent + $appendBlock) -Encoding utf8
}
