<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prenom',
        'nom',
        'email',
        'telephone',
        'adresse',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get only account numbers for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comptes()
    {
        // The comptes table uses `client_id` as the foreign key. Use that to eager-load the
        // related accounts and select the appropriate columns. Previously this referenced
        // `utilisateur_id` which does not exist in the schema and caused SQL errors.
        return $this->hasMany(Compte::class, 'client_id')
                    ->select(['id', 'numero', 'client_id']); // Keep id and client_id for the relation
    }

    /**
     * Retourne les scopes (permissions) à ajouter au token pour cet utilisateur.
     * Utilise le rôle pour déterminer des scopes simples. Améliorer selon la logique métier.
     *
     * @return array
     */
    public function getScopes(): array
    {
        $role = $this->role ?? 'client';

        if ($role === 'admin') {
            // admin a tous les droits
            return ['role:admin', '*'];
        }

        // Client permissions: can only see their own accounts and modify their own information
        return [
            'role:client',
            'comptes:read',
            'comptes:create',
            'comptes:update',
            'comptes:delete',
            'users:read',
            'users:update',
        ];
    }
}
