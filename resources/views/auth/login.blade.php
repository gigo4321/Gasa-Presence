<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - GASA ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .slideshow {
            position: fixed; width: 100%; height: 100%; top: 0; left: 0; z-index: -1;
            background: #000;
        }
        .slideshow figure {
            width: 100%; height: 100%; position: absolute; top: 0; left: 0;
            background-size: cover; background-position: center; opacity: 0;
            animation: imageAnimation 18s linear infinite;
        }
        /* Utilisation des photos locales du dossier public/images/ */
        .slideshow figure:nth-child(1) { background-image: url('{{ asset('images/modif1.png') }}'); }
        .slideshow figure:nth-child(2) { background-image: url('{{ asset('images/modif2.jpeg') }}'); animation-delay: 6s; }
        .slideshow figure:nth-child(3) { background-image: url('{{ asset('images/modif3.png') }}'); animation-delay: 12s; }

        @keyframes imageAnimation {
            0% { opacity: 0; animation-timing-function: ease-in; }
            8% { opacity: 0.6; animation-timing-function: ease-out; }
            33% { opacity: 0.6; }
            41% { opacity: 0; }
            100% { opacity: 0; }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <!-- Background Slideshow -->
    <div class="slideshow">
        <figure></figure><figure></figure><figure></figure>
    </div>

    <!-- Login Card -->
    <div class="w-full max-w-md p-8 space-y-6 bg-stone-50/90 backdrop-blur-md rounded-xl shadow-2xl border border-stone-200/50">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-stone-800">GASA-FORMATION</h1>
            <p class="mt-2 text-sm text-stone-600 font-medium uppercase tracking-wider">Système de Gestion ERP</p>
        </div>

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-stone-700">Email professionnel</label>
                <input type="email" name="email" required value="{{ old('email') }}"
                    class="w-full px-4 py-3 mt-1 border border-stone-300 rounded-lg focus:ring-2 focus:ring-stone-500 focus:outline-none bg-white/50 @error('email') border-red-500 @enderror">
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-stone-700">Mot de passe</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 mt-1 border border-stone-300 rounded-lg focus:ring-2 focus:ring-stone-500 focus:outline-none bg-white/50">
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                    <label for="remember" class="ml-2 text-sm text-gray-600">Se souvenir de moi</label>
                </div>
            </div>

            <button type="submit"
                class="w-full py-3 text-white bg-stone-800 rounded-lg font-semibold hover:bg-stone-700 transition duration-200 shadow-lg">
                Se connecter
            </button>
        </form>

        <div class="pt-4 text-center border-t border-stone-200">
            <p class="text-xs text-stone-500 uppercase tracking-widest">Université GASA-FORMATION</p>
        </div>
    </div>
</body>
</html>
