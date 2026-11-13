#!/bin/bash
set -e

# Default port to 10000 if not provided by Render
PORT="${PORT:-10000}"

echo "=================================================="
echo " Starting Apache Web Server on Port: ${PORT}"
echo " Render Reverse Proxy & Aiven SSL Enabled"
echo "=================================================="

# Update Apache listening port dynamically
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/Listen 10000/Listen ${PORT}/g" /etc/apache2/ports.conf 2>/dev/null || true

# Configure VirtualHost with Reverse Proxy protections
cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${PORT}>
    ServerName localhost
    DocumentRoot /var/www/html

    UseCanonicalName Off
    UseCanonicalPhysicalPort Off

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Identify HTTPS through Render reverse proxy
    SetEnvIf X-Forwarded-Proto "^https$" HTTPS=on

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Ensure AllowOverride All in apache2.conf for .htaccess
sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Verify apache configuration syntax before starting
apache2ctl -t || true

# Run Apache in foreground
exec apache2-foreground
