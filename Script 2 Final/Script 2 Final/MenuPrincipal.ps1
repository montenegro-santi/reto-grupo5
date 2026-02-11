#Requires -Modules ActiveDirectory
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# --- DETECCI�N AUTOM�TICA DEL DOMINIO ---
try {
    $currentDomain = Get-ADDomain
    $domainDN = $currentDomain.DistinguishedName      # Detecta "DC=Dominio, DC=Local"
    $upnSuffix = "@" + $currentDomain.DnsRoot         # Detecta "@dominio.local"
    $dominioNombre = $currentDomain.DnsRoot
} catch {
    Write-Host "ERROR: No se pudo detectar un dominio de Active Directory." -ForegroundColor Red
    Pause
    exit
}
function Ejecutar-Rollback {
    # Definimos la ruta e la OU principal bas�ndonos en el dominio detectado
    $targetOU = "OU=Empresa,$domainDN"
    
    # Verificamos si la OU existe antes de intentar borrar
    if (Get-ADOrganizationalUnit -Filter "DistinguishedName -eq '$targetOU'") {
        $confirm = Read-Host "ADVERTENCIA: Se borrar�n TODOS los usuarios y grupos de '$targetOU'. �Proceder? (S/N)"
        if ($confirm -eq "S" -or $confirm -eq "s") {
            Write-Host "Limpiando infraestructura..." -ForegroundColor Yellow
            
            # 1. Desbloqueamos la protecci�n contra borrado accidental de la OU y sus subcarpetas
           Get-ADOrganizationalUnit -Identity $targetOU | 
                Set-ADObject -ProtectedFromAccidentalDeletion $false
            
            # 2. Borramos la OU ra�z y todo lo que tiene dentro (Recursivo)
            Remove-ADOrganizationalUnit -Identity $targetOU -Recursive -Confirm:$false
            
            Write-Host "Rollback completado. El dominio est� limpio." -ForegroundColor Green
        }
    } else {
        Write-Host "No se encontr� la carpeta 'Empresa'. El dominio ya est� limpio." -ForegroundColor Gray
    }
    }

function Mostrar-Menu {
    Clear-Host
    Write-Host "==============================================" -ForegroundColor Green
    Write-Host "   GESTOR AUTOMATICO PARA: $dominioNombre " -ForegroundColor Green
    Write-Host "==============================================" -ForegroundColor Green
    Write-Host "1. Crear Usuarios y Grupos"
    Write-Host "2. Crear Objetos de Equipo"
    Write-Host "3. Organizar Equipos por Departamento"
    Write-Host "4. EJECUTAR TODO EL PROCESO (Full Auto)"
    Write-Host "5. Ver Archivos de LOG"
    Write-Host "6. ROLLBACK (Limpiar Dominio)" -ForegroundColor Red
    Write-Host "7. Ver salud de los servicios (DNS/AD)"
    Write-Host "8. Reporte salud del servidor (HTML)"
    Write-Host "9. Listar usuarios inactivos (30 dias)"
    Write-Host "10. Crear copia de seguridad de usuarios"
    Write-Host "11. Auditoria proactiva de seguridad"
    Write-Host "12. Restaurar usuarios desde backup"
    Write-Host "13. Salir"
    Write-Host "=============================================="
}

