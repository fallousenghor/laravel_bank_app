<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Events\ClientCreated;
use App\Events\CompteCreated;

class Compte extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'numero',
        'type',
        'solde',
        'statut',
        'date_creation',
        'client_id',
        'devise',
        'date_debut_blocage',
        'date_fin_blocage'
    ];

    /**
     * Get the transactions for the compte.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Indique que l'ID n'est pas auto-incrémenté
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indique que l'ID est une chaîne de caractères
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($compte) {
            do {
                $numero = 'CPT' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            } while (static::where('numero', $numero)->exists());

            $compte->numero = $numero;
        });

        // Override soft delete scope to not apply if column doesn't exist
        if (!Schema::hasColumn('comptes', 'deleted_at')) {
            // Remove the soft delete global scope
            static::withoutGlobalScope('Illuminate\Database\Eloquent\SoftDeletingScope');
        }
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id')->select('id', 'prenom', 'nom');
    }

    /**
     * Alias pour la relation client (pour la rétrocompatibilité)
     */
    public function utilisateur()
    {
        return $this->client();
    }

    /**
     * Scope local pour récupérer un compte par son numéro
     */
    public function scopeNumero($query, $numero)
    {
        return $query->where('numero', $numero);
    }

    /**
     * Scope local pour récupérer les comptes d'un client basé sur le téléphone
     */
    public function scopeClient($query, $telephone)
    {
        return $query->whereHas('utilisateur', function ($q) use ($telephone) {
            $q->where('telephone', $telephone);
        });
    }

    /**
     * Get the solde attribute - calculated from transactions
     */
    public function getSoldeAttribute($value)
    {
        // If there are transactions, calculate balance from them
        if ($this->transactions()->exists()) {
            return $this->transactions()
                ->selectRaw('SUM(CASE WHEN type = \'depot\' THEN montant WHEN type = \'retrait\' THEN -montant ELSE 0 END) as calculated_solde')
                ->first()
                ->calculated_solde ?? $value;
        }

        // Otherwise return stored value (for new accounts)
        return $value;
    }

    /**
     * Créer un nouveau compte avec gestion du client
     */
    public static function createWithClient(array $compteData, array $clientData)
    {
        \DB::beginTransaction();
        try {
            // Find existing client by id, email or telephone (in that order)
            $client = null;

            if (!empty($clientData['id'])) {
                $client = User::find($clientData['id']);
            }

            if (!$client && !empty($clientData['email'])) {
                $client = User::where('email', $clientData['email'])->first();
            }

            if (!$client && !empty($clientData['telephone'])) {
                $client = User::where('telephone', $clientData['telephone'])->first();
            }

            if (!$client) {
                // Create new client and send credentials + verification code
                $password = Str::random(8);
                // Use a 6-digit numeric code for SMS verification
                $code = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

                // Parse titulaire into prenom / nom more robustly
                $titulaire = $clientData['titulaire'] ?? '';
                $parts = preg_split('/\s+/', trim($titulaire));
                $prenom = $parts[0] ?? '';
                $nom = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : ($parts[0] ?? '');

                $client = User::create([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $clientData['email'] ?? null,
                    'telephone' => $clientData['telephone'] ?? null,
                    'adresse' => $clientData['adresse'] ?? null,
                    'nci' => $clientData['nci'] ?? null,
                    'code' => $code,
                    'password' => Hash::make($password),
                    // DB uses 'user' for client role (enum: 'admin','user')
                    'role' => 'user',
                ]);

                // Fire event for notifications (email + SMS)
                event(new ClientCreated($client, $password, $code));
            }

            // Create account — only include 'devise' if the DB column exists (migrations may be out of sync)
            $compteData['client_id'] = $client->id;
            $compteData['statut'] = 'actif';
            $compteData['date_creation'] = now();

            if (!Schema::hasColumn('comptes', 'devise')) {
                unset($compteData['devise']);
            }

            $compte = static::create($compteData);

            // Load the client relationship
            $compte->load('utilisateur');

            \DB::commit();
            return $compte;

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Erreur lors de la création du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compteData' => $compteData,
                'clientData' => $clientData
            ]);
            throw $e;
        }
    }

    /**
     * Mettre à jour les informations du client associé au compte
     */
    public function updateClientInfo(array $updateData)
    {
        \DB::beginTransaction();
        try {
            $user = $this->utilisateur;
            if (!$user) {
                throw new \Exception("Utilisateur associé non trouvé");
            }

            // Update titulaire if provided
            if (isset($updateData['titulaire'])) {
                // Parse titulaire into prenom / nom
                $titulaire = $updateData['titulaire'];
                $parts = preg_split('/\s+/', trim($titulaire));
                $prenom = $parts[0] ?? '';
                $nom = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : ($parts[0] ?? '');

                $user->prenom = $prenom;
                $user->nom = $nom;
            }

            // Update client information if provided
            if (isset($updateData['informationsClient'])) {
                $clientInfo = $updateData['informationsClient'];

                if (isset($clientInfo['telephone'])) {
                    $user->telephone = $clientInfo['telephone'];
                }

                if (isset($clientInfo['email'])) {
                    $user->email = $clientInfo['email'];
                }

                if (isset($clientInfo['password'])) {
                    $user->password = Hash::make($clientInfo['password']);
                }

                if (isset($clientInfo['nci'])) {
                    $user->nci = $clientInfo['nci'];
                }
            }

            // Save user changes
            $user->save();

            \DB::commit();

            // Load the updated relationship
            $this->load('utilisateur');

            return $this;

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Erreur lors de la mise à jour du compte: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'compte_id' => $this->id,
                'updateData' => $updateData
            ]);
            throw $e;
        }
    }

    /**
     * Bloquer le compte avec des dates de blocage
     */
    public function bloquer(array $blockingData)
    {
        // Règle métier : n'autoriser le blocage QUE pour les comptes de type 'epargne'
        if (!isset($this->type) || strtolower($this->type) !== 'epargne') {
            throw new \Exception("Seul un compte d'épargne peut être bloqué");
        }

        $updateData = ['statut' => 'bloque'];

        if (Schema::hasColumn('comptes', 'date_debut_blocage')) {
            $updateData['date_debut_blocage'] = $blockingData['date_debut_blocage'];
        }

        if (Schema::hasColumn('comptes', 'date_fin_blocage')) {
            $updateData['date_fin_blocage'] = $blockingData['date_fin_blocage'];
        }

        $this->update($updateData);

        return $this;
    }

    /**
     * Fermer le compte (soft delete)
     */
    public function fermer()
    {
        $this->statut = 'ferme';
        $this->save();

        return $this;
    }
}
