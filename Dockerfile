FROM php:8.4-fpm

# Instalar dependências do sistema e bibliotecas de desenvolvimento
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    git \
    npm \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar arquivos do projeto
COPY . .

# Ajustar permissões
RUN chown -R www-data:www-data /var/www

EXPOSE 9000

CMD ["php-fpm"]