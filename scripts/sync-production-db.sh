#!/usr/bin/env bash

set -Eeuo pipefail

readonly PROD_SSH_HOST="76.13.167.16"
readonly PROD_SSH_USER="ricelli"
readonly PROD_DB_NAME="isabelle_db"
readonly PROD_DB_USER="isabelle_user"
readonly APP_SERVICE="isabelle_app"
readonly DB_SERVICE="isabelle_database"

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${PROJECT_DIR}/database/backups"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
PROD_DUMP="${BACKUP_DIR}/production_${PROD_DB_NAME}_${TIMESTAMP}.sql.gz"
LOCAL_BACKUP="${BACKUP_DIR}/local_${PROD_DB_NAME}_before_sync_${TIMESTAMP}.sql.gz"

cleanup() {
    unset PROD_DB_PASSWORD || true
}
trap cleanup EXIT

fail() {
    printf 'Erro: %s\n' "$*" >&2
    exit 1
}

command -v docker >/dev/null 2>&1 || fail "Docker não foi encontrado."
command -v ssh >/dev/null 2>&1 || fail "O cliente SSH não foi encontrado."
command -v gzip >/dev/null 2>&1 || fail "gzip não foi encontrado."
command -v gunzip >/dev/null 2>&1 || fail "gunzip não foi encontrado."

cd "${PROJECT_DIR}"
docker compose config --quiet
docker compose ps --status running "${APP_SERVICE}" | grep -q "${APP_SERVICE}" \
    || fail "O serviço ${APP_SERVICE} não está em execução."
docker compose ps --status running "${DB_SERVICE}" | grep -q "${DB_SERVICE}" \
    || fail "O serviço ${DB_SERVICE} não está em execução."

mkdir -p "${BACKUP_DIR}"

printf '\nSincronização do banco de PRODUÇÃO para o ambiente LOCAL\n'
printf 'Produção: %s@%s / %s\n' "${PROD_SSH_USER}" "${PROD_SSH_HOST}" "${PROD_DB_NAME}"
printf 'Destino local: serviço %s / banco definido por MYSQL_DATABASE\n\n' "${DB_SERVICE}"
printf 'A senha SSH será solicitada pelo próprio SSH.\n'
read -r -s -p "Senha do MySQL de produção (${PROD_DB_USER}): " PROD_DB_PASSWORD
printf '\n'
[[ -n "${PROD_DB_PASSWORD}" ]] || fail "A senha do MySQL de produção não foi informada."

printf '\n1/6 Baixando dump da produção...\n'
printf '%s\n' "${PROD_DB_PASSWORD}" \
    | ssh -o StrictHostKeyChecking=accept-new "${PROD_SSH_USER}@${PROD_SSH_HOST}" \
        "IFS= read -r DB_PASSWORD; export MYSQL_PWD=\"\${DB_PASSWORD}\"; if command -v mysqldump >/dev/null 2>&1; then DUMP=mysqldump; elif command -v mariadb-dump >/dev/null 2>&1; then DUMP=mariadb-dump; else echo 'mysqldump não encontrado no servidor' >&2; exit 127; fi; exec \"\${DUMP}\" --no-tablespaces --single-transaction --quick --routines --triggers --events --hex-blob --default-character-set=utf8mb4 -u '${PROD_DB_USER}' '${PROD_DB_NAME}'" \
    | gzip -c > "${PROD_DUMP}"

gzip -t "${PROD_DUMP}" || fail "O dump de produção está corrompido."
[[ -s "${PROD_DUMP}" ]] || fail "O dump de produção ficou vazio."
unset PROD_DB_PASSWORD
printf 'Dump salvo em: %s\n' "${PROD_DUMP}"

printf '\n2/6 Gerando backup da base local atual...\n'
docker compose exec -T "${DB_SERVICE}" sh -lc \
    'export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; exec mysqldump --no-tablespaces --single-transaction --quick --routines --triggers --events --hex-blob --default-character-set=utf8mb4 -uroot "$MYSQL_DATABASE"' \
    | gzip -c > "${LOCAL_BACKUP}"
gzip -t "${LOCAL_BACKUP}" || fail "O backup local está corrompido."
printf 'Backup local salvo em: %s\n' "${LOCAL_BACKUP}"

printf '\nATENÇÃO: o conteúdo atual do banco local será apagado e substituído.\n'
read -r -p "Digite RECRIAR ${PROD_DB_NAME} para continuar: " CONFIRMATION
[[ "${CONFIRMATION}" == "RECRIAR ${PROD_DB_NAME}" ]] || fail "Operação cancelada; nenhum dado local foi apagado."

printf '\n3/6 Recriando o banco local...\n'
docker compose exec -T "${DB_SERVICE}" sh -lc \
    'export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; mysql -uroot -e "DROP DATABASE IF EXISTS \`$MYSQL_DATABASE\`; CREATE DATABASE \`$MYSQL_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'

printf '\n4/6 Importando o dump de produção...\n'
gunzip -c "${PROD_DUMP}" \
    | docker compose exec -T "${DB_SERVICE}" sh -lc \
        'export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; exec mysql -uroot "$MYSQL_DATABASE"'

printf '\n5/6 Aplicando migrations pendentes...\n'
docker compose exec -T "${APP_SERVICE}" php artisan migrate --force --no-interaction

printf '\n6/6 Limpando caches da aplicação...\n'
docker compose exec -T "${APP_SERVICE}" php artisan optimize:clear

printf '\nSincronização concluída com sucesso.\n'
printf 'Dump de produção: %s\n' "${PROD_DUMP}"
printf 'Backup local anterior: %s\n' "${LOCAL_BACKUP}"
printf 'Admin: http://localhost:8006/admin\n'
printf 'Portal: http://localhost:8006/portal\n'
