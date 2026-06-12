<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\{Centre, Filiere, Matiere, Option, Etudiant, Salle, User};

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche le formulaire d'importation de données.
     */
    public function index()
    {
        $user = Auth::user();
        $importableEntities = [];

        // Entités que seul un Admin peut importer (globales ou sensibles)
        if ($user->estAdmin()) {
            $importableEntities['global'] = [
                'Filiere' => 'Filières (Globales)',
                'Matiere' => 'Matières (Globales)',
                'Centre'  => 'Centres',
                'User'    => 'Utilisateurs (Tous Centres)',
            ];
        }

        // Entités qu'un Admin ou Responsable de Centre peut importer (spécifiques au centre)
        if ($user->estAdmin() || $user->estResponsable()) {
            $importableEntities['centre'] = [
                'Option'   => 'Options de formation',
                'Etudiant' => 'Étudiants',
                'Salle'    => 'Salles',
                // Les responsables peuvent aussi importer des utilisateurs pour leur centre
                'UserCentre' => 'Utilisateurs (pour mon Centre)',
            ];
        }

        return view('import.index', compact('importableEntities'));
    }

    /**
     * Traite le fichier importé et insère les données en base.
     */
    public function store(Request $request)
    {
        $request->validate([
            'entity_type' => ['required', 'string'],
            'import_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // Max 10MB, CSV/TXT pour l'instant
        ]);

        $user = Auth::user();
        $entityType = $request->input('entity_type');
        $file = $request->file('import_file');

        // Vérification d'autorisation basée sur le type d'entité
        if (in_array($entityType, ['Filiere', 'Matiere', 'Centre'])) {
            if (!$user->estAdmin()) {
                abort(403, 'Vous n\'êtes pas autorisé à importer ce type de données.');
            }
        } elseif (in_array($entityType, ['Option', 'Etudiant', 'Salle', 'UserCentre'])) {
            if (!$user->estAdmin() && !$user->estResponsable()) {
                abort(403, 'Vous n\'êtes pas autorisé à importer ce type de données.');
            }
        } elseif ($entityType === 'User') { // Import global d'utilisateurs par l'Admin
            if (!$user->estAdmin()) {
                abort(403, 'Vous n\'êtes pas autorisé à importer des utilisateurs globalement.');
            }
        } else {
            abort(400, 'Type d\'entité d\'importation non valide.');
        }

        try {
            // Pour des fichiers volumineux, il est fortement recommandé d'utiliser les Jobs Laravel (Queues)
            // Exemple: ImportDataJob::dispatch($file->path(), $entityType, $user->id, $user->centre_id);
            $this->processCsvImport($file, $entityType, $user->centre_id);

            return back()->with('succes', 'Le fichier a été importé avec succès.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de l\'importation : ' . $e->getMessage());
        }
    }

    /**
     * Traite un fichier CSV pour l'importation.
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $entityType
     * @param int|null $userCentreId Le centre de l'utilisateur qui importe (pour le cloisonnement)
     * @throws ValidationException
     * @throws \Exception
     */
    private function processCsvImport($file, $entityType, $userCentreId = null)
    {
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $headers = array_shift($data); // La première ligne est l'en-tête

        DB::beginTransaction();
        try {
            foreach ($data as $rowIndex => $row) {
                if (count($row) !== count($headers)) {
                    throw ValidationException::withMessages(['import_file' => "Ligne mal formée à la ligne " . ($rowIndex + 2) . ". Vérifiez le nombre de colonnes."]);
                }
                $rowData = array_combine($headers, $row);

                switch ($entityType) {
                    case 'Filiere':
                        Filiere::create($rowData);
                        break;
                    case 'Matiere':
                        $filiere = Filiere::where('code', $rowData['filiere_code'])->firstOrFail("Filière non trouvée pour le code " . $rowData['filiere_code'] . " à la ligne " . ($rowIndex + 2));
                        Matiere::create(array_merge($rowData, ['filiere_id' => $filiere->id]));
                        break;
                    case 'Centre':
                        Centre::create($rowData);
                        break;
                    case 'Option':
                        $filiere = Filiere::where('code', $rowData['filiere_code'])->firstOrFail("Filière non trouvée pour le code " . $rowData['filiere_code'] . " à la ligne " . ($rowIndex + 2));
                        $centre = Centre::where('nom', $rowData['centre_nom'])->firstOrFail("Centre non trouvé pour le nom " . $rowData['centre_nom'] . " à la ligne " . ($rowIndex + 2));
                        Option::create(array_merge($rowData, ['filiere_id' => $filiere->id, 'centre_id' => $centre->id]));
                        break;
                    case 'Etudiant':
                        $option = Option::where('nom', $rowData['option_nom'])->whereHas('centre', fn($q) => $q->where('id', $userCentreId))->firstOrFail("Option non trouvée pour le nom " . $rowData['option_nom'] . " dans votre centre à la ligne " . ($rowIndex + 2));
                        Etudiant::create(array_merge($rowData, ['option_id' => $option->id]));
                        break;
                    case 'Salle':
                        Salle::create(array_merge($rowData, ['centre_id' => $userCentreId]));
                        break;
                    case 'User': // Admin important des utilisateurs pour n'importe quel centre
                    case 'UserCentre': // Responsable important des utilisateurs pour son centre
                        $centre = ($entityType === 'User' && isset($rowData['centre_nom'])) ? Centre::where('nom', $rowData['centre_nom'])->firstOrFail("Centre non trouvé pour l'utilisateur " . $rowData['email'] . " à la ligne " . ($rowIndex + 2)) : null;
                        User::create(array_merge($rowData, ['password' => Hash::make($rowData['password'] ?? 'password'), 'centre_id' => $centre->id ?? $userCentreId]));
                        break;
                    default:
                        throw ValidationException::withMessages(['entity_type' => 'Type d\'entité non supporté pour l\'importation.']);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}