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
        $confirm = Read-Host "ADVERTENCIA: Se borraran TODOS los usuarios y grupos de '$targetOU'. Proceder? (S/N)"
        if ($confirm -eq "S" -or $confirm -eq "s") {
            Write-Host "Limpiando infraestructura..." -ForegroundColor Yellow
            
            # 1. Desbloqueamos la protecci�n contra borrado accidental de la OU y sus subcarpetas
           Get-ADOrganizationalUnit -Identity $targetOU | 
                Set-ADObject -ProtectedFromAccidentalDeletion $false
            
            # 2. Borramos la OU ra�z y todo lo que tiene dentro (Recursivo)
            Remove-ADOrganizationalUnit -Identity $targetOU -Recursive -Confirm:$false
            
            Write-Host "Rollback completado. El dominio esta limpio." -ForegroundColor Green
        }
    } else {
        Write-Host "No se encontro la carpeta 'Empresa'. El dominio ya esta limpio." -ForegroundColor Gray
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
    Write-Host "12. Restaurar todo desde backup"
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
        Write-Host "--- DESPLIEGUE TOTAL: ASEGURANDO ESTRUCTURA Y RAMAS ---" -ForegroundColor Cyan
        $dominioDN = (Get-ADDomain).DistinguishedName
        $departamentos = @("Finanzas", "IT", "RRHH", "Soporte", "Ventas")

        # --- NIVEL 1: ASEGURAR ESTRUCTURA BASE ---
        $rutaEmpresa = "OU=Empresa,$dominioDN"
        if (-not (Get-ADOrganizationalUnit -Filter "DistinguishedName -eq '$rutaEmpresa'" -ErrorAction SilentlyContinue)) {
            New-ADOrganizationalUnit -Name "Empresa" -Path $dominioDN
            Write-Host "[+] Creada OU: Empresa" -ForegroundColor Green
        }

        $rutaEquiposBase = "OU=Equipos,$rutaEmpresa"
        if (-not (Get-ADOrganizationalUnit -Filter "DistinguishedName -eq '$rutaEquiposBase'" -ErrorAction SilentlyContinue)) {
            New-ADOrganizationalUnit -Name "Equipos" -Path $rutaEmpresa
            Write-Host "[+] Creada OU: Equipos" -ForegroundColor Green
        }

        # --- NIVEL 2: PROCESAR CADA RAMA ---
        foreach ($dep in $departamentos) {
            $pathDep = "OU=$dep,$rutaEquiposBase"
            Write-Host "`n> Configurando Rama: $dep" -ForegroundColor Yellow

            # Asegurar que la sub-OU del departamento existe (Evita el error 'Object Not Found')
            if (-not (Get-ADOrganizationalUnit -Filter "DistinguishedName -eq '$pathDep'" -ErrorAction SilentlyContinue)) {
                try {
                    New-ADOrganizationalUnit -Name $dep -Path $rutaEquiposBase
                    Write-Host "  [+] Rama $dep creada con exito." -ForegroundColor Green
                    Start-Sleep -Milliseconds 300 # Pausa técnica para sincronización de AD
                } catch {
                    Write-Host "  [!] Error critico al crear carpeta $dep: $($_.Exception.Message)" -ForegroundColor Red
                    continue # Salta al siguiente departamento si este falla
                }
            }

            # Lógica de nombre (Parche IT incluido)
            $prefijo = if ($dep.Length -ge 3) { $dep.Substring(0,3).ToUpper() } else { $dep.ToUpper() }

            # --- NIVEL 3: CREAR/MOVER EQUIPOS ---
            for ($i = 1; $i -le 4; $i++) {
                $nombrePC = "${prefijo}-PC0$i"
                try {
                    $objPC = Get-ADComputer -Filter "Name -eq '$nombrePC'" -ErrorAction SilentlyContinue
                    
                    if (-not $objPC) {
                        # No existe, lo creamos
                        New-ADComputer -Name $nombrePC -SamAccountName "${nombrePC}$" -Path $pathDep -Enabled $true
                        Write-Host "    [NUEVO] ${nombrePC} creado en ${dep}" -ForegroundColor Gray
                    } 
                    elseif ($objPC.DistinguishedName -notlike "*$pathDep*") {
                        # Existe pero mal ubicado, lo movemos
                        Move-ADObject -Identity $objPC.DistinguishedName -TargetPath $pathDep
                        Write-Host "    [MOVIDO] ${nombrePC} reubicado en ${dep}" -ForegroundColor Blue
                    }
                    else {
                        Write-Host "    [OK] ${nombrePC} ya esta en su sitio." -ForegroundColor DarkGray
                    }
                } catch {
                    # Usamos ${} para evitar errores de sintaxis con los dos puntos (:)
                    Write-Host "    [!] Error en ${nombrePC}: $($_.Exception.Message)" -ForegroundColor Red
                }
            }
        }
        Write-Host "`n--- PROCESO FINALIZADO CON EXITO ---" -ForegroundColor Green
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
        "10"{
            $fecha = Get-Date -Format "yyyyMMdd_HHmm"
            $rutaFinal = "$PSScriptRoot\Backup_Total_$fecha.csv"
            
            Write-Host "Generando Backup Total con Jerarquía y Membresías..." -ForegroundColor Yellow
            
            # Capturamos todos los objetos con sus propiedades extendidas (incluyendo a qué grupos pertenecen)
            $objetos = Get-ADObject -Filter * -Properties Name, ObjectClass, DistinguishedName, SamAccountName, MemberOf, DisplayName | 
                       Select-Object Name, ObjectClass, DistinguishedName, SamAccountName, DisplayName, 
                       @{Name='Groups'; Expression={$_.MemberOf -join ';'}} # Guardamos los grupos separados por ';'

            # Orden jerárquico vital: 1º OUs, 2º Grupos, 3º Usuarios/Equipos
            $objetos | Sort-Object {
                if ($_.ObjectClass -eq "organizationalUnit") { 1 }
                elseif ($_.ObjectClass -eq "group") { 2 }
                else { 3 }
            } | Export-Csv -Path $rutaFinal -NoTypeInformation -Encoding UTF8
            
            Write-Host "Backup completado en: $rutaFinal" -ForegroundColor Green
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
            Write-Host "--- INICIANDO RESTAURACION TOTAL DESDE BACKUP ---" -ForegroundColor Cyan
            $backupFile = Get-ChildItem -Path "$PSScriptRoot\Backup_Total_*.csv" | Sort-Object LastWriteTime -Descending | Select-Object -First 1

            if ($backupFile) {
                Write-Host "Cargando archivo: $($backupFile.Name)" -ForegroundColor Yellow
                $datos = Import-Csv -Path $backupFile.FullName -Encoding UTF8
                $dominioDN = (Get-ADDomain).DistinguishedName

                # --- PASO 0: ASEGURAR ESTRUCTURA BASE (Evita errores de 'Object Not Found') ---
                # Forzamos la creación de la raíz para que las sub-OUs tengan donde apoyarse
                $nombresBase = @("Empresa", "Equipos", "Usuarios", "Grupos")
                foreach ($nombre in $nombresBase) {
                    $rutaPadre = if ($nombre -eq "Empresa") { $dominioDN } else { "OU=Empresa,$dominioDN" }
                    $dnCompleto = if ($nombre -eq "Empresa") { "OU=Empresa,$dominioDN" } else { "OU=$nombre,OU=Empresa,$dominioDN" }
                    
                    if (-not (Get-ADOrganizationalUnit -Filter "DistinguishedName -eq '$dnCompleto'" -ErrorAction SilentlyContinue)) {
                        New-ADOrganizationalUnit -Name $nombre -Path $rutaPadre -ErrorAction SilentlyContinue
                        Write-Host "[+] Estructura base asegurada: $nombre" -ForegroundColor Green
                    }
                }

                # --- PASO 1: Restaurar OUs de los Departamentos ---
                Write-Host "[1/4] Restaurando OUs de Departamentos..." -ForegroundColor White
                $datos | Where-Object { $_.ObjectClass -eq "organizationalUnit" -and $_.Name -notin $nombresBase } | ForEach-Object {
                    if (-not (Get-ADOrganizationalUnit -Filter "DistinguishedName -eq '$($_.DistinguishedName)'" -ErrorAction SilentlyContinue)) {
                        $padreDN = $_.DistinguishedName -replace "^OU=[^,]+,",""
                        try {
                            New-ADOrganizationalUnit -Name $_.Name -Path $padreDN -ErrorAction SilentlyContinue
                            Write-Host "  [OK] OU: $($_.Name)" -ForegroundColor Gray
                        } catch { }
                    }
                }

                # --- PASO 2: Restaurar Grupos ---
                Write-Host "[2/4] Restaurando Grupos..." -ForegroundColor White
                $datos | Where-Object { $_.ObjectClass -eq "group" } | ForEach-Object {
                    if (-not (Get-ADGroup -Filter "SamAccountName -eq '$($_.SamAccountName)'" -ErrorAction SilentlyContinue)) {
                        $padreDN = $_.DistinguishedName -replace "^CN=[^,]+,",""
                        New-ADGroup -Name $_.Name -SamAccountName $_.SamAccountName -GroupScope Global -Path $padreDN -ErrorAction SilentlyContinue
                    }
                }

                # --- PASO 3: Usuarios con Membresía de Grupo ---
                Write-Host "[3/4] Restaurando Usuarios y Membresías..." -ForegroundColor White
                $datos | Where-Object { $_.ObjectClass -eq "user" -and $_.SamAccountName -ne "Administrator" } | ForEach-Object {
                    $u = $_
                    if (-not (Get-ADUser -Filter "SamAccountName -eq '$($u.SamAccountName)'" -ErrorAction SilentlyContinue)) {
                        $padreDN = $u.DistinguishedName -replace "^CN=[^,]+,",""
                        $pass = ConvertTo-SecureString "Password2026!" -AsPlainText -Force
                        New-ADUser -Name $u.Name -SamAccountName $u.SamAccountName -Path $padreDN -AccountPassword $pass -Enabled $true -ErrorAction SilentlyContinue
                    }
                    # Restaurar grupos si la columna 'Groups' existe en tu backup mejorado
                    if ($u.Groups) {
                        $u.Groups -split ';' | ForEach-Object {
                            try { Add-ADGroupMember -Identity $_ -Members $u.SamAccountName -ErrorAction SilentlyContinue } catch {}
                        }
                    }
                }

                # --- PASO 4: Restaurar Equipos ---
                Write-Host "[4/4] Restaurando Equipos..." -ForegroundColor White
                $datos | Where-Object { $_.ObjectClass -eq "computer" } | ForEach-Object {
                    if (-not (Get-ADComputer -Filter "Name -eq '$($_.Name)'" -ErrorAction SilentlyContinue)) {
                        $padreDN = $_.DistinguishedName -replace "^CN=[^,]+,",""
                        New-ADComputer -Name $_.Name -SamAccountName "$($_.Name)$" -Path $padreDN -Enabled $true -ErrorAction SilentlyContinue
                        Write-Host "  [OK] Equipo: $($_.Name)" -ForegroundColor Gray
                    }
                }

            } else {
                Write-Host "No se encontró ningún archivo de backup (.csv)." -ForegroundColor Red
            }
            Write-Host "--- RESTAURACION FINALIZADA ---" -ForegroundColor Cyan
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
} while ($opcion -ne "13")
