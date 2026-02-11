#Requires -Version 5.1
#Requires -Modules ActiveDirectory

[CmdletBinding()]
param(
  # Donde van a quedar los equipos:
  # OU=IT,OU=Equipos,OU=Empresa,DC=...
  [string]$BaseOUPath = "Empresa/Equipos",

  # Log en la carpeta del script
  [string]$LogPath = "$PSScriptRoot\move-computers.log",

  # Simulación
  [switch]$WhatIf
)

Import-Module ActiveDirectory
$ErrorActionPreference = 'Stop'

function Write-Log {
  param(
    [Parameter(Mandatory)][string]$Action,
    [Parameter(Mandatory)][string]$Result
  )
  $ts = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
  Add-Content -Path $LogPath -Value "[$ts] - $Action - $Result"
}

# --- helpers para crear OUs si no existen ---
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
    [Parameter(Mandatory)][string]$OUPath,   # ej: Empresa/Equipos/IT
    [Parameter(Mandatory)][string]$BaseDN
  )
  $parts = $OUPath -split '[\\/]' | Where-Object { $_.Trim() -ne '' }
  $parent = $BaseDN
  foreach ($p in $parts) {
    $parent = Ensure-OU -Name $p.Trim() -ParentDN $parent
  }
  return $parent
}

# --- Detectar dominio (vale para cualquier dominio) ---
$domainDN = (Get-ADDomain).DistinguishedName  # detecta DN del dominio [web:69]
$computersContainer = "CN=Computers,$domainDN"

Write-Log "Inicio ejecución (origen: $computersContainer)" "OK"

# Asegurar OU base Empresa/Equipos
$baseOUDN = Ensure-OUPath -OUPath $BaseOUPath -BaseDN $domainDN

# Reglas por prefijo (ajusta si quieres otros patrones)
# IMPORTANTE: usa prefijos que coincidan con tus nombres: IT-PC01, RRHH-PC01, VENT-PC01, SOP-PC01, FIN-PC01
$rules = @(
  @{ Prefix = "IT-";    Dept = "IT" }
  @{ Prefix = "RRHH-";  Dept = "RRHH" }
  @{ Prefix = "VENT-";  Dept = "Ventas" }
  @{ Prefix = "SOP-";   Dept = "Soporte" }
  @{ Prefix = "FIN-";   Dept = "Finanzas" }
)

# Leer equipos solo del contenedor "Computers" [web:226]
$pcs = Get-ADComputer -Filter * -SearchBase $computersContainer

foreach ($pc in $pcs) {

  $dept = $null
  foreach ($r in $rules) {
    if ($pc.Name.StartsWith($r.Prefix)) { $dept = $r.Dept; break }
  }

  if (-not $dept) {
    Write-Log "Evaluar equipo $($pc.Name)" "SKIP (sin regla)"
    continue
  }

  # Asegurar OU del departamento: Empresa/Equipos/<Dept>
  $deptOUDN = Ensure-OUPath -OUPath ("$BaseOUPath/$dept") -BaseDN $domainDN

  if ($WhatIf) {
    Write-Log "Mover $($pc.Name) -> $deptOUDN" "WHATIF"
    continue
  }

  try {
    # Move-ADObject requiere -TargetPath, y debe ser OU o contenedor [web:221]
    Move-ADObject -Identity $pc.DistinguishedName -TargetPath $deptOUDN
    Write-Log "Mover $($pc.Name) -> $deptOUDN" "OK"
  } catch {
    Write-Log "Mover $($pc.Name) -> $deptOUDN" "ERROR ($($_.Exception.Message))"
  }
}

Write-Log "Fin ejecución" "OK"
