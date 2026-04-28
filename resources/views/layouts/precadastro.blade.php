<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pré-Cadastro de Colaboradores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 antialiased">
    <header class="bg-white border-b border-gray-200 py-4 px-6 mb-8">
        <div class="max-w-2xl mx-auto flex items-center gap-3">
            <img src="{{ asset('images/logo2.png') }}" alt="Isabelle" class="h-10 object-contain"
                onerror="this.style.display='none'">
            <span class="text-lg font-semibold text-gray-700">Isabelle</span>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 pb-16">
        @yield('content')
    </main>

    <footer class="text-center text-xs text-gray-400 py-6">
        Sistema de Gestão Isabelle
    </footer>
</body>
</html>
