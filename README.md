# 🚀 Sistema Instituto Alves Neves 

Este repositório é um guia passo a passo para configurar o sistemapara rodar localmente em modo desenvolvimento.

## ✅ Pré-requisitos

- [Git](https://git-scm.com/)
- [Docker](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/install/)

---

## 📦 Passo a passo para subir o projeto

### 1. Clone o repositório do Laravel 12

```bash
git clone --branch 12.x --single-branch https://github.com/laravel/laravel.git laravelapp
````

### 2. Clone o repositório com a configuração Docker

```bash
git clone --branch laravel-12 https://github.com/felipe-rodolfo/docker-laravel
```

### 3. Copie os arquivos de Docker para dentro da pasta do Laravel

```bash
cp -r docker-laravel/* laravelapp/
```

### 4. Acesse o diretório do projeto Laravel

```bash
cd laravelapp
```

### 5. Crie o arquivo `.env` a partir do exemplo

```bash
cp .env.example .env
```

### 6. Altere as variáveis do banco de dados no arquivo `.env`

Edite o `.env` e configure a conexão com MySQL:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysqldb
DB_PORT=3306
DB_DATABASE=xxxxxx
DB_USERNAME=xxxxx
DB_PASSWORD=xxxxxx
```

> 💡 Essas credenciais devem corresponder ao que está definido no `docker-compose.yml`.

---

## 🐳 Suba os containers com Docker

```bash
docker-compose up -d
```

---

## 🛠 Acesse o container da aplicação

```bash
docker-compose exec app bash
```

---

## ⚙️ Instale as dependências do Laravel

```bash
composer install
```

---

## 🔐 Gere a chave da aplicação

```bash
php artisan key:generate
```

---

## 🗄 Rode as migrations

```bash
php artisan migrate
```

---

## 🌐 Acesse o projeto no navegador

Abra [http://localhost:8000](http://localhost:8000) no seu navegador.

---

## 🧼 Finalizando

Pronto! Seu ambiente Laravel 12 com Docker está funcionando! 🎉

---

## 🐞 Dúvidas ou problemas?

Abra uma issue no repositório.

## Produção

Em produção, sempre carregue explicitamente o arquivo de override e recrie os
containers quando o Compose for alterado:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --force-recreate
```

O MySQL roda no host e o `.env` deve usar `DB_HOST=host.docker.internal`. O
mapeamento desse nome para o gateway do host fica no `docker-compose.yml`, para
que ele também exista em servidores Linux.

Confira a resolução do host e a conexão antes de limpar os caches:

```bash
docker exec isabelle_app getent hosts host.docker.internal
docker exec isabelle_app php artisan db:show
docker exec isabelle_app php artisan optimize:clear
```
