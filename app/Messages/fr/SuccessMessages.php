<?php

namespace App\Messages\fr;

enum SuccessMessages: string
{
    // Messages de succès pour les comptes
    case COMPTE_CREE = 'Compte créé avec succès';
    case COMPTE_RECUPERE = 'Compte récupéré avec succès';
    case COMPTE_MIS_A_JOUR = 'Compte mis à jour avec succès';
    case COMPTE_SUPPRIME = 'Compte supprimé avec succès';
    case COMPTE_BLOQUE = 'Compte bloqué avec succès';
    case COMPTE_DEBLOQUE = 'Compte débloqué avec succès';
    case COMPTE_FERME = 'Compte fermé avec succès';

    // Messages de succès pour les clients
    case CLIENT_CREE = 'Client créé avec succès';
    case CLIENT_MODIFIE = 'Client modifié avec succès';
    case CLIENT_SUPPRIME = 'Client supprimé avec succès';

    // Messages de succès pour les transactions
    case TRANSACTION_EFFECTUEE = 'Transaction effectuée avec succès';
    case TRANSACTION_ANNULEE = 'Transaction annulée avec succès';

    // Messages de succès généraux
    case OPERATION_REUSSIE = 'Opération réalisée avec succès';
    case DONNEES_ENREGISTREES = 'Données enregistrées avec succès';
    case DONNEES_MODIFIEES = 'Données modifiées avec succès';
    case DONNEES_SUPPRIMEES = 'Données supprimées avec succès';

    // Messages de succès pour les listes
    case COMPTES_RECUPERES = 'Comptes récupérés';
    case TRANSACTIONS_RECUPEREES = 'Transactions récupérées';
    case CLIENTS_RECUPERES = 'Clients récupérés';
}
