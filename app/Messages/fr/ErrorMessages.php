<?php

namespace App\Messages\fr;

enum ErrorMessages: string
{
    // Erreurs générales
    case ERREUR_INTERNE = "Erreur interne du serveur";
    case AUTHENTIFICATION_REQUISE = 'Authentification requise ou paramètre admin_id';
    case ACCES_NON_AUTORISE = 'Accès non autorisé';
    case PARAMETRE_ADMIN_ID_REQUIS = "Paramètre 'user_id' requis lorsque non authentifié";
    case ID_ADMINISTRATEUR_REQUIS = "ID administrateur requis";
    case ADMIN_REQUIS_BLOQUER_COMPTE = "Seul un administrateur peut bloquer un compte";
    case COMPTE_NON_TROUVE = "Compte non trouvé";

    // Erreurs de création
    case ERREUR_CREATION_COMPTE = "Erreur lors de la création du compte";
    case ERREUR_CREATION_CLIENT = "Erreur lors de la création du client";

    // Erreurs de mise à jour
    case ERREUR_MISE_A_JOUR_COMPTE = "Erreur lors de la mise à jour du compte";
    case ERREUR_MISE_A_JOUR_CLIENT = "Erreur lors de la mise à jour des informations client";

    // Erreurs de suppression
    case ERREUR_SUPPRESSION_COMPTE = "Erreur lors de la suppression du compte";
    case ERREUR_FERMETURE_COMPTE = "Erreur lors de la fermeture du compte";

    // Erreurs de blocage
    case ERREUR_BLOCAGE_COMPTE = "Erreur lors du blocage du compte";
    case COMPTE_NON_EPARGNE = "Seul un compte d'épargne peut être bloqué";

    // Erreurs de récupération
    case ERREUR_RECUPERATION_COMPTES = "Erreur lors de la récupération des comptes";
    case ERREUR_RECUPERATION_COMPTE = "Erreur lors de la récupération du compte";

    // Erreurs de pagination
    case ERREUR_PAGINATION = "Erreur lors de la pagination des comptes";

    // Erreurs de validation
    case DONNEES_INVALIDE = "Les données fournies sont invalides";
    case CHAMP_REQUIS = "Ce champ est requis";
    case FORMAT_INVALIDE = "Le format de ce champ est invalide";

    // Erreurs métier
    case SOLDE_INSUFFISANT = "Solde insuffisant pour cette opération";
    case COMPTE_DEJA_BLOQUE = "Ce compte est déjà bloqué";
    case COMPTE_DEJA_FERME = "Ce compte est déjà fermé";
    case OPERATION_NON_AUTORISEE = "Cette opération n'est pas autorisée sur ce type de compte";
}
