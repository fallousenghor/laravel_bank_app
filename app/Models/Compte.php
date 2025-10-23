<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'type',
        'solde',
        'statut',
        'date_creation',
        'utilisateur_id'
    ];

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
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
