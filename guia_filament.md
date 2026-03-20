# Guia de Referência: Filament PHP 5.x

Este documento centraliza as instruções técnicas para a configuração de traduções, localização e demais configurações em projetos utilizando o ecossistema Laravel e Filament.

---

## 🌍 Seção: Traduções (pt_BR)

Siga este passo a passo detalhado para localizar o painel administrativo, as validações do sistema e os componentes de interface para o Português do Brasil.

### 1. Configuração do Locale no Laravel

O primeiro passo é definir o idioma global da aplicação. No arquivo `config/app.php` ou diretamente no seu arquivo `.env`, ajuste as seguintes chaves:

```env
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
```

---

## 2. Publicação das Traduções do Filament

Para que o sistema utilize arquivos de tradução que podem ser editados manualmente, execute o comando para publicar as traduções do core:

```bash
php artisan vendor:publish --tag=filament-translations
```

---

## 3. Instalação do Pacote de Linguagens do Laravel (Recomendado)

Para traduzir as mensagens de erro e validações nativas do Laravel (ex: "The email field is required"), utilize o pacote de idiomas da comunidade, que é o padrão ouro do ecossistema:

```bash
composer require laravel-lang/common --dev
php artisan lang:add pt_BR
```

### ⚠️ Observação Técnica (Resolução de Erros)

Se ao rodar o comando acima (Passo 3) ocorrer um erro de permissão ou conflito de segurança, a melhor prática é atualizar o Filament para uma versão corrigida e estável. Execute:

```bash
composer update filament/filament filament/tables --with-all-dependencies
```

---

## 4. Tradução de Recursos (Resources)

Para traduzir os nomes dos menus, rótulos de campos e títulos de colunas nos seus Resources, utilize os métodos `$modelLabel` e `$pluralModelLabel`. O Filament usa o nome da classe para gerar os rótulos automaticamente, então a sobrescrita é necessária para o português.

Abra seu arquivo de Resource (ex: `app/Filament/Resources/UserResource.php`) e adicione:

```php
namespace App\Filament\Resources;

use App\Models\User;
use Filament\Resources\Resource;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Tradução do nome do menu e títulos (Singular)
    protected static ?string $modelLabel = 'usuário';

    // Tradução do plural (usado no botão "Criar usuários" e no menu lateral)
    protected static ?string $pluralModelLabel = 'usuários';

    // ... restante do código
}
```

---

## 5. Publicando Traduções Específicas (Actions, Tables e Forms)

Se você precisar customizar mensagens internas de componentes específicos, como filtros de tabelas ou botões de ações, utilize o comando de publicação global. No Filament v5, os arquivos serão organizados em subpastas:

```bash
php artisan vendor:publish --tag=filament-translations
```

Os arquivos de tradução estarão disponíveis nos seguintes caminhos:

- `lang/vendor/filament-tables/pt_BR` (Mensagens de busca, filtros e paginação)
- `lang/vendor/filament-actions/pt_BR` (Mensagens de confirmação de exclusão, botões de ação)
- `lang/vendor/filament-forms/pt_BR` (Rótulos internos de componentes de formulário)

---

## 6. Uso do Tinker (Diagnóstico e Configuração Profissional)

### 🔍 Verificando sincronização de horários (PHP, Banco e SO)

Para garantir que o PHP (Laravel), o banco de dados (MySQL) e o sistema operacional estão sincronizados corretamente (mesmo timezone), utilize o Tinker:

```bash
php artisan tinker
```

Execute o seguinte comando:

```php
echo "Horário PHP: " . now()->toDateTimeString() . "\n" . "Horário DB:  " . DB::select('SELECT NOW() as agora')[0]->agora;
```

👉 Isso permite verificar rapidamente se há divergência de horário entre aplicação e banco.

---

### ⚙️ Configurando o Tinker (PsySH) para uso profissional

Para evitar travamentos ao lidar com objetos grandes (muito comum no Laravel e Filament) e melhorar a experiência no terminal, configure o PsySH com limites e visual mais limpo.

Execute este comando dentro do container:

```bash
cat <<EOF > /home/dev/.config/psysh/config.php
<?php

return [
    'startupMessage' => '<info>Bem-vindo ao Tinker - Project Isabelle</info>',
    'updateCheck' => 'never',
    'useUnicode' => true,
    'depth' => 3,
    'maxItems' => 100,
];
EOF
```

### 💡 Benefícios dessa configuração:

- Evita travamentos ao exibir objetos grandes
- Limita profundidade de arrays/objetos (`depth`)
- Limita quantidade de itens exibidos (`maxItems`)
- Interface mais limpa e profissional
