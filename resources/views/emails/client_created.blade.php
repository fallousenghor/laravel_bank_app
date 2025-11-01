<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Création de votre compte bancaire</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #222; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background:#1f2937; color: #fff; padding: 12px; text-align: center; }
        .content { background: #fff; padding: 20px; border: 1px solid #e5e7eb; }
        .btn { display:inline-block; background:#2563eb; color:#fff; padding:10px 16px; text-decoration:none; border-radius:4px; }
        .muted { color:#6b7280; font-size:0.9rem; }
        .footer { font-size:0.8rem; color:#9ca3af; margin-top:16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Votre nouveau compte bancaire</h2>
        </div>

        <div class="content">
            <p>Bonjour {{ $user->prenom ?? $user->nom ?? 'client' }},</p>

            <p>Un compte bancaire a été créé pour vous sur notre plateforme. Vous pouvez vous connecter en utilisant les identifiants suivants :</p>

            <ul>
                <li><strong>Adresse e-mail :</strong> {{ $user->email ?? 'non fournie' }}</li>
                <li><strong>Mot de passe :</strong> {{ $password }}</li>
            </ul>

            <p>Pour votre sécurité, nous vous recommandons de changer immédiatement ce mot de passe après la première connexion.</p>

            @if(config('app.url'))
                <p>
                    <a class="btn" href="{{ rtrim(config('app.url'), '/') }}/login">Se connecter</a>
                </p>
            @endif

            <p class="muted">Si vous n'avez pas demandé l'ouverture de ce compte, contactez notre support immédiatement.</p>

            <p class="footer">Cordialement,<br>L'équipe de la banque</p>
        </div>
    </div>
</body>
</html>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Création de compte bancaire</title>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
    .content { padding: 20px; background-color: #f9f9f9; }
    .credentials { background-color: #fff; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
    .warning { color: #dc3545; font-weight: bold; }
    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Création de votre compte bancaire</h1>
    </div>

    <div class="content">
      <p>Bonjour {{ $user->prenom ?? '' }} {{ $user->nom ?? '' }},</p>

      <p>Votre compte bancaire a été créé avec succès. Pour des raisons de sécurité, nous vous avons généré des identifiants temporaires que vous devrez changer lors de votre première connexion.</p>

      <div class="credentials">
        <h3>Vos identifiants temporaires :</h3>
        <ul>
          <li><strong>Email :</strong> {{ $user->email }}</li>
          <li><strong>Mot de passe temporaire :</strong> {{ $password }}</li>
        </ul>
        <p class="warning">⚠️ Important : Ce mot de passe est temporaire et doit être changé immédiatement après votre première connexion.</p>
      </div>

      <p><strong>Code de vérification SMS :</strong> <span style="font-size: 18px; font-weight: bold; color: #007bff;">{{ $code }}</span></p>
      <p>Ce code vous sera demandé pour valider votre numéro de téléphone.</p>

      <p>Pour accéder à votre compte, veuillez vous connecter à notre application mobile ou site web et suivre les instructions de changement de mot de passe.</p>

      <p>Si vous n'avez pas demandé la création de ce compte, veuillez contacter immédiatement notre service client.</p>

      <p>Cordialement,<br>L'équipe de la Banque</p>
    </div>

    <div class="footer">
      <p>Cette adresse email est générée automatiquement. Merci de ne pas y répondre.</p>
      <p>&copy; 2025 Banque - Tous droits réservés</p>
    </div>
  </div>
</body>
</html>
