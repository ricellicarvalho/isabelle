FROM php:8.4-fpm

# 1. Instalar dependências do sistema, bibliotecas de desenvolvimento, suporte a idiomas e configurar UTF-8
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
    locales \
    && sed -i -e 's/# pt_BR.UTF-8 UTF-8/pt_BR.UTF-8 UTF-8/' /etc/locale.gen \
    && locale-gen \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

ENV LANG pt_BR.UTF-8
ENV LANGUAGE pt_BR:pt
ENV LC_ALL pt_BR.UTF-8

# 2. Argumentos para bater com o usuário (ID 1000) 
ARG uid=1000
ARG user=dev

# 3. Criar usuário do sistema e pastas para Tinker/Composer 
# Isso evita o erro "Writing to directory /.config/psysh is not allowed"
RUN useradd -G www-data,root -u $uid -d /home/$user $user && \
    mkdir -p /home/$user/.composer /home/$user/.config/psysh && \
    touch /home/$user/.config/psysh/config.php && \
    chown -R $user:$user /home/$user

# 4. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Configurar diretório de trabalho
WORKDIR /var/www

# 6. Copiar arquivos do projeto definindo o novo usuário como dono
COPY --chown=$user:$user . .

# 7. Variáveis de ambiente cruciais
ENV HOME=/home/$user
ENV PSYSH_CONFIG=/home/$user/.config/psysh/config.php
ENV TZ=America/Araguaina

# 8. Mudar para o usuário criado antes de rodar o processo. 
# Essa linha diz ao Docker: "A partir de agora, não use mais o root; use o usuário 1000".
USER $user

EXPOSE 9000

CMD ["php-fpm"]