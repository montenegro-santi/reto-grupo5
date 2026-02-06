#Requires -Version 5.1
#Requires -Modules ActiveDirectory

[CmdletBinding()]
param(
  [Parameter(Mandatory)]
  [ValidateScript({ Test-Path $_ })]
  [string]$CsvPath,

  [string]$Delimiter = ';',

  # Log en la carpeta del script
  [string]$LogPath = "$PSScriptRoot\create-computers.log"
)

Import-Module ActiveDirectory
$ErrorActionPreference = 'Stop'

function Write-Log {
  param(
    [Parameter(Mandatory)][string]$Action,
    [Parameter(Mandatory)][string]$Result
  )
  $ts = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
  Add-Content -Path $LogPath -Value "[$ts] - $Action - $Result"  # añade al final del fichero [web:305]
}

$dn = (Get-ADDomain).DistinguishedName
$computersContainer = "CN=Computers,$dn"

Write-Log "Inicio ejecución ($CsvPath)" "OK"

$rows = Import-Csv -Path $CsvPath -Delimiter $Delimiter

foreach ($r in $rows) {
  if ([string]::IsNullOrWhiteSpace($r.Equipo)) {
    Write-Log "Validar fila CSV" "SKIP (falta 'Equipo')"
    continue
  }

  $name = $r.Equipo.Trim()

  try {
    $exists = Get-ADComputer -Filter "Name -eq '$name'" -ErrorAction SilentlyContinue
    if ($exists) {
      Write-Log "Crear equipo $name en $computersContainer" "SKIP (ya existe)"
      continue
    }

    # Crea objeto Computer en AD (no une PC real al dominio) [web:236]
    New-ADComputer -Name $name -SamAccountName $name -Path $computersContainer | Out-Null
    Write-Log "Crear equipo $name en $computersContainer" "OK (creado)"
  }
  catch {
    Write-Log "Crear equipo $name en $computersContainer" "ERROR ($($_.Exception.Message))"
  }
}

Write-Log "Fin ejecución ($CsvPath)" "OK"
