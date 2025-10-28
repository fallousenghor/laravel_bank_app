<?php

namespace App\Messages\fr;

enum ValidationMessages: string
{
    // Messages de validation pour les comptes
    case COMPTE_ID_UUID = "L'identifiant du compte doit être un UUID valide.";
    case COMPTE_ID_EXISTS = 'Le compte spécifié n\'existe pas.';
    case COMPTE_TYPE_IN = "Le type de compte doit être 'epargne' ou 'cheque'.";
    case COMPTE_STATUT_IN = "Le statut doit être 'actif', 'bloque' ou 'ferme'.";
    case COMPTE_SOLDE_NUMERIC = 'Le solde doit être un nombre.';
    case COMPTE_SOLDE_MIN = 'Le solde ne peut pas être négatif.';
    case COMPTE_DEVISE_IN = "La devise doit être 'FCFA' ou 'EUR'.";

    // Messages de validation pour les clients
    case CLIENT_PRENOM_REQUIRED = 'Le prénom est requis.';
    case CLIENT_PRENOM_STRING = 'Le prénom doit être une chaîne de caractères.';
    case CLIENT_PRENOM_MAX = 'Le prénom ne peut pas dépasser 255 caractères.';
    case CLIENT_NOM_REQUIRED = 'Le nom est requis.';
    case CLIENT_NOM_STRING = 'Le nom doit être une chaîne de caractères.';
    case CLIENT_NOM_MAX = 'Le nom ne peut pas dépasser 255 caractères.';
    case CLIENT_EMAIL_REQUIRED = 'L\'email est requis.';
    case CLIENT_EMAIL_EMAIL = 'L\'email doit être valide.';
    case CLIENT_EMAIL_UNIQUE = 'Cet email est déjà utilisé.';
    case CLIENT_TELEPHONE_REQUIRED = 'Le numéro de téléphone est requis.';
    case CLIENT_TELEPHONE_UNIQUE = 'Ce numéro de téléphone est déjà utilisé.';
    case CLIENT_NCI_REQUIRED = 'Le NCI est requis.';
    case CLIENT_NCI_UNIQUE = 'Ce NCI est déjà utilisé.';

    // Messages de validation pour les transactions
    case TRANSACTION_TYPE_IN = "Le type doit être 'debit' ou 'credit'.";
    case TRANSACTION_MONTANT_NUMERIC = 'Le montant doit être un nombre.';
    case TRANSACTION_MONTANT_MIN = 'Le montant doit être positif.';
    case TRANSACTION_DESCRIPTION_STRING = 'La description doit être une chaîne de caractères.';
    case TRANSACTION_DESCRIPTION_MAX = 'La description ne peut pas dépasser 1000 caractères.';

    // Messages de validation pour les dates de blocage
    case DATE_DEBUT_BLOCAGE_REQUIRED = 'La date de début de blocage est requise.';
    case DATE_DEBUT_BLOCAGE_DATE = 'La date de début de blocage doit être une date valide.';
    case DATE_DEBUT_BLOCAGE_AFTER = 'La date de début de blocage doit être dans le futur.';
    case DATE_FIN_BLOCAGE_DATE = 'La date de fin de blocage doit être une date valide.';
    case DATE_FIN_BLOCAGE_AFTER = 'La date de fin de blocage doit être après la date de début.';

    // Messages de validation pour la pagination
    case PAGE_INTEGER = 'Le numéro de page doit être un entier.';
    case PAGE_MIN = 'Le numéro de page doit être au minimum 1.';
    case LIMIT_INTEGER = 'La limite doit être un entier.';
    case LIMIT_MIN = 'La limite doit être au minimum 1.';
    case LIMIT_MAX = 'La limite ne peut pas dépasser 100.';

    // Messages de validation pour les filtres
    case SEARCH_STRING = 'Le terme de recherche doit être une chaîne de caractères.';
    case SEARCH_MAX = 'Le terme de recherche ne peut pas dépasser 255 caractères.';
    case SORT_IN = "Le tri doit être 'dateCreation', 'solde' ou 'titulaire'.";
    case ORDER_IN = "L'ordre doit être 'asc' ou 'desc'.";
}
