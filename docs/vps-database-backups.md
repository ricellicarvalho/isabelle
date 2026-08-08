# Backup automatico das bases da VPS

Este guia configura backup diario, as 02:00, das bases `isabelle` e `agilgestao`.

O backup fica em dois lugares:

- na VPS, fora dos containers;
- no Google Drive, via `rclone`.

O script mantem sempre os ultimos 7 backups em cada destino.

## Arquivos criados no projeto

- `scripts/backup-vps-databases.sh`
- `scripts/backup-vps-databases.env.example`
- `docs/vps-database-backups.md`

O script `scripts/sync-production-db.sh` nao foi alterado.

## O que voce precisa alterar

Na VPS, o arquivo real sera:

```bash
/home/ricelli/.vps-database-backups.env
```

Nele, altere principalmente:

```bash
ISABELLE_DB_NAME=isabelle_db
AGILGESTAO_DB_NAME=agilgestao_db
```

Troque `isabelle_db` e `agilgestao_db` pelos nomes reais das bases na VPS.

Se as credenciais reais forem diferentes das fake abaixo, altere tambem:

```bash
MYSQL_USER=root
MYSQL_PASSWORD=gurupi

ISABELLE_DB_USER=isabelle_user
ISABELLE_DB_PASSWORD=gurupi

AGILGESTAO_DB_USER=agilgestao_user
AGILGESTAO_DB_PASSWORD=gurupi
```

Se o nome do remote do Google Drive no `rclone` nao for `gdrive`, altere:

```bash
RCLONE_REMOTE=gdrive
```

## Como o Google Drive conecta

O script nao usa usuario/senha do Google Drive direto.

A conexao e feita pelo `rclone`. Voce configura uma vez na VPS com:

```bash
rclone config
```

Nesse processo, o `rclone` abre/autentica sua conta Google e salva um token local no usuario da VPS, normalmente em:

```bash
/home/ricelli/.config/rclone/rclone.conf
```

Depois disso, o script consegue enviar arquivos para o Drive usando:

```bash
rclone copyto ...
```

Os diretorios que voce ja criou no Drive serao usados:

```bash
Backups_VPS_ISABELLE
Backups_VPS_AGILGESTAO
```

## Instalacao na VPS

Execute estes comandos na VPS.

### 1. Instalar dependencias

```bash
sudo apt update
sudo apt install -y gzip rclone mysql-client
```

Se `mysql-client` nao existir nessa distro, use:

```bash
sudo apt install -y mariadb-client
```

### 2. Configurar Google Drive no rclone

```bash
rclone config
```

Crie um remote chamado:

```text
gdrive
```

Teste se ele acessa o Drive:

```bash
rclone lsd gdrive:
rclone lsf gdrive:Backups_VPS_ISABELLE
rclone lsf gdrive:Backups_VPS_AGILGESTAO
```

### 3. Criar diretorios locais de backup

```bash
sudo mkdir -p /var/backups/isabelle/database
sudo mkdir -p /var/backups/agilgestao/database
sudo chown -R ricelli:ricelli /var/backups/isabelle /var/backups/agilgestao
chmod 700 /var/backups/isabelle/database /var/backups/agilgestao/database
```

### 4. Criar arquivo real de configuracao

No diretorio do projeto `isabelle` na VPS:

```bash
cp scripts/backup-vps-databases.env.example /home/ricelli/.vps-database-backups.env
chmod 600 /home/ricelli/.vps-database-backups.env
```

Edite:

```bash
nano /home/ricelli/.vps-database-backups.env
```

Troque os nomes reais das bases:

```bash
ISABELLE_DB_NAME=nome_real_da_base_isabelle
AGILGESTAO_DB_NAME=nome_real_da_base_agilgestao
```

### 5. Testar manualmente

No diretorio do projeto `isabelle` na VPS:

```bash
./scripts/backup-vps-databases.sh
```

Conferir arquivos locais:

```bash
ls -lh /var/backups/isabelle/database
ls -lh /var/backups/agilgestao/database
```

Validar os arquivos compactados:

```bash
gzip -t /var/backups/isabelle/database/db_backup_$(date +%F).sql.gz
gzip -t /var/backups/agilgestao/database/db_backup_$(date +%F).sql.gz
```

Conferir no Google Drive:

