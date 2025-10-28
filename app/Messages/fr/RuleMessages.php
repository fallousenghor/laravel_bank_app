<?php

namespace App\Messages\fr;

enum RuleMessages: string
{
    // Messages pour NciRule
    case NCI_STRING = 'Le NCI doit être une chaîne de caractères.';
    case NCI_LONGUEUR = 'Le NCI doit contenir exactement 13 chiffres.';
    case NCI_CHIFFRES = 'Le NCI ne doit contenir que des chiffres.';
    case NCI_COMMENCE_PAR_1_OU_2 = 'Le NCI doit commencer par 1 ou 2.';
    case NCI_SEQUENCE_REPETITIVE = 'Le NCI ne semble pas être un numéro valide (séquence trop répétitive).';

    // Messages pour TelephoneRule
    case TELEPHONE_STRING = 'Le numéro de téléphone doit être une chaîne de caractères.';
    case TELEPHONE_LONGUEUR = 'Le numéro de téléphone doit contenir 9 chiffres après le préfixe.';
    case TELEPHONE_CHIFFRES = 'Le numéro de téléphone ne doit contenir que des chiffres.';
    case TELEPHONE_INDICATIF = 'Le numéro de téléphone doit commencer par un indicatif valide (70, 76, 77, 78).';
    case TELEPHONE_INVALIDE = 'Le numéro de téléphone ne semble pas être valide.';

    // Messages pour d'autres règles personnalisées
    case EMAIL_DOMAINE_AUTORISE = 'Ce domaine email n\'est pas autorisé.';
    case PASSWORD_COMPLEXITE = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.';
    case AGE_MINIMUM = 'Vous devez avoir au moins 18 ans.';
    case MONTANT_MAXIMUM = 'Le montant dépasse la limite autorisée.';
}
