<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>GASA-ERP — @yield('titre','Tableau de Bord')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root{--creme:#FFFDF1;--beige:#FFF3DD;--marron:#8D6E63;--fonce:#3E2723;}
        body{background:var(--creme);font-family:'Segoe UI',sans-serif;}
        .sidebar{width:240px;height:100vh;position:fixed;top:0;left:0;background:var(--fonce);display:flex;flex-direction:column;z-index:100;overflow:hidden;}
        .sidebar-logo{padding:16px 16px 10px;border-bottom:1px solid rgba(255,255,255,.1);flex-shrink:0;}
        .badge-erp{font-size:10px;background:var(--marron);padding:2px 8px;border-radius:20px;font-family:monospace;color:#fff;}
        .sidebar-logo h6{font-size:13px;margin:5px 0 1px;color:#FFF3DD;}
        .sidebar-logo small{font-size:10px;opacity:.6;color:#FFF3DD;}
        .annee-badge{display:inline-block;margin-top:4px;font-size:10px;background:rgba(255,255,255,.1);color:#FFF3DD;padding:2px 8px;border-radius:12px;font-family:monospace;}
        .sidebar nav{flex:1;overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--marron) transparent;}
        .sidebar nav a{display:flex;align-items:center;gap:10px;padding:9px 16px;color:rgba(255,243,221,.8);text-decoration:none;font-size:13px;transition:all .2s;}
        .sidebar nav a:hover,.sidebar nav a.active{background:var(--marron);color:#fff;}
        .sidebar nav a i{width:18px;text-align:center;flex-shrink:0;}
        .sidebar-section{padding:7px 16px 3px;font-size:10px;text-transform:uppercase;letter-spacing:.08em;opacity:.4;font-weight:600;color:#FFF3DD;}
        .centre-link{display:flex;align-items:center;gap:8px;padding:6px 16px 6px 28px;color:rgba(255,243,221,.65);text-decoration:none;font-size:12px;transition:all .2s;border-left:2px solid transparent;}
        .centre-link:hover{color:#fff;background:rgba(255,255,255,.07);border-left-color:var(--marron);}
        .centre-link.active{color:#fff;background:rgba(255,255,255,.12);border-left-color:#FFF3DD;}
        .centre-dot{width:6px;height:6px;border-radius:50%;background:var(--marron);flex-shrink:0;}
        .sidebar-footer{flex-shrink:0;padding:14px 16px;border-top:1px solid rgba(255,255,255,.1);}
        .main-content{margin-left:240px;}
        .top-bar{background:#fff;border-bottom:1px solid rgba(0,0,0,.08);padding:14px 28px;display:flex;align-items:center;justify-content:space-between;}
        .top-bar h1{font-size:19px;font-weight:700;color:var(--fonce);margin:0;}
        .content-area{padding:22px 28px;}
        .stat-card{background:#fff;border-radius:16px;border:1px solid rgba(0,0,0,.06);padding:18px;display:flex;align-items:center;gap:14px;}
        .stat-icon{font-size:26px;}.stat-value{font-size:24px;font-weight:700;color:var(--fonce);}.stat-label{font-size:12px;color:var(--marron);}
        .table-gasa thead th{background:var(--fonce);color:#FFF3DD;font-size:13px;}
        .table-gasa tbody tr:hover{background:var(--beige);}
    </style>
    @stack('styles')
</head>
<body>
<div class="sidebar">
    <div class="sidebar-logo">
        <span class="badge-erp">GASA-ERP</span>
        <h6>{{ auth()->user()->centre?->nom ?? 'Direction Générale' }}</h6>
        <small>{{ auth()->user()->role_libelle }}</small>
        @php $anneeActive = \App\Models\AnneeScolaire::courante(); @endphp
        @if($anneeActive)
        <span class="annee-badge">{{ $anneeActive->libelle }}</span>
        @endif
    </div>
    <nav>
        @php $cid = auth()->user()->centre_id ?? request()->route('centreId'); @endphp

        @admin
        <a href="{{ route('dashboard.directeur') }}" class="{{ request()->routeIs('dashboard.directeur')?'active':'' }}">
            <i class="bi bi-speedometer2"></i> Vue d'ensemble
        </a>
        <a href="{{ route('filieres.index') }}" class="{{ request()->routeIs('filieres.*')?'active':'' }}">
            <i class="bi bi-diagram-3"></i> Filières & Matières
        </a>
        <a href="{{ route('presences.annees') }}" class="{{ request()->routeIs('presences.annees*')?'active':'' }}">
            <i class="bi bi-calendar-range"></i> Années Scolaires
        </a>
        @php $tousCentres = \App\Models\Centre::all(); @endphp
        @if($tousCentres->count())
        <div class="sidebar-section">Centres</div>
        @foreach($tousCentres as $c)
        <a href="{{ route('dashboard.centre',$c->id) }}" class="centre-link {{ request()->route('centreId')==$c->id?'active':'' }}">
            <span class="centre-dot"></span>{{ $c->nom }}
        </a>
        @endforeach
        @endif
        @if($cid)
        <div class="sidebar-section mt-1">Ce centre</div>
        <a href="{{ route('dashboard.centre',$cid) }}" class="{{ request()->routeIs('dashboard.centre')?'active':'' }}"><i class="bi bi-house"></i> Tableau de Bord</a>
        <a href="{{ route('options.index',$cid) }}" class="{{ request()->routeIs('options.*')?'active':'' }}"><i class="bi bi-collection"></i> Groupes</a>
        <a href="{{ route('etudiants.index',$cid) }}" class="{{ request()->routeIs('etudiants.*')?'active':'' }}"><i class="bi bi-mortarboard"></i> Étudiants</a>
        <a href="{{ route('professeurs.index',$cid) }}" class="{{ request()->routeIs('professeurs.*')?'active':'' }}"><i class="bi bi-person-badge"></i> Professeurs</a>
        <a href="{{ route('seances.index',$cid) }}" class="{{ request()->routeIs('seances.*')?'active':'' }}"><i class="bi bi-calendar3"></i> Planning</a>
        <a href="{{ route('planning.apercu',$cid) }}" class="{{ request()->routeIs('planning.*')?'active':'' }}"><i class="bi bi-magic"></i> Génération</a>
        <a href="{{ route('salles.index',$cid) }}" class="{{ request()->routeIs('salles.*')?'active':'' }}"><i class="bi bi-door-open"></i> Salles</a>
        <a href="{{ route('scan.index',$cid) }}" class="{{ request()->routeIs('scan.*')?'active':'' }}"><i class="bi bi-qr-code-scan"></i> Scan Accès</a>
        <a href="{{ route('matieres.index',$cid) }}" class="{{ request()->routeIs('matieres.*')?'active':'' }}"><i class="bi bi-book"></i> Matières</a>
        <a href="{{ route('presences.centre',$cid) }}" class="{{ request()->routeIs('presences.centre')?'active':'' }}"><i class="bi bi-clipboard2-check"></i> Présences</a>
        @endif
        @endadmin

        @if(!auth()->user()->estAdmin() && $cid)
        <a href="{{ route('dashboard.centre',$cid) }}" class="{{ request()->routeIs('dashboard.centre')?'active':'' }}"><i class="bi bi-house"></i> Tableau de Bord</a>
        <a href="{{ route('options.index',$cid) }}" class="{{ request()->routeIs('options.*')?'active':'' }}"><i class="bi bi-collection"></i> Groupes</a>
        <a href="{{ route('etudiants.index',$cid) }}" class="{{ request()->routeIs('etudiants.*')?'active':'' }}"><i class="bi bi-mortarboard"></i> Étudiants</a>
        <a href="{{ route('professeurs.index',$cid) }}" class="{{ request()->routeIs('professeurs.*')?'active':'' }}"><i class="bi bi-person-badge"></i> Professeurs</a>
        <a href="{{ route('seances.index',$cid) }}" class="{{ request()->routeIs('seances.*')?'active':'' }}"><i class="bi bi-calendar3"></i> Planning</a>
        <a href="{{ route('planning.apercu',$cid) }}" class="{{ request()->routeIs('planning.*')?'active':'' }}"><i class="bi bi-magic"></i> Génération</a>
        <a href="{{ route('salles.index',$cid) }}" class="{{ request()->routeIs('salles.*')?'active':'' }}"><i class="bi bi-door-open"></i> Salles</a>
        <a href="{{ route('scan.index',$cid) }}" class="{{ request()->routeIs('scan.*')?'active':'' }}"><i class="bi bi-qr-code-scan"></i> Scan Accès</a>
        <a href="{{ route('matieres.index',$cid) }}" class="{{ request()->routeIs('matieres.*')?'active':'' }}"><i class="bi bi-book"></i> Matières</a>
        <a href="{{ route('presences.centre',$cid) }}" class="{{ request()->routeIs('presences.centre')?'active':'' }}"><i class="bi bi-clipboard2-check"></i> Présences</a>
        @endif
    </nav>
    <div class="sidebar-footer">
        <div style="font-size:13px;font-weight:600;color:#FFF3DD;">{{ auth()->user()->name }}</div>
        <div style="font-size:11px;opacity:.6;margin-bottom:8px;color:#FFF3DD;">{{ auth()->user()->email }}</div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button type="submit" class="btn btn-sm w-100" style="background:#c62828;color:#fff;font-size:12px;">
                <i class="bi bi-box-arrow-left"></i> Déconnexion
            </button>
        </form>
    </div>
</div>
<div class="main-content">
    <div class="top-bar">
        <h1>@yield('titre','Tableau de Bord')</h1>
        <span style="font-size:13px;color:#999;">{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</span>
    </div>
    <div class="content-area">
        @if(session('succes'))
        <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3">
            <i class="bi bi-check-circle me-2"></i>{{ session('succes') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('import_erreurs') && count(session('import_erreurs')))
        <div class="alert alert-warning alert-dismissible fade show mb-4 rounded-3">
            <strong>Lignes ignorées :</strong>
            @foreach(session('import_erreurs') as $e)<div style="font-size:13px;">{{ $e }}</div>@endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3">
            @foreach($errors->all() as $err)<div><i class="bi bi-exclamation-circle me-1"></i>{{ $err }}</div>@endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @yield('content')
    </div>
</div>
@stack('modals')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
