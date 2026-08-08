#!/usr/bin/env bash

set -Eeuo pipefail

readonly DEFAULT_CONFIG_FILE="/home/ricelli/.vps-database-backups.env"
CONFIG_FILE="${BACKUP_CONFIG_FILE:-${DEFAULT_CONFIG_FILE}}"

log() {
    printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

fail() {
    log "ERRO: $*" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "Comando obrigatório não encontrado: $1"
}

project_var() {
    local project="$1"
    local suffix="$2"
    local key

    key="$(printf '%s_%s' "$project" "$suffix" | tr '[:lower:]' '[:upper:]')"
    printf '%s' "${!key:-}"
}

cleanup_old_local_backups() {
    local backup_dir="$1"
    local retention="$2"
    local files

    shopt -s nullglob
    files=("${backup_dir}"/db_backup_*.sql.gz)
    shopt -u nullglob

    if ((${#files[@]} <= retention)); then
        return
    fi

    printf '%s\n' "${files[@]}" \
        | sort \
        | head -n "$((${#files[@]} - retention))" \
        | while IFS= read -r file; do
            log "Removendo backup local antigo: ${file}"
            rm -f -- "$file"
        done
}

cleanup_old_drive_backups() {
    local remote="$1"
    local folder="$2"
    local retention="$3"
    local files=()
    local remove_count

    mapfile -t files < <(rclone lsf "${remote}:${folder}" --files-only --include 'db_backup_*.sql.gz' 2>/dev/null | sort)

    if ((${#files[@]} <= retention)); then
        return
    fi

    remove_count="$((${#files[@]} - retention))"
    for file in "${files[@]:0:${remove_count}}"; do
        log "Removendo backup antigo do Drive: ${remote}:${folder}/${file}"
        rclone deletefile "${remote}:${folder}/${file}"
    done
}

dump_database() {
    local project="$1"
    local db_name="$2"
    local db_user="$3"
    local db_password="$4"
    local backup_dir="$5"
    local backup_file="$6"
    local dump_bin="$7"
    local mysql_host="$8"
    local mysql_port="$9"
    local tmp_file="${backup_file}.tmp"

    log "Gerando backup de ${project}: banco=${db_name}, destino=${backup_file}"
    mkdir -p "$backup_dir"
    chmod 700 "$backup_dir"

    export MYSQL_PWD="$db_password"
    "$dump_bin" \
        --host="$mysql_host" \
        --port="$mysql_port" \
        --user="$db_user" \
        --no-tablespaces \
        --single-transaction \
        --quick \
        --routines \
        --triggers \
        --events \
        --hex-blob \
        --default-character-set=utf8mb4 \
        "$db_name" \
        | gzip -c > "$tmp_file"
    unset MYSQL_PWD

    gzip -t "$tmp_file" || fail "Backup compactado inválido: ${tmp_file}"
    [[ -s "$tmp_file" ]] || fail "Backup vazio: ${tmp_file}"

    mv -f "$tmp_file" "$backup_file"
    chmod 600 "$backup_file"
}

upload_to_drive() {
    local backup_file="$1"
    local remote="$2"
    local folder="$3"

    log "Enviando para Google Drive: ${remote}:${folder}/$(basename "$backup_file")"
    rclone mkdir "${remote}:${folder}"
    rclone copyto "$backup_file" "${remote}:${folder}/$(basename "$backup_file")"
}

main() {
    [[ -f "$CONFIG_FILE" ]] || fail "Arquivo de configuração não encontrado: ${CONFIG_FILE}"

    # shellcheck disable=SC1090
    source "$CONFIG_FILE"

    require_command gzip
    require_command rclone

    local dump_bin
    if command -v mysqldump >/dev/null 2>&1; then
        dump_bin="mysqldump"
    elif command -v mariadb-dump >/dev/null 2>&1; then
        dump_bin="mariadb-dump"
    else
        fail "mysqldump/mariadb-dump não encontrado"
    fi

    local retention="${BACKUP_RETENTION_COUNT:-7}"
    local date_format="${BACKUP_DATE_FORMAT:-%F}"
    local backup_date
    backup_date="$(date "+${date_format}")"

    local mysql_host="${MYSQL_HOST:-127.0.0.1}"
    local mysql_port="${MYSQL_PORT:-3306}"
    local mysql_user="${MYSQL_USER:-}"
    local mysql_password="${MYSQL_PASSWORD:-}"
    local rclone_remote="${RCLONE_REMOTE:-}"

    [[ -n "${mysql_user}" ]] || fail "MYSQL_USER não foi definido"
    [[ -n "${mysql_password}" ]] || fail "MYSQL_PASSWORD não foi definido"
    [[ -n "${rclone_remote}" ]] || fail "RCLONE_REMOTE não foi definido"
    [[ "${retention}" =~ ^[0-9]+$ && "${retention}" -gt 0 ]] || fail "BACKUP_RETENTION_COUNT inválido"
    declare -p PROJECTS >/dev/null 2>&1 || fail "PROJECTS não foi definido"
    [[ "${#PROJECTS[@]}" -gt 0 ]] || fail "PROJECTS não foi definido"

    log "Iniciando backup de bancos da VPS"

    local project db_name backup_dir drive_folder db_user db_password backup_file
    for project in "${PROJECTS[@]}"; do
        db_name="$(project_var "$project" DB_NAME)"
        backup_dir="$(project_var "$project" BACKUP_DIR)"
        drive_folder="$(project_var "$project" DRIVE_FOLDER)"
        db_user="$(project_var "$project" DB_USER)"
        db_password="$(project_var "$project" DB_PASSWORD)"

        db_user="${db_user:-${mysql_user}}"
        db_password="${db_password:-${mysql_password}}"

        [[ -n "$db_name" ]] || fail "${project}: DB_NAME não definido"
        [[ -n "$backup_dir" ]] || fail "${project}: BACKUP_DIR não definido"
        [[ -n "$drive_folder" ]] || fail "${project}: DRIVE_FOLDER não definido"

        backup_file="${backup_dir}/db_backup_${backup_date}.sql.gz"

        dump_database "$project" "$db_name" "$db_user" "$db_password" "$backup_dir" "$backup_file" "$dump_bin" "$mysql_host" "$mysql_port"
        upload_to_drive "$backup_file" "$rclone_remote" "$drive_folder"
        cleanup_old_local_backups "$backup_dir" "$retention"
        cleanup_old_drive_backups "$rclone_remote" "$drive_folder" "$retention"

        log "Backup concluído para ${project}"
    done

    log "Rotina de backup concluída com sucesso"
}

main "$@"
