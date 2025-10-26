<!DOCTYPE html>
<html>
<head>
    <title>Création de compte bancaire</title>
</head>
<body>
    <h1>Bienvenue chez {{ config('app.name') }}</h1>

    <p>Cher(e) {{ $compte->titulaire }},</p>

    <p>Nous sommes ravis de vous informer que votre compte bancaire a été créé avec succès.</p>

    <p>Voici les détails de votre compte :</p>
    <ul>
        <li>Numéro de compte : {{ $compte->numeroCompte }}</li>
        <li>Type de compte : {{ $compte->type }}</li>
        <li>Solde initial : {{ number_format($compte->solde, 2) }} {{ $compte->devise }}</li>
        <li>Date de création : {{ $compte->dateCreation->format('d/m/Y H:i') }}</li>
    </ul>

    <p>Si vous avez des questions ou besoin d'assistance, n'hésitez pas à nous contacter.</p>

    <p>Cordialement,<br>
    L'équipe {{ config('app.name') }}</p>
</body>
</html>
