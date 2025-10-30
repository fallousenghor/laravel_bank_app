<?php

namespace App\Overrides;

use Laravel\Passport\Token as PassportToken;
use Laravel\Passport\TokenRepository as BaseTokenRepository;
use Illuminate\Support\Facades\DB;

class TokenRepository extends BaseTokenRepository
{
    /**
     * Create a new Access Token using a raw SQL statement to avoid server-side prepared
     * statement type issues with some poolers. This builds a SQL INSERT with proper
     * UUID cast and boolean literal values.
     *
     * @param array $attributes
     * @return \Laravel\Passport\Token
     */
    public function create($attributes)
    {
        $id = $attributes['id'];
        $userId = $attributes['user_id'];
        $clientId = intval($attributes['client_id']);
        $scopes = json_encode($attributes['scopes'] ?? []);
        $revoked = ($attributes['revoked'] ?? false) ? 'true' : 'false';

        // Accept DateTime or DateTimeImmutable (DateTimeInterface)
        $created = $attributes['created_at'] instanceof \DateTimeInterface ? $attributes['created_at']->format('Y-m-d H:i:s') : (string) $attributes['created_at'];
        $updated = $attributes['updated_at'] instanceof \DateTimeInterface ? $attributes['updated_at']->format('Y-m-d H:i:s') : (string) $attributes['updated_at'];
        $expires = $attributes['expires_at'] instanceof \DateTimeInterface ? $attributes['expires_at']->format('Y-m-d H:i:s') : (string) $attributes['expires_at'];

        // Escape single quotes for SQL literals
        $scopesSql = str_replace("'", "''", $scopes);
        $createdSql = str_replace("'", "''", $created);
        $updatedSql = str_replace("'", "''", $updated);
        $expiresSql = str_replace("'", "''", $expires);

        $sql = "INSERT INTO oauth_access_tokens (id, user_id, client_id, scopes, revoked, created_at, updated_at, expires_at) VALUES ('{$id}', '{$userId}'::uuid, {$clientId}, '{$scopesSql}', {$revoked}, '{$createdSql}', '{$updatedSql}', '{$expiresSql}');";

        // Use unprepared to avoid PDO prepared statements and binding type issues
        DB::unprepared($sql);

        return PassportToken::where('id', $id)->first();
    }
}
