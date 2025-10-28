# Refactoring CompteController - Déplacer la logique métier vers les modèles/repositories

## Tâches à accomplir

### 1. Ajouter des méthodes dans CompteRepository
- [x] Ajouter `getFilteredComptesPaginated` pour remplacer la logique de `index`
- [ ] Ajouter `getUserComptes` pour remplacer la logique de `mine`
- [x] Ajouter `createCompteWithClient` pour remplacer la logique de `store`
- [x] Ajouter `updateCompteClientInfo` pour remplacer la logique de `update`
- [x] Ajouter `bloquerCompte` pour remplacer la logique de `bloquer`
- [x] Ajouter `fermerCompte` pour remplacer la logique de `destroy`

### 2. Modifier CompteController
- [x] Simplifier la méthode `index` pour utiliser `getFilteredComptesPaginated`
- [x] Simplifier la méthode `mine` pour utiliser `getUserComptes` (utilise déjà getActiveComptesByUserId)
- [x] Simplifier la méthode `store` pour utiliser `createCompteWithClient`
- [x] Simplifier la méthode `update` pour utiliser `updateCompteClientInfo`
- [x] Simplifier la méthode `bloquer` pour utiliser `bloquerCompte`
- [x] Simplifier la méthode `destroy` pour utiliser `fermerCompte`
- [x] Remplacer les réponses par les nouvelles ressources API
- [x] Remplacer tous les textes hardcodés par les enums de messages

### 3. Tests et validation
- [x] Tester tous les endpoints pour s'assurer que la fonctionnalité est préservée (tests lancés)
- [x] Vérifier les logs d'erreur (pas d'erreurs détectées dans les modifications)
- [x] S'assurer que les règles métier sont respectées (toutes les règles préservées dans les repositories)
