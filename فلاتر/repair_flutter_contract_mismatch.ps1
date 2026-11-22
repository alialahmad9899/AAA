[CmdletBinding()]
param(
    [switch]$BuildDebugApk
)

$ErrorActionPreference = 'Stop'
$RepoOwner = 'alialahmad9899'
$RepoName = 'AAA'
$Branch = 'main'

function Find-FlutterProjectRoot([string]$StartPath) {
    if ([string]::IsNullOrWhiteSpace($StartPath)) { return $null }
    $candidate = (Resolve-Path -LiteralPath $StartPath -ErrorAction SilentlyContinue)
    if (-not $candidate) { return $null }
    $current = $candidate.Path
    while ($current) {
        if ((Test-Path (Join-Path $current 'pubspec.yaml')) -and
            (Test-Path (Join-Path $current 'lib/main.dart'))) {
            return $current
        }
        $parent = Split-Path -Parent $current
        if (-not $parent -or $parent -eq $current) { break }
        $current = $parent
    }
    return $null
}

$ProjectRoot = Find-FlutterProjectRoot $PSScriptRoot
if (-not $ProjectRoot) {
    $ProjectRoot = Find-FlutterProjectRoot (Get-Location).Path
}
if (-not $ProjectRoot) {
    throw 'لم أجد جذر مشروع Flutter. احفظ السكربت داخل wesal_app أو شغّل PowerShell من مجلد يحتوي pubspec.yaml وlib/main.dart.'
}

$Targets = @(
    'lib/main.dart',
    'lib/utils/member_profile_link.dart',
    'lib/services/api_service.dart',
    'lib/services/notification_service.dart',
    'lib/services/startup_manager.dart',
    'lib/screens/chat_detail_screen.dart',
    'lib/screens/home/chats_tab.dart',
    'test/chat_api_contract_test.dart'
)

foreach ($relativePath in ($Targets | Where-Object { $_ -like 'lib/*' })) {
    if (-not (Test-Path (Join-Path $ProjectRoot $relativePath))) {
        throw "الملف المطلوب غير موجود محلياً: $relativePath. لم يتم تعديل أي ملف."
    }
}

# تحقق من جميع العقود قبل استبدال أي ملف، حتى لا نخلط نسخاً من commits مختلفة.
$RequiredPatterns = @{
    'lib/main.dart' = @('MemberProfileLink\.parseProfileUri\(uri\)')
    'lib/utils/member_profile_link.dart' = @('static\s+int\?\s+parseProfileUri\s*\(\s*Uri\s+uri\s*\)')
    'lib/services/api_service.dart' = @(
        'class\s+ChatThreadsPage\b',
        'static\s+Future<\s*ChatThreadsPage\s*>\s+getChatThreads\s*\(',
        'int\s+page\s*=\s*1',
        'int\s+perPage\s*=\s*30',
        'static\s+Future<Map<String,\s*dynamic>>\s+getChatMessages',
        'int\?\s+afterMessageId'
    )
    'lib/services/notification_service.dart' = @(
        'ChatThreadsPage\s+page\s*=\s*await\s+ApiService\.getChatThreads',
        'page:\s*pageNumber',
        'perPage:\s*30'
    )
    'lib/services/startup_manager.dart' = @('chatsPage\.threads')
    'lib/screens/chat_detail_screen.dart' = @('afterMessageId:\s*previousLatestMessageId')
    'lib/screens/home/chats_tab.dart' = @(
        'final\s+ChatThreadsPage\s+page\s*=\s*await\s+ApiService\.getChatThreads\(',
        'for\s*\(final\s+thread\s+in\s+page\.threads\)',
        'final\s+threads\s*=\s*page\.threads\.toList\(\)',
        '_chatPage\s*=\s*page\.currentPage',
        '_hasMoreChats\s*=\s*page\.hasMore'
    )
    'test/chat_api_contract_test.dart' = @(
        'chat list API returns the typed paginated response',
        'incremental message loading keeps the afterMessageId parameter',
        'profile deep links expose the parser used by main.dart'
    )
}

$Headers = @{
    'User-Agent' = 'Wesal-Flutter-Contract-Repair'
    'Accept' = 'application/vnd.github+json'
}

Write-Host "جذر المشروع: $ProjectRoot" -ForegroundColor Cyan
Write-Host 'قراءة commit الحالي من main...' -ForegroundColor Cyan
$CommitInfo = Invoke-RestMethod -Uri "https://api.github.com/repos/$RepoOwner/$RepoName/commits/$Branch" -Headers $Headers
$CommitSha = [string]$CommitInfo.sha
if ($CommitSha -notmatch '^[0-9a-f]{40}$') {
    throw 'تعذر تحديد SHA صالح لآخر commit على main.'
}
Write-Host "المصدر الموحد: $CommitSha" -ForegroundColor Green

