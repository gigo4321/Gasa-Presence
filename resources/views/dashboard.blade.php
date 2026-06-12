<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - GASA ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-100 flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-stone-900 text-stone-200 flex flex-col">
        <div class="p-6 text-xl font-bold border-b border-stone-800 text-white">GASA-ERP</div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="#" class="block p-3 bg-stone-700 text-white rounded shadow-sm">Tableau de bord</a>
            @admin <a href="#" class="block p-3 hover:bg-stone-800 rounded transition">Gestion Globale</a> @endadmin
            <a href="#" class="block p-3 hover:bg-stone-800 rounded transition">Planning</a>
            <a href="#" class="block p-3 hover:bg-stone-800 rounded transition">Étudiants</a>
        </nav>
        <form action="{{ route('logout') }}" method="POST" class="p-4 border-t border-stone-800">
            @csrf
            <button class="w-full p-2 text-sm bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white rounded">Déconnexion</button>
        </form>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        <header class="bg-white border-b border-stone-200 p-4 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-stone-800">Bienvenue, {{ Auth::user()->name }}</h2>
            <span class="px-3 py-1 bg-stone-100 text-stone-700 rounded-full text-xs font-bold uppercase tracking-wide border border-stone-200">{{ Auth::user()->role }}</span>
        </header>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Les widgets de statistiques viendront ici -->
            </div>
        </div>
    </main>
</body>
</html>
