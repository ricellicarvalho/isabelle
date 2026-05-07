<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */

    'column.name'        => 'Nome',
    'column.guard_name'  => 'Guard',
    'column.team'        => 'Equipe',
    'column.roles'       => 'Perfis',
    'column.permissions' => 'Permissões',
    'column.updated_at'  => 'Atualizado em',

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    'field.name'               => 'Nome',
    'field.guard_name'         => 'Guard',
    'field.permissions'        => 'Permissões',
    'field.team'               => 'Equipe',
    'field.team.placeholder'   => 'Selecione uma equipe...',
    'field.select_all.name'    => 'Selecionar Todas',
    'field.select_all.message' => 'Habilita/Desabilita todas as Permissões para este perfil',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Resource
    |--------------------------------------------------------------------------
    */

    'nav.group'            => 'Controle de Acesso',
    'nav.role.label'       => 'Perfis',
    'nav.role.icon'        => 'heroicon-o-shield-check',
    'resource.label.role'  => 'Perfil',
    'resource.label.roles' => 'Perfis',

    /*
    |--------------------------------------------------------------------------
    | Section & Tabs
    |--------------------------------------------------------------------------
    */

    'section'   => 'Entidades',
    'resources' => 'Recursos',
    'widgets'   => 'Widgets',
    'pages'     => 'Páginas',
    'custom'    => 'Permissões Personalizadas',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'forbidden' => 'Você não tem permissão para acessar este recurso.',

    /*
    |--------------------------------------------------------------------------
    | Resource Permissions' Labels
    |--------------------------------------------------------------------------
    */

    'resource_permission_prefixes_labels' => [
        'view'             => 'Visualizar',
        'view_any'         => 'Ver Lista',
        'create'           => 'Cadastrar',
        'update'           => 'Editar',
        'delete'           => 'Excluir',
        'delete_any'       => 'Excluir em Massa',
        'force_delete'     => 'Excluir Permanentemente',
        'force_delete_any' => 'Excluir Permanentemente em Massa',
        'restore'          => 'Restaurar',
        'reorder'          => 'Reordenar',
        'restore_any'      => 'Restaurar em Massa',
        'replicate'        => 'Duplicar',
    ],
];
