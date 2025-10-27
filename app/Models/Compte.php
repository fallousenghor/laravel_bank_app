<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'numero',
        'type',
        'solde',
        'statut',
        'date_creation',
        'utilisateur_id',
        'devise'
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

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id')->select('id', 'prenom', 'nom');
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
}
