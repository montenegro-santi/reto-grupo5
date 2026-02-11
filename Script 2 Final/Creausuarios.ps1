#Requires -Version 5.1
#Requires -Modules ActiveDirectory
<#
SCRIPT: Provision-AD.ps1
OBJETIVO:
  - Leer usuarios desde CSV.
  - Crear estructura de OUs si no existen.
  - Crear usuarios (SamAccountName = primera letra + apellido, único).
  - Forzar cambio de contraseña en el primer inicio de sesión.
  - Crear un grupo de seguridad por departamento (última OU del OUPath) y añadir el usuario.
  - Generar un .log (append) con formato:
    [FECHA Y HORA] - [ACCIÓN REALIZADA] - [RESULTADO]
#>

[CmdletBinding()]
param(
  [string]$CsvPath,
  [string]$Delimiter = ';',
  [string]$LogPath = "$PSScriptRoot\provision-ad.log",
  [string]$DomainDN,
  [string]$UpnSuffix,
  [string]$DefaultPassword = 'P@ssw0rd!ChangeMe2026',
  [switch]$WhatIf
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ---------------------------
# BLOQUE 1: Logging
# ---------------------------
function Write-Log {
  param(
    [Parameter(Mandatory)][string]$Action,
    [Parameter(Mandatory)][string]$Result
  )
  $ts = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
  Add-Content -Path $LogPath -Value "[$ts] - $Action - $Result"
}

# ---------------------------
# BLOQUE 2: Entrada interactiva si falta CsvPath
# ---------------------------
function Prompt-ForCsvIfMissing {
  if ($script:CsvPath) { return }

  Write-Host "No se indicó -CsvPath. CSVs encontrados en la carpeta actual:"
  Get-ChildItem -File -Filter *.csv | ForEach-Object { Write-Host " - $($_.Name)" }

  $script:CsvPath = Read-Host -Prompt "Escribe el nombre del CSV a usar (ej: usuarios.csv)"
  if (-not (Test-Path $script:CsvPath)) {
    throw "No existe el CSV: $($script:CsvPath)"
  }

  $ans = Read-Host -Prompt "¿Ejecutar en simulación (WhatIf)? (S/N)"
  if ($ans -match '^(s|S)$') { $script:WhatIf = $true }

  $ans2 = Read-Host -Prompt "Delimitador del CSV (ENTER para ';' o escribe ',' etc.)"
  if ($ans2) { $script:Delimiter = $ans2 }
}

# ---------------------------
# BLOQUE 3: Utilidades texto/login
# ---------------------------
function Remove-Diacritics {
  param([Parameter(Mandatory)][string]$Text)
  $norm = $Text.Normalize([Text.NormalizationForm]::FormD)
  $sb = New-Object System.Text.StringBuilder
  foreach ($ch in $norm.ToCharArray()) {
    if ([Globalization.CharUnicodeInfo]::GetUnicodeCategory($ch) -ne [Globalization.UnicodeCategory]::NonSpacingMark) {
      [void]$sb.Append($ch)
    }
  }
  $sb.ToString().Normalize([Text.NormalizationForm]::FormC)
}

function Normalize-Login {
  param([Parameter(Mandatory)][string]$Text)
  $t = (Remove-Diacritics $Text).ToLower()
  -join ($t.ToCharArray() | Where-Object { $_ -match '[a-z0-9]' })
}

function Get-UniqueSamAccountName {
  param([Parameter(Mandatory)][string]$BaseSam)

  $sam = (Normalize-Login $BaseSam)
  if (-not (Get-ADUser -Filter "SamAccountName -eq '$sam'" -ErrorAction SilentlyContinue)) { return $sam }

  for ($i = 1; $i -lt 1000; $i++) {
    $candidate = "$sam$i"
    if (-not (Get-ADUser -Filter "SamAccountName -eq '$candidate'" -ErrorAction SilentlyContinue)) { return $candidate }
  }
  throw "No se pudo generar SamAccountName único para base '$BaseSam'."
}

# ---------------------------
# BLOQUE 4: OUs
# ---------------------------
function Ensure-OU {
  param(
    [Parameter(Mandatory)][string]$Name,
    [Parameter(Mandatory)][string]$ParentDN
  )
  $ouDN = "OU=$Name,$ParentDN"
  try {
    Get-ADOrganizationalUnit -Identity $ouDN -ErrorAction Stop | Out-Null
    Write-Log "Comprobar OU $ouDN" "OK (existe)"
  } catch {
    if ($WhatIf) { Write-Log "Crear OU $ouDN" "WHATIF"; return $ouDN }
    New-ADOrganizationalUnit -Name $Name -Path $ParentDN -ProtectedFromAccidentalDeletion $true | Out-Null
    Write-Log "Crear OU $ouDN" "OK (creada)"
  }
  return $ouDN
}

function Ensure-OUPath {
  param(
    [Parameter(Mandatory)][string]$OUPath,
    [Parameter(Mandatory)][string]$BaseDN
  )
  $parts = $OUPath -split '[\\/]' | Where-Object { $_.Trim() -ne '' }
  $parent = $BaseDN
  foreach ($p in $parts) {
    $parent = Ensure-OU -Name $p.Trim() -ParentDN $parent
  }
  return $parent
}

# ---------------------------
# BLOQUE 5: Grupos de seguridad por departamento
# ---------------------------
function Ensure-DeptSecurityGroup {
  param(
    [Parameter(Mandatory)][string]$DepartmentName,
    [Parameter(Mandatory)][string]$GroupPathDN
  )

  $safeDept = (Normalize-Login $DepartmentName)
  if (-not $safeDept) { $safeDept = "dept" }

  $groupName = "GS_$safeDept"   # ej: GS_it, GS_rrhh

  try {
    Get-ADGroup -Identity $groupName -ErrorAction Stop | Out-Null
    Write-Log "Comprobar grupo $groupName" "OK (existe)"
  } catch {
    if ($WhatIf) {
      Write-Log "Crear grupo $groupName en $GroupPathDN" "WHATIF"
    } else {
      New-ADGroup -Name $groupName -SamAccountName $groupName -GroupScope Global -GroupCategory Security -Path $GroupPathDN -Description "Grupo de seguridad para $DepartmentName" | Out-Null
      Write-Log "Crear grupo $groupName en $GroupPathDN" "OK (creado)"
    }
  }

  return $groupName
}

# ---------------------------
# BLOQUE 6: Autodetección dominio
# ---------------------------
Prompt-ForCsvIfMissing

try {
  if (-not $DomainDN)  { $DomainDN  = (Get-ADDomain).DistinguishedName }
  if (-not $UpnSuffix) { $UpnSuffix = (Get-ADDomain).DNSRoot }
} catch {
  throw "No se pudo detectar el dominio con Get-ADDomain."
}

# ---------------------------
# BLOQUE 7: Ejecución principal
# ---------------------------
Write-Log "Inicio ejecución ($CsvPath)" "OK"

$rows = Import-Csv -Path $CsvPath -Delimiter $Delimiter

foreach ($r in $rows) {

  if ([string]::IsNullOrWhiteSpace($r.OUPath) -or
      [string]::IsNullOrWhiteSpace($r.Nombre) -or
      [string]::IsNullOrWhiteSpace($r.Apellido)) {
    Write-Log "Validar fila CSV" "ERROR (faltan OUPath/Nombre/Apellido)"
    continue
  }

  # 1) OU destino
  $targetOuDN = Ensure-OUPath -OUPath $r.OUPath -BaseDN $DomainDN

  # 2) Grupo de seguridad por departamento
  $deptName  = ($r.OUPath -split '[\\/]')[-1].Trim()
  $groupName = Ensure-DeptSecurityGroup -DepartmentName $deptName -GroupPathDN $targetOuDN

  # 3) Login
  $baseSam = ("{0}{1}" -f $r.Nombre.Substring(0,1), $r.Apellido)
  $sam     = Get-UniqueSamAccountName -BaseSam $baseSam
  $display = "$($r.Nombre) $($r.Apellido)"
  $upn     = "$sam@$UpnSuffix"

  # 4) Password
  $plainPwd = if ([string]::IsNullOrWhiteSpace($r.Password)) { $DefaultPassword } else { [string]$r.Password }
  $secPwd   = ConvertTo-SecureString $plainPwd -AsPlainText -Force

  # 5) Usuario (crear o reutilizar existente) + añadir al grupo
  $userObj = $null

  # Comprobar si ya existe por UPN o Sam antes de crear
  $userObj = Get-ADUser -Filter "UserPrincipalName -eq '$upn' -or SamAccountName -eq '$sam'" -ErrorAction SilentlyContinue
  if ($userObj) {
    Write-Log "Comprobar usuario $sam ($upn)" "SKIP (ya existe como $($userObj.SamAccountName))"
  }
  elseif ($WhatIf) {
    Write-Log "Crear usuario $sam ($upn) en $targetOuDN" "WHATIF"
  }
  else {
    try {
      New-ADUser `
        -Name $display `
        -GivenName $r.Nombre `
        -Surname $r.Apellido `
        -DisplayName $display `
        -SamAccountName $sam `
        -UserPrincipalName $upn `
        -Path $targetOuDN `
        -AccountPassword $secPwd `
        -Enabled $true `
        -ChangePasswordAtLogon $true

      Write-Log "Crear usuario $sam ($upn) en $targetOuDN" "OK (creado)"

      $userObj = Get-ADUser -Filter "UserPrincipalName -eq '$upn'" -ErrorAction SilentlyContinue
      if (-not $userObj) {
        $userObj = Get-ADUser -Filter "SamAccountName -eq '$sam'" -ErrorAction SilentlyContinue
      }
    }
    catch {
      Write-Log "Crear usuario $sam ($upn) en $targetOuDN" "ERROR ($($_.Exception.Message))"
      # Intentar localizarlo igualmente por si el error es “ya existe”
      $userObj = Get-ADUser -Filter "UserPrincipalName -eq '$upn' -or SamAccountName -eq '$sam'" -ErrorAction SilentlyContinue
      if ($userObj) {
        Write-Log "Buscar usuario existente ($upn / $sam)" "OK (encontrado: $($userObj.SamAccountName))"
      } else {
        Write-Log "Buscar usuario existente ($upn / $sam)" "ERROR (no encontrado)"
        continue
      }
    }
  }

  # Si estamos en WhatIf, solo registraríamos
  if ($WhatIf) {
    Write-Log "Añadir $sam al grupo $groupName" "WHATIF"
    continue
  }

  if ($userObj) {
    try {
      Add-ADGroupMember -Identity $groupName -Members $userObj.SamAccountName -ErrorAction Stop
      Write-Log "Añadir $($userObj.SamAccountName) al grupo $groupName" "OK"
    }
    catch {
      Write-Log "Añadir $($userObj.SamAccountName) al grupo $groupName" "ERROR ($($_.Exception.Message))"
    }
  }
}

Write-Log "Fin ejecución ($CsvPath)" "OK"

