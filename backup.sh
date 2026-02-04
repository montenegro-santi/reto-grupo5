#!/bin/bash
 
# Variables RDS
endpoint="$ENDPOINT_RDS"
user="admin"
db="wordpress"
password="Grupo5RetoAgl"
fecha=$(date +"%Y-%m-%d_%H-%M")
archivo="backup_${fecha}.sql"
 
# Variables S3
bucket="$BUCKET_S3"
 
# Descarga con mysqldump
if mysqldump -h "$endpoint" -u "$user" -p"$password" --databases "$db" --set-gtid-purged=OFF --single-transaction --no-tablespaces > "$archivo"; then
    echo "[+] Dumpeo de base de datos $db exitoso: $archivo"
 
    # Subida de backup a bucket usando la variable correcta
    echo "[*] Subiendo a S3..."
    aws s3 cp "$archivo" "s3://$bucket/$archivo"
 
    if [ $? -eq 0 ]; then
        echo "[+] Proceso completado."
        # rm "$archivo"
    fi
else
    echo "[!] Dumpeo de base de datos $db fallido"
    # Borramos el archivo vacío generado por el error
    rm -f "$archivo"
    exit 1
fi

# Tarea cron (Idenpendiente del script, agregar en crontab con 'crontab -e'):
# 0 3 * * * /home/ubuntu/backup.sh >> /home/ubuntu/backup.log 2>&1 