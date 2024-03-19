# Backend App for PedidosAm


```bash
git clone https://github.com/phyzerbert/pedidos-am-backend.git .
```

#### Install dependency
```bash
composer install
```

```bash
cp .env.example .env
```

```bash
php artisan key:generate
```

```bash
php artisan jwt:secret
```

```bash
php artisan storage:link
```


#### Add .htaccess file with this code
```
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^public
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```
