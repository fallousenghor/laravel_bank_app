<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'type',
        'solde',
        'statut',
        'date_creation',
        'utilisateur_id'
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

        // Global scope pour exclure les comptes supprimés
        static::addGlobalScope('nonSupprimes', function ($builder) {
            $builder->whereNull('deleted_at');
        });
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
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
}
