# TODO: US 2.6 Archivage de compte

## Tasks
- [x] Create migration to add date_debut_blocage and date_fin_blocage to comptes table
- [x] Update Compte model to include new fields in fillable
- [x] Add bloquer method to CompteController
- [x] Create ArchiveComptesJob
- [x] Create UnarchiveComptesJob
- [x] Schedule jobs in Kernel.php
- [x] Update routes to include bloquer endpoint
- [x] Test the implementation
