# Página web para el ALB
git clone https://github.com/S4ntiHack/Cloud
cd Proyect/web_ec2
mv * /var/www/html
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html