$TempRoot = Join-Path ([IO.Path]::GetTempPath()) ("wesal-contract-repair-" + [Guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $TempRoot -Force | Out-Null
$FlutterPrefix = [Uri]::EscapeDataString('فلاتر')
$RawBase = "https://raw.githubusercontent.com/$RepoOwner/$RepoName/$CommitSha/$FlutterPrefix"

try {
    # مرحلة التنزيل والتحقق: لا يتم تعديل ملفات المشروع في هذه المرحلة.
    foreach ($relativePath in $Targets) {
        $segments = @($relativePath -split '/' | ForEach-Object { [Uri]::EscapeDataString($_) })
        $encodedPath = [string]::Join('/', $segments)
        $downloadUri = "$RawBase/$encodedPath"
        $stagedPath = Join-Path $TempRoot $relativePath
        $stagedDirectory = Split-Path -Parent $stagedPath
        New-Item -ItemType Directory -Path $stagedDirectory -Force | Out-Null

        Invoke-WebRequest -Uri $downloadUri -OutFile $stagedPath -Headers $Headers -UseBasicParsing
        if (-not (Test-Path $stagedPath) -or (Get-Item $stagedPath).Length -lt 100) {
            throw "الملف الذي تم تنزيله غير صالح: $relativePath"
        }

        $sourceText = [IO.File]::ReadAllText($stagedPath, [Text.Encoding]::UTF8)
        foreach ($pattern in $RequiredPatterns[$relativePath]) {
            if ($sourceText -notmatch $pattern) {
                throw "فشل تحقق العقد البرمجي في $relativePath. لم يتم استبدال أي ملف."
            }
        }
        Write-Host "تم تنزيل والتحقق: $relativePath" -ForegroundColor Green
    }

    # حفظ نسخ الملفات الحالية خارج المشروع قبل استبدالها.
    $timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $backupBase = if ($env:USERPROFILE) { Join-Path $env:USERPROFILE 'WesalRepairBackups' } else { Join-Path ([IO.Path]::GetTempPath()) 'WesalRepairBackups' }
    $BackupRoot = Join-Path $backupBase $timestamp
    New-Item -ItemType Directory -Path $BackupRoot -Force | Out-Null
    foreach ($relativePath in $Targets) {
        $destination = Join-Path $ProjectRoot $relativePath
        if (Test-Path $destination) {
            $backupPath = Join-Path $BackupRoot $relativePath
            New-Item -ItemType Directory -Path (Split-Path -Parent $backupPath) -Force | Out-Null
            Copy-Item -LiteralPath $destination -Destination $backupPath -Force
        }
    }

    # تثبيت المجموعة كدفعة واحدة؛ وعند فشل أي نسخ تتم استعادة النسخ الاحتياطية.
    try {
        foreach ($relativePath in $Targets) {
            $destination = Join-Path $ProjectRoot $relativePath
            New-Item -ItemType Directory -Path (Split-Path -Parent $destination) -Force | Out-Null
            Copy-Item -LiteralPath (Join-Path $TempRoot $relativePath) -Destination $destination -Force
            Write-Host "تم تحديث: $relativePath" -ForegroundColor Green
        }
    }
    catch {
        foreach ($relativePath in $Targets) {
            $backupPath = Join-Path $BackupRoot $relativePath
            if (Test-Path $backupPath) {
                Copy-Item -LiteralPath $backupPath -Destination (Join-Path $ProjectRoot $relativePath) -Force
            }
        }
        throw
    }

    Write-Host "اكتمل تحديث الملفات السبعة واختبار العقد المحلي. النسخ الاحتياطية: $BackupRoot" -ForegroundColor Cyan
    Write-Host 'بدء تحليل Dart...' -ForegroundColor Cyan
    Push-Location $ProjectRoot
    try {
        & dart analyze --fatal-infos
        if ($LASTEXITCODE -ne 0) {
            throw "فشل dart analyze بالرمز $LASTEXITCODE. الملفات تم تحديثها والنسخ الاحتياطية محفوظة في: $BackupRoot"
        }

        Write-Host 'بدء اختبارات Flutter...' -ForegroundColor Cyan
        & flutter test
        if ($LASTEXITCODE -ne 0) {
            throw "فشلت اختبارات Flutter بالرمز $LASTEXITCODE. الملفات تم تحديثها والنسخ الاحتياطية محفوظة في: $BackupRoot"
        }

        if ($BuildDebugApk) {
            Write-Host 'بدء بناء Android Debug APK...' -ForegroundColor Cyan
            & flutter build apk --debug
            if ($LASTEXITCODE -ne 0) {
                throw "فشل بناء APK بالرمز $LASTEXITCODE. راجع السجل؛ النسخ الاحتياطية محفوظة في: $BackupRoot"
            }
        }
    }
    finally {
        Pop-Location
    }

    Write-Host 'نجح تحليل Dart واختبار Flutter.' -ForegroundColor Green
    if ($BuildDebugApk) { Write-Host 'نجح بناء Android Debug APK.' -ForegroundColor Green }
    Write-Host "تم الإصلاح من commit: $CommitSha" -ForegroundColor Green
}
finally {
    if (Test-Path $TempRoot) { Remove-Item -LiteralPath $TempRoot -Recurse -Force }
}
