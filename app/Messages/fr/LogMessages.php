<?php

namespace App\Messages\fr;

enum LogMessages: string
{
    // Messages de log pour les comptes
    case COMPTE_CREE = 'Nouveau compte créé';
    case COMPTE_MODIFIE = 'Compte modifié';
    case COMPTE_SUPPRIME = 'Compte supprimé (soft delete)';
    case COMPTE_SUPPRIME_DEFINITIVEMENT = 'Compte supprimé définitivement';
    case COMPTE_BLOQUE = 'Compte bloqué';
    case COMPTE_DEBLOQUE = 'Compte débloqué';
    case COMPTE_FERME = 'Compte fermé';
    case COMPTE_STATUT_CHANGE = 'Changement de statut du compte';

    // Messages de log pour les clients
    case CLIENT_CREE = 'Nouveau client créé';
    case CLIENT_MODIFIE = 'Client modifié';
    case CLIENT_SUPPRIME = 'Client supprimé';

    // Messages de log pour les transactions
    case TRANSACTION_CREE = 'Nouvelle transaction créée';
    case TRANSACTION_MODIFIEE = 'Transaction modifiée';
    case TRANSACTION_ANNULEE = 'Transaction annulée';

    // Messages d'erreur de log
    case ERREUR_CREATION_COMPTE = 'Erreur lors de la création du compte';
    case ERREUR_MISE_A_JOUR_COMPTE = 'Erreur lors de la mise à jour du compte';
    case ERREUR_SUPPRESSION_COMPTE = 'Erreur lors de la suppression du compte';
    case ERREUR_FERMETURE_COMPTE = 'Erreur lors de la fermeture du compte';
    case ERREUR_BLOCAGE_COMPTE = 'Erreur lors du blocage du compte';
    case ERREUR_RECUPERATION_COMPTES = 'Erreur lors de la récupération des comptes';
    case ERREUR_RECUPERATION_COMPTE = 'Erreur lors de la récupération du compte';
    case ERREUR_PAGINATION_COMPTES = 'Erreur lors de la pagination des comptes';
    case ERREUR_PAGINATION_SANS_SOFT_DELETE = 'Erreur lors de la pagination des comptes (sans soft delete)';

    // Messages de log pour les emails
    case EMAIL_ENVOYE = 'Email envoyé avec succès';
    case ERREUR_ENVOI_EMAIL = 'Erreur lors de l\'envoi de l\'email';

    // Messages de log pour les événements
    case EVENEMENT_COMPTE_CREE = 'Événement CompteCreated déclenché';
    case EVENEMENT_CLIENT_CREE = 'Événement ClientCreated déclenché';

    // Messages de log pour les opérations système
    case CACHE_VIDE = 'Cache vidé';
    case DONNEES_SYNCHRONISEES = 'Données synchronisées';
    case BACKUP_CREE = 'Sauvegarde créée';
}