do {
    Mostrar-Menu
    $opcion = Read-Host "Selecciona una opcion"

    switch ($opcion) {
        "1" { 
            .\Creausuarios.ps1 -CsvPath ".\usuarios.csv" -DomainDN $domainDN -UpnSuffix $upnSuffix
            Pause
        }
        "2" { 
            .\Computers.ps1 -CsvPath ".\equipos.csv"
            Pause
        }
        "3" { 
            .\moverequipos.ps1
            Pause
        }
        "4" {
            Write-Host "Iniciando automatizaci�n total en $dominioNombre..." -ForegroundColor Yellow
            .\Creausuarios.ps1 -CsvPath ".\usuarios.csv" -DomainDN $domainDN -UpnSuffix $upnSuffix
            .\Computers.ps1 -CsvPath ".\equipos.csv"
            .\moverequipos.ps1
            Write-Host "�Dominio configurado con �xito!" -ForegroundColor Green
            Pause
        }
        "5" {
            Write-Host "Abriendo archivos de registro..." -ForegroundColor Cyan
            if (Test-Path ".\provision-ad.log") { notepad ".\provision-ad.log" }
            if (Test-Path ".\create-computers.log") { notepad ".\create-computers.log" }
            if (Test-Path ".\move-computers.log") { notepad ".\move-computers.log" }
            Pause
        }
        "6" { 
            # Aqu� es donde llamamos a la funci�n que ten�a la raya debajoa
            Ejecutar-Rollback
            Pause 
        }
       "7" { 
            Write-Host "`n--- ESTADO DE SALUD DEL SERVIDOR ---" -ForegroundColor Cyan
            # Verificamos los 3 pilares de Active Directorya
            $servicios = @("dns", "adws", "ntds")
            foreach ($s in $servicios) {
                $status = Get-Service $s -ErrorAction SilentlyContinue
                if ($status) {
                    $color = if ($status.Status -eq "Running") { "Green" } else { "Red" }
                    Write-Host "$($status.DisplayName): [$($status.Status)]" -ForegroundColor $color
                } else {
                    Write-Host "Servicio ${s}: [NO ENCONTRADO]" -ForegroundColor Yellow
                }
            }
            Write-Host "-------------------------------------"
            Pause 
        }
        "8" { 
            $reportPath = "$PSScriptRoot\Reporte_Salud.html"
            $servicios = Get-Service dns, adws, ntds
            $html = $servicios | Select-Object Name, DisplayName, Status | ConvertTo-Html -Title "Reporte de AD"
            $html | Out-File $reportPath
            Write-Host "Reporte generado en: $reportPath" -ForegroundColor Green
            Pause
        }
        "9" {
           Write-Host "Buscando usuarios inactivos (30 dias)..." -ForegroundColor Yellow
            $fecha = (Get-Date).AddDays(-30)
            $inactivos = Get-ADUser -Filter "LastLogonDate -lt '$fecha'" -Properties LastLogonDate
            if ($inactivos) {
            $inactivos | Select-Object Name, SamAccountName, LastLogonDate | Format-Table
            } else {
                Write-Host "No hay usuarios inactivos. ¡Todos estan trabajando!" -ForegroundColor Green
            }
            Pause
            }   
        "10" {
             $fecha = Get-Date -Format "yyyyMMdd_HHmm"
            Get-ADUser -Filter * | Export-Csv "$PSScriptRoot\Backup_Users_$fecha.csv"
            Write-Host "Copia de seguridad de usuarios creada." -ForegroundColor Green
            Pause
            }
        "11"{
            Write-Host "`n--- AUDITORÍA PROACTIVA DE SEGURIDAD ---" -ForegroundColor Cyan -BackgroundColor Black
    
            # 1. Detectar usuarios con contraseñas que NUNCA caducan (Riesgo de seguridad)
            Write-Host "[!] Analizando contraseñas permanentes..." -ForegroundColor Yellow
            $passNeverExpires = Get-ADUser -Filter 'PasswordNeverExpires -eq $true' | Select-Object -ExpandProperty SamAccountName
            if ($passNeverExpires) {
            Write-Host "    ALERTA: Se han encontrado usuarios con contraseñas que no caducan." -ForegroundColor Red
            Write-Host "    Usuarios: $passNeverExpires"
            $fix = Read-Host "¿Deseas corregirlo ahora y obligar a que caduquen? (S/N)"
            if ($fix -eq "S") {
            $passNeverExpires | Set-ADUser -PasswordNeverExpires $false
            Write-Host "    [OK] Seguridad corregida." -ForegroundColor Green
            }
            } else {
                Write-Host "    [OK] No hay contraseñas permanentes peligrosas." -ForegroundColor Green
            }

            # 2. Buscar grupos vacíos (Limpieza de AD)
            Write-Host "`n[!] Buscando grupos sin miembros..." -ForegroundColor Yellow
            $emptyGroups = Get-ADGroup -Filter * | Where-Object { -not (Get-ADGroupMember -Identity $_.DistinguishedName) }
            if ($emptyGroups) {
            Write-Host "    Se han encontrado $($emptyGroups.Count) grupos vacios." -ForegroundColor Cyan
            $emptyGroups | Select-Object Name | Format-Table
            }

            # 3. Verificar si la Papelera de Reciclaje de AD está activa
            Write-Host "[!] Verificando Papelera de Reciclaje de Active Directory..." -ForegroundColor Yellow
            $features = Get-ADOptionalFeature -Filter 'Name -like "Recycle Bin Feature"'
            if ($features.EnabledScopes) {
            Write-Host "    [OK] La Papelera de Reciclaje esta ACTIVA (Protección contra borrados accidentales)." -ForegroundColor Green
            } else {
                Write-Host "    [PELIGRO] La Papelera de Reciclaje está DESACTIVADA." -ForegroundColor Red
            }

            Pause
            }
        "12"{
            Write-Host "--- RESTAURANDO USUARIOS DESDE BACKUP ---" -ForegroundColor Cyan
    
            # 1. Buscar el archivo más reciente en la carpeta
            $archivoBackup = Get-ChildItem -Path "$PSScriptRoot\Backup_Users_*.csv" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    
            if (-not $archivoBackup) {
            Write-Host "Error: No se ha encontrado ningún archivo de backup en $PSScriptRoot" -ForegroundColor Red
            } else {
            Write-Host "Usando el backup más reciente: $($archivoBackup.Name)" -ForegroundColor Yellow
            $confirmar = Read-Host "¿Estás seguro de que quieres importar estos usuarios? (S/N)"
        
            if ($confirmar -eq "S") {
            $usuarios = Import-Csv -Path $archivoBackup.FullName
            
            foreach ($u in $usuarios) {
                # Verificamos si el usuario ya existe para no dar error
                if (-not (Get-ADUser -Filter "SamAccountName -eq '$($u.SamAccountName)'")) {
                    Write-Host "Restaurando usuario: $($u.SamAccountName)..." -ForegroundColor Gray
                    
                    # Creamos el usuario con los datos básicos del CSV
                    # Nota: La contraseña no se guarda en el CSV por seguridad, pondremos una por defecto
                    $password = ConvertTo-SecureString "Password2026!" -AsPlainText -Force
                    
                    New-ADUser -Name $u.Name `
                               -SamAccountName $u.SamAccountName `
                               -UserPrincipalName $u.UserPrincipalName `
                               -AccountPassword $password `
                               -Enabled $true `
                               -DisplayName $u.DisplayName
                               
                    Write-Host " [OK] $($u.SamAccountName) restaurado." -ForegroundColor Green
                } else {
                    Write-Host " [!] El usuario $($u.SamAccountName) ya existe, saltando..." -ForegroundColor Yellow
                }
                }
                }
                }
                    Pause
                }
        "13"{
            Write-Host "Saliendo del gestor..." -ForegroundColor Gray
            return
        }
        Default { 
            Write-Host "Opcion no valida, intenta de nuevo." -ForegroundColor Red
            Pause
        }
    }
} while ($opcion -ne "12")
