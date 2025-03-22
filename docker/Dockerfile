FROM php:8.2-cli

# dependências de desenvolvimento PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    wget \
    gnupg \
    ca-certificates \
    lsb-release \
    supervisor \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    pkg-config \
    && docker-php-ext-install mbstring zip exif pcntl

# Symlinks para evitar problemas com supervisord
RUN ln -s /usr/bin/supervisord /usr/local/bin/supervisord && \
    ln -s /usr/bin/supervisorctl /usr/local/bin/supervisorctl

# Cria pasta de logs do Supervisor
RUN mkdir -p /var/log/supervisor

# Instala extensão MongoDB via PECL
RUN pecl channel-update pecl.php.net && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia config do supervisor
COPY supervisord.conf /etc/supervisord.conf

# Define diretório de trabalho
WORKDIR /var/www/html

# Inicia o Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
