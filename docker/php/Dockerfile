FROM php:8.2-fpm

# 1. CONFIGURAR REPOSITORIOS Y DEPENDENCIAS BASE
RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list \
    && apt-get update

# 2. INSTALAR DEPENDENCIAS DEL SISTEMA Y EXTENSIONES PHP
# GD se compila con --with-webp. Sin esa bandera imagewebp() no existe,
# OptimizadorImagen lanza y su plan B guarda la imagen subida tal cual: sin
# redimensionar ni comprimir. El fallo es silencioso -prefiere publicar una
# imagen pesada antes que dar error- y solo se nota en el peso de las paginas.
RUN apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. EXTENSION DE REDIS
# phpredis y no predis: es la que Laravel usa por defecto (REDIS_CLIENT) y
# esta escrita en C. Con predis, cada operacion de cache y de cola pasa por
# PHP, y la cache es justo lo que no debe costar.
RUN pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

# 4. ACTUALIZAR NPM A LA ÚLTIMA VERSIÓN
RUN npm install -g npm@latest \
    && npm cache clean --force

# 5. INSTALAR COMPOSER DESDE LA IMAGEN OFICIAL
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 6. CONFIGURAR DIRECTORIO DE TRABAJO
WORKDIR /var/www/html

# 7. CREAR USUARIO NO-ROOT CON EL UID/GID DEL HOST
# En Linux el bind mount conserva el propietario real de los ficheros, asi que
# si el usuario del host no coincide con el del contenedor, este no puede
# escribir en storage/ ni en bootstrap/cache y la aplicacion revienta al
# primer log. (En Docker Desktop para Windows no pasa: alli todo se ve como
# root y 777, de modo que el problema solo aparece al desplegar.)
#
#   UID=$(id -u) GID=$(id -g) docker compose build
#
# o dejandolos en el .env de la raiz, que es de donde Compose los toma.
ARG UID=1000
ARG GID=1000
RUN groupadd -f -g ${GID} laravel && \
    useradd -u ${UID} -g ${GID} -G www-data,root -d /home/laravel laravel && \
    mkdir -p /home/laravel/.composer && \
    chown -R ${UID}:${GID} /home/laravel

# 8. COPIAR CONFIGURACIÓN PHP PERSONALIZADA
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# 9. PREPARAR vendor/ Y node_modules/ CON EL DUEÑO CORRECTO
# Un volumen nombrado se inicializa copiando lo que la imagen tenga en esa ruta,
# incluido el propietario. Sin estas carpetas, Docker las crea vacías y como
# root, y el usuario `laravel` no puede escribir dentro: `composer install`
# muere con «/var/www/html/vendor/... does not exist and could not be created».
RUN mkdir -p /var/www/html/vendor /var/www/html/node_modules \
    && chown -R ${UID}:${GID} /var/www/html

# 10. CAMBIAR AL USUARIO LARAVEL
USER laravel

# 11. EXPONER PUERTO 9000 PARA PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
