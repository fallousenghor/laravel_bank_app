<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'montant',
        'type',
        'date',
        'compte_id'
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
     * Get the compte that owns the transaction.
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}