```bash
rclone lsf gdrive:Backups_VPS_ISABELLE
rclone lsf gdrive:Backups_VPS_AGILGESTAO
```

## Agendar todo dia as 02:00

Esta linha nao e para executar direto no terminal:

```cron
0 2 * * * /caminho/do/projeto/isabelle/scripts/backup-vps-databases.sh >> /var/log/vps-database-backups.log 2>&1
```

Ela deve ser colocada dentro do crontab.

Abra o crontab:

```bash
crontab -e
```

Adicione a linha, ajustando `/caminho/do/projeto/isabelle` para o caminho real do projeto na VPS.

Exemplo, se o projeto estiver em `/home/ricelli/isabelle`:

```cron
0 2 * * * /home/ricelli/isabelle/scripts/backup-vps-databases.sh >> /var/log/vps-database-backups.log 2>&1
```

Criar o arquivo de log:

```bash
sudo touch /var/log/vps-database-backups.log
sudo chown ricelli:ricelli /var/log/vps-database-backups.log
```

Verificar se o cron ficou salvo:

```bash
crontab -l
```

## O que o backup inclui

O dump usa:

```bash
--routines --triggers --events --single-transaction --quick --hex-blob
```

Ou seja, inclui:

- estrutura das tabelas;
- dados;
- routines/procedures;
- triggers;
- events.

## Restauracao rapida

Use estes passos quando precisar restaurar uma base.

### 1. Colocar a aplicacao em manutencao

Para Isabelle:

```bash
cd /home/ricelli/isabelle
docker exec isabelle_app php artisan down
```

Para Agilgestao, use o diretorio/container correto do projeto.

Se o PHP roda direto no host:

```bash
php artisan down
```

### 2. Escolher o backup local

Isabelle:

```bash
ls -1t /var/backups/isabelle/database/db_backup_*.sql.gz | head
```

Agilgestao:

```bash
ls -1t /var/backups/agilgestao/database/db_backup_*.sql.gz | head
```

Se precisar buscar no Drive:

```bash
rclone copy gdrive:Backups_VPS_ISABELLE/db_backup_YYYY-MM-DD.sql.gz /tmp/
rclone copy gdrive:Backups_VPS_AGILGESTAO/db_backup_YYYY-MM-DD.sql.gz /tmp/
```

### 3. Validar o backup

```bash
gzip -t /var/backups/isabelle/database/db_backup_YYYY-MM-DD.sql.gz
gzip -t /var/backups/agilgestao/database/db_backup_YYYY-MM-DD.sql.gz
```

### 4. Restaurar Isabelle

Troque `isabelle_db` pelo nome real da base se for diferente.

```bash
export MYSQL_PWD='gurupi'
gunzip -c /var/backups/isabelle/database/db_backup_YYYY-MM-DD.sql.gz | mysql -u isabelle_user --default-character-set=utf8mb4 isabelle_db
unset MYSQL_PWD
```

Se precisar usar root:

```bash
export MYSQL_PWD='gurupi'
gunzip -c /var/backups/isabelle/database/db_backup_YYYY-MM-DD.sql.gz | mysql -u root --default-character-set=utf8mb4 isabelle_db
unset MYSQL_PWD
```

### 5. Restaurar Agilgestao

Troque `agilgestao_db` pelo nome real da base se for diferente.

```bash
export MYSQL_PWD='gurupi'
gunzip -c /var/backups/agilgestao/database/db_backup_YYYY-MM-DD.sql.gz | mysql -u agilgestao_user --default-character-set=utf8mb4 agilgestao_db
unset MYSQL_PWD
```

Se precisar usar root:

```bash
export MYSQL_PWD='gurupi'
gunzip -c /var/backups/agilgestao/database/db_backup_YYYY-MM-DD.sql.gz | mysql -u root --default-character-set=utf8mb4 agilgestao_db
unset MYSQL_PWD
```

### 6. Limpar cache e religar a aplicacao

Isabelle:

```bash
cd /home/ricelli/isabelle
docker exec isabelle_app php artisan optimize:clear
docker exec isabelle_app php artisan migrate --force
docker exec isabelle_app php artisan up
```

Para Agilgestao, use o diretorio/container correto.

Se o PHP roda direto no host:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan up
```

### 7. Conferir

```bash
php artisan migrate:status
```

Depois abra as telas principais da aplicacao e confira login, dashboards e listagens.
