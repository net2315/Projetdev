# Guide d'installation et de configuration du système de gestion de stock

## Prérequis

- Machine serveur (Linux recommandé)
- Machine client PC
- PHP 8.1 ou supérieur
- Composer
- MySQL 5.7 ou supérieur
- Apache ou Nginx
- Git

## 1. Configuration de la machine serveur

### Installation des dépendances sur Ubuntu/Debian

```bash
# Mettre à jour les dépôts
sudo apt update
sudo apt upgrade -y

# Installer Apache
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

# Installer PHP et extensions nécessaires
sudo apt install php8.1 php8.1-cli php8.1-common php8.1-curl php8.1-mbstring php8.1-mysql php8.1-xml php8.1-zip php8.1-gd -y

# Installer MySQL
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo systemctl start mysql

# Sécuriser l'installation MySQL
sudo mysql_secure_installation

# Installer Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Configuration de MySQL

```bash
# Se connecter à MySQL
sudo mysql

# Créer un utilisateur et une base de données
CREATE DATABASE stock_management;
CREATE USER 'stockadmin'@'localhost' IDENTIFIED BY 'YourSecurePassword';
GRANT ALL PRIVILEGES ON stock_management.* TO 'stockadmin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Configuration d'Apache

```bash
# Créer un virtual host pour l'application
sudo nano /etc/apache2/sites-available/stock.conf
```

Ajoutez le contenu suivant:

```apache
<VirtualHost *:80>
    ServerName stock.local
    DocumentRoot /var/www/stock-management/public
    
    <Directory /var/www/stock-management/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/stock-error.log
    CustomLog ${APACHE_LOG_DIR}/stock-access.log combined
</VirtualHost>
```

```bash
# Activer le site et le module rewrite
sudo a2ensite stock.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## 2. Installation du projet

### Cloner le dépôt

```bash
cd /var/www
sudo git clone [URL_DU_DEPOT] stock-management
cd stock-management
sudo chown -R $USER:www-data .
sudo chmod -R 775 storage bootstrap/cache
```

### Installation des dépendances

```bash
composer install
```

### Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

Éditez le fichier .env:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stock_management
DB_USERNAME=stockadmin
DB_PASSWORD=YourSecurePassword
```

### Migration et seeding de la base de données

```bash
php artisan migrate
php artisan db:seed
```

### Paramétrage des droits d'accès

```bash
sudo chown -R www-data:www-data /var/www/stock-management
sudo chmod -R 755 /var/www/stock-management
sudo chmod -R 777 /var/www/stock-management/storage
```

## 3. Configuration du client

Sur votre machine cliente, ajoutez une entrée DNS dans le fichier hosts:

- Windows : `C:\Windows\System32\drivers\etc\hosts`
- Linux/Mac : `/etc/hosts`

Ajoutez la ligne suivante:
```
[IP_DU_SERVEUR] stock.local
```

## 4. Accès au projet

- URL: http://stock.local
- Admin par défaut:
  - Utilisateur: admin@example.com
  - Mot de passe: password

## 5. Utilisation du système

### Fonctionnalités Admin
- Accès au tableau de bord via "Dashboard"
- Gestion des produits: Ajout, modification, suppression
- Gestion des catégories
- Gestion des utilisateurs
- Consultation des commandes
- Utilisation de l'authentification à deux facteurs

### Fonctionnalités Client
- Parcourir le catalogue de produits
- Rechercher et filtrer les produits
- Ajouter des produits au panier
- Passer des commandes
- Consulter l'historique des commandes

## 6. Double authentification (OTP)

Une fois connecté en tant qu'administrateur, activez la double authentification:

1. Naviguez vers "Profile" > "Security"
2. Cliquez sur "Enable Two-Factor Authentication"
3. Scannez le code QR avec Google Authenticator ou une application similaire
4. Entrez le code généré pour confirmer

## 7. Dépannage courant

### Problèmes de permission
```bash
sudo chown -R www-data:www-data /var/www/stock-management
sudo chmod -R 755 /var/www/stock-management
sudo chmod -R 777 /var/www/stock-management/storage bootstrap/cache
```

### Rafraîchir la configuration Laravel
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Problèmes de base de données
```bash
php artisan migrate:fresh --seed
```
