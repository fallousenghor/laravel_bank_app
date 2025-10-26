# TODO: Implement US 2.2 - Créer un nouveau compte

## Database Changes
- [x] Create migration to add 'devise' field to comptes table (default 'FCFA')
- [x] Create migration to add 'nci' and 'code' fields to users table

## Validation Rules
- [x] Create App/Rules/TelephoneRule.php for Senegalese telephone validation
- [x] Create App/Rules/NciRule.php for Senegalese NCI validation

## Request Classes
- [x] Create App/Http/Requests/StoreCompteRequest.php with validation rules

## Controller
- [x] Add store method to CompteController with logic for:
  - Check if client exists by telephone
  - If not, create client with generated password and code
  - Create account with generated numero
  - Fire event for notifications

## Events & Listeners
- [x] Create App/Events/ClientCreated.php event
- [x] Create App/Listeners/SendClientNotification.php listener for email and SMS

## Middleware
- [x] Create App/Http/Middleware/LoggingMiddleware.php for logging operations

## Model Updates
- [x] Add custom solde accessor to Compte model (calculated from transactions)

## Routes
- [x] Add POST route for comptes in routes/api.php

## Tests
- [x] Add test for store method in CompteControllerTest.php

## Documentation
- [x] Add Swagger documentation for the new endpoint
