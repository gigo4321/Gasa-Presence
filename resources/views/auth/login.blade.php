<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GASA-ERP — Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .slideshow{position:fixed;width:100%;height:100%;top:0;left:0;z-index:-1;background:#1a0a00;}
        .slideshow figure{width:100%;height:100%;position:absolute;top:0;left:0;background-size:cover;background-position:center;opacity:0;animation:img 18s linear infinite;}
        .slideshow figure:nth-child(1){background-image:url('/images/modif1.png');}
        .slideshow figure:nth-child(2){background-image:url('/images/modif2.jpeg');animation-delay:6s;}
        .slideshow figure:nth-child(3){background-image:url('/images/modif3.png');animation-delay:12s;}
        @keyframes img{0%{opacity:0}8%{opacity:.5}33%{opacity:.5}41%{opacity:0}100%{opacity:0}}
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="slideshow"><figure></figure><figure></figure><figure></figure></div>
    <div class="w-full max-w-md p-8 space-y-6 rounded-2xl shadow-2xl border border-white/20" style="background:rgba(255,253,241,0.88);backdrop-filter:blur(12px);">
        <div class="text-center">
            <img src="{{ asset('images/logo.png') }}" alt="Logo GASA" class="h-16 mx-auto mb-4 drop-shadow-md">
            <span class="inline-block px-4 py-1 text-xs font-mono rounded-full text-white mb-2" style="background:#3E2723;">GASA-FORMATION ERP</span>
            <h1 class="text-2xl font-bold" style="color:#3E2723;">Espace Administratif</h1>
            <p class="mt-1 text-sm" style="color:#8D6E63;">Connectez-vous pour accéder à votre espace</p>
        </div>
        @if($errors->any())
        <div class="p-3 rounded-xl text-sm border-l-4" style="background:#fee2e2;border-color:#dc2626;color:#991b1b;">
            ⚠ {{ $errors->first() }}
        </div>
        @endif
        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold mb-1" style="color:#3E2723;">Adresse Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-4 py-3 rounded-xl border focus:outline-none"
                    style="border-color:rgba(141,110,99,.4);background:rgba(255,255,255,.9);"
                    placeholder="votre.nom@gasa.bj">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1" style="color:#3E2723;">Mot de passe</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 rounded-xl border focus:outline-none"
                    style="border-color:rgba(141,110,99,.4);background:rgba(255,255,255,.9);"
                    placeholder="••••••••">
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" class="rounded">
                <label for="remember" class="text-sm" style="color:#8D6E63;">Se souvenir de moi</label>
            </div>
            <button type="submit" class="w-full py-3 rounded-xl text-white font-semibold transition-all hover:opacity-90" style="background:#8D6E63;">
                Se connecter au portail
            </button>
        </form>
    </div>
</body>
</html>
