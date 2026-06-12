@extends('layouts.app')

@section('titre', 'Importation de Données')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-stone-800 mb-6">Importer des Données</h2>

    <div class="bg-white rounded-lg shadow-md p-6 border border-stone-200">
        <form action="{{ route('import.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label for="entity_type" class="block text-sm font-medium text-stone-700 mb-1">Type d'entité à importer :</label>
                <select name="entity_type" id="entity_type" required
                        class="mt-1 block w-full px-3 py-2 border border-stone-300 rounded-md shadow-sm focus:outline-none focus:ring-stone-500 focus:border-stone-500 sm:text-sm bg-white">
                    <option value="">-- Sélectionner un type --</option>
                    @foreach($importableEntities as $group => $entities)
                        <optgroup label="{{ ucfirst($group) }}">
                            @foreach($entities as $value => $label)
                                <option value="{{ $value }}" {{ old('entity_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('entity_type')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="import_file" class="block text-sm font-medium text-stone-700 mb-1">Fichier CSV à importer :</label>
                <input type="file" name="import_file" id="import_file" required
                       class="mt-1 block w-full text-sm text-stone-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-full file:border-0
                              file:text-sm file:font-semibold
                              file:bg-stone-100 file:text-stone-700
                              hover:file:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500">
                @error('import_file')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-stone-800 hover:bg-stone-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-stone-500 transition duration-150 ease-in-out">
                <i class="bi bi-upload me-2"></i> Importer le fichier
            </button>
        </form>
    </div>

    <div class="mt-8 p-6 bg-stone-50 rounded-lg border border-stone-200 text-sm text-stone-600">
        <p class="font-semibold mb-3 text-stone-800">Instructions d'importation :</p>
        <ul class="list-disc list-inside space-y-2">
            <li>Le fichier doit être au format **CSV** (pour l'instant, le support XLSX nécessite une librairie additionnelle comme Maatwebsite/Laravel-Excel).</li>
            <li>La première ligne du fichier doit contenir les **en-têtes de colonne** qui correspondent aux noms des champs de l'entité (ex: `nom,prenom,email,matricule`).</li>
            <li>Pour les entités liées (ex: `Matiere` à `Filiere`), utilisez les **codes uniques** (`filiere_code`) pour les références, le système fera la correspondance.</li>
            <li>Les `Étudiants`, `Options` et `Salles` importés par un Responsable de Centre seront automatiquement rattachés à son centre.</li>
            <li>Pour les `Utilisateurs` importés par un Responsable de Centre, ils seront rattachés à son centre. Un Admin peut spécifier le `centre_nom` dans le CSV pour un utilisateur.</li>
            <li>En cas d'erreur dans une ligne, l'importation entière sera annulée pour garantir l'intégrité des données.</li>
            <li>Pour les fichiers volumineux, l'utilisation de <a href="https://laravel.com/docs/11.x/queues" target="_blank" class="text-stone-700 underline hover:text-stone-900">Laravel Queues (Jobs)</a> est recommandée pour éviter les timeouts.</li>
        </ul>
    </div>
</div>
@endsection