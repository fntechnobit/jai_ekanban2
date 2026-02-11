# Files Manifest - Deployment Package

**Package Date:** January 28, 2026
**Total Files:** 98 files

## Legend
- **[NEW]** - Newly added file
- **[MOD]** - Modified existing file
- **[DEL]** - File to be deleted from server

---

## Configuration Files

### Root Level
- **[MOD]** `.env.example` - Updated environment configuration example
- **[MOD]** `composer.lock` - Updated composer dependencies lock file

### Config Directory
- **[MOD]** `config/session.php` - Session configuration updates

---

## Application Files (app/)

### Config
- **[NEW]** `app/Config/CircuitTemplateConfig.php` - Circuit template configuration
- **[MOD]** `app/Config/ShikakeTemplateConfig.php` - Shikake template configuration updates

### Console Commands
- **[NEW]** `app/Console/Commands/GenerateSampleDataTemplate.php` - Generate sample data templates
- **[NEW]** `app/Console/Commands/GenerateTemplates.php` - Generate all templates
- **[DEL]** `app/Console/Commands/GenerateShikakeTemplates.php` - DEPRECATED: Delete this file

### Helpers
- **[MOD]** `app/Helpers/ImageHelper.php` - Image handling helper updates

### Controllers

#### Main Controllers
- **[NEW]** `app/Http/Controllers/DefectController.php` - Defect management controller

#### Master Data Controllers
- **[MOD]** `app/Http/Controllers/MasterData/MasterCircuitController.php` - Circuit master data controller
- **[MOD]** `app/Http/Controllers/MasterData/MasterShikakeController.php` - Shikake master data controller

#### Schedule Controllers
- **[MOD]** `app/Http/Controllers/Schedule/EkanbanCircuitController.php` - E-Kanban circuit controller
- **[MOD]** `app/Http/Controllers/Schedule/EkanbanShikakeController.php` - E-Kanban shikake controller

### Form Requests
- **[MOD]** `app/Http/Requests/UpdateBonderShikakeRequest.php` - Bonder shikake validation
- **[MOD]** `app/Http/Requests/UpdateDblCrimpShikakeRequest.php` - Double crimp shikake validation
- **[MOD]** `app/Http/Requests/UpdateJointShikakeRequest.php` - Joint shikake validation
- **[MOD]** `app/Http/Requests/UpdateShieldShikakeRequest.php` - Shield shikake validation
- **[MOD]** `app/Http/Requests/UpdateTwistShikakeRequest.php` - Twist shikake validation

### Imports (Excel Import Classes)
- **[MOD]** `app/Imports/BaseShikakeImport.php` - Base shikake import class
- **[MOD]** `app/Imports/MasterCircuitImport.php` - Circuit import
- **[MOD]** `app/Imports/MasterShikakeBonderImport.php` - Bonder shikake import
- **[MOD]** `app/Imports/MasterShikakeDblCrimpImport.php` - Double crimp shikake import
- **[MOD]** `app/Imports/MasterShikakeImport.php` - General shikake import
- **[MOD]** `app/Imports/MasterShikakeJointImport.php` - Joint shikake import
- **[MOD]** `app/Imports/MasterShikakeShieldImport.php` - Shield shikake import
- **[MOD]** `app/Imports/MasterShikakeTwistImport.php` - Twist shikake import

### Models

#### Schedule Models
- **[MOD]** `app/Models/AssyScheduleCircuit.php` - Assembly schedule circuit model
- **[MOD]** `app/Models/AssyScheduleShikake.php` - Assembly schedule shikake model

#### Defect Log Models
- **[NEW]** `app/Models/DefectLogCircuit.php` - Circuit defect log model
- **[NEW]** `app/Models/DefectLogShikake.php` - Shikake defect log model

#### Kanban Balance Models
- **[NEW]** `app/Models/KanbanBalanceCircuit.php` - Circuit kanban balance model
- **[NEW]** `app/Models/KanbanBalanceShikake.php` - Shikake kanban balance model

#### Master Data Models
- **[MOD]** `app/Models/MasterCircuit.php` - Master circuit model
- **[MOD]** `app/Models/MasterShikake.php` - Master shikake model
- **[MOD]** `app/Models/MasterShikakeBonder.php` - Bonder shikake model
- **[MOD]** `app/Models/MasterShikakeDblCrimp.php` - Double crimp shikake model
- **[MOD]** `app/Models/MasterShikakeJoint.php` - Joint shikake model
- **[MOD]** `app/Models/MasterShikakeShield.php` - Shield shikake model
- **[MOD]** `app/Models/MasterShikakeTwist.php` - Twist shikake model

### Services (Business Logic)
- **[NEW]** `app/Services/DefectService.php` - Defect management business logic
- **[MOD]** `app/Services/EkanbanCircuitService.php` - E-Kanban circuit service
- **[MOD]** `app/Services/EkanbanShikakeService.php` - E-Kanban shikake service
- **[NEW]** `app/Services/KanbanGeneratorService.php` - Kanban generation service
- **[MOD]** `app/Services/MasterCircuitService.php` - Circuit master data service
- **[MOD]** `app/Services/MasterShikakeService.php` - Shikake master data service
- **[MOD]** `app/Services/ScheduleVerificationService.php` - Schedule verification service

---

## Database Files (database/)

### Migrations (14 files)

#### January 12, 2026
- **[NEW]** `database/migrations/2026_01_12_210519_remove_released_date_from_child_process_tables.php`
  - Removes released_date column from child process tables

#### January 13, 2026
- **[NEW]** `database/migrations/2026_01_13_000001_alter_master_shikake_dbl_crimp_table.php`
  - Alters master_shikake_dbl_crimp table structure

#### January 18, 2026
- **[NEW]** `database/migrations/2026_01_18_025451_add_carline_to_master_shikake_and_master_circuit_tables.php`
  - Adds carline column to master tables

- **[NEW]** `database/migrations/2026_01_18_025949_remove_issue_barcode_kanban_released_note_from_master_tables.php`
  - Removes obsolete columns from master tables

#### January 20, 2026
- **[NEW]** `database/migrations/2026_01_20_000001_rename_bonder_no_to_address_no_in_master_shikake_shield_table.php`
  - Renames bonder_no to address_no in shield table

- **[NEW]** `database/migrations/2026_01_20_000002_create_kanban_balance_table.php`
  - Creates initial kanban_balance table (later separated)

- **[NEW]** `database/migrations/2026_01_20_000003_add_kanban_fields_to_assy_schedule_circuit_table.php`
  - Adds kanban-related fields to circuit schedule

- **[NEW]** `database/migrations/2026_01_20_000004_add_kanban_fields_to_assy_schedule_shikake_table.php`
  - Adds kanban-related fields to shikake schedule

- **[NEW]** `database/migrations/2026_01_20_000005_create_defect_log_table.php`
  - Creates initial defect_log table (later separated)

#### January 24, 2026
- **[NEW]** `database/migrations/2026_01_24_214105_add_released_note_and_cleanup_columns.php`
  - Adds released_note and cleanup-related columns

#### January 25, 2026
- **[NEW]** `database/migrations/2026_01_25_000001_drop_unique_circuit_group_constraint.php`
  - Drops unique constraint on circuit_group

#### January 27, 2026
- **[NEW]** `database/migrations/2026_01_27_000001_separate_kanban_balance_tables.php`
  - Separates kanban_balance into circuit and shikake tables

- **[NEW]** `database/migrations/2026_01_27_000002_separate_defect_log_tables.php`
  - Separates defect_log into circuit and shikake tables

#### January 28, 2026
- **[NEW]** `database/migrations/2026_01_28_000001_add_defect_menu.php`
  - Adds defect menu to system menus

---

## Documentation (docs/)

- **[NEW]** `docs/ANALISIS_CUTOFF_MASTER_CIRCUIT_2026.md` - Circuit cutoff analysis
- **[NEW]** `docs/PERBANDINGAN_RENCANA_VS_IMPLEMENTASI.md` - Plan vs implementation comparison
- **[NEW]** `docs/PLANNING_KANBAN_GENERATION.md` - Kanban generation planning

---

## Public Files (public/docs/)

### Excel Templates (6 files)
- **[MOD]** `public/docs/Template_Cutting.xlsx` - Updated cutting template
- **[MOD]** `public/docs/Template_Shikake_Bonder.xlsx` - Updated bonder template
- **[MOD]** `public/docs/Template_Shikake_Dbl_Crimp.xlsx` - Updated double crimp template
- **[MOD]** `public/docs/Template_Shikake_Joint.xlsx` - Updated joint template
- **[MOD]** `public/docs/Template_Shikake_Shield.xlsx` - Updated shield template
- **[MOD]** `public/docs/Template_Shikake_Twist.xlsx` - Updated twist template

---

## Views (resources/views/)

### Defect Views (NEW MODULE)
- **[NEW]** `resources/views/defect/cutting.blade.php` - Cutting defect recording page
- **[NEW]** `resources/views/defect/history.blade.php` - Defect history page
- **[NEW]** `resources/views/defect/shikake.blade.php` - Shikake defect recording page

### Master Data - Circuit Views
- **[MOD]** `resources/views/master_data/master_circuit/detail_modal.blade.php` - Circuit detail modal
- **[MOD]** `resources/views/master_data/master_circuit/form.blade.php` - Circuit form
- **[MOD]** `resources/views/master_data/master_circuit/index.blade.php` - Circuit listing page

### Master Data - Shikake Views
- **[MOD]** `resources/views/master_data/master_shikake/detail_modal.blade.php` - Shikake detail modal
- **[MOD]** `resources/views/master_data/master_shikake/form.blade.php` - Shikake form
- **[MOD]** `resources/views/master_data/master_shikake/index.blade.php` - Shikake listing page
- **[MOD]** `resources/views/master_data/master_shikake/view.blade.php` - Shikake view page

### Schedule - E-Kanban Circuit Views
- **[MOD]** `resources/views/schedule/ekanban_circuit/print_machine.blade.php` - Circuit machine print
- **[MOD]** `resources/views/schedule/ekanban_circuit/print_ticket.blade.php` - Circuit ticket print

### Schedule - E-Kanban Shikake Views
- **[MOD]** `resources/views/schedule/ekanban_shikake/actions.blade.php` - Shikake actions
- **[MOD]** `resources/views/schedule/ekanban_shikake/print_machine.blade.php` - Shikake machine print
- **[MOD]** `resources/views/schedule/ekanban_shikake/print_preview.blade.php` - General print preview
- **[MOD]** `resources/views/schedule/ekanban_shikake/print_ticket_generic.blade.php` - Generic ticket template

### Schedule - Print Ticket Views (Separated Preview and Print)

#### Bonder
- **[NEW]** `resources/views/schedule/ekanban_shikake/print_ticket_bonder_preview.blade.php` - Bonder preview
- **[NEW]** `resources/views/schedule/ekanban_shikake/print_ticket_bonder_print.blade.php` - Bonder print
- **[DEL]** `resources/views/schedule/ekanban_shikake/print_ticket_bonder.blade.php` - DEPRECATED

#### Double Crimp
- **[NEW]** `resources/views/schedule/ekanban_shikake/print_ticket_dbl_crimp_preview.blade.php` - Dbl crimp preview
- **[NEW]** `resources/views/schedule/ekanban_shikake/print_ticket_dbl_crimp_print.blade.php` - Dbl crimp print
- **[DEL]** `resources/views/schedule/ekanban_shikake/print_ticket_dbl_crimp.blade.php` - DEPRECATED

#### Joint
- **[NEW]** `resources/views/schedule/ekanban_shikake/print_ticket_joint_preview.blade.php` - Joint preview
- **[NEW]** `resources/views/schedule/ekanban_shikake/print_ticket_joint_print.blade.php` - Joint print
- **[DEL]** `resources/views/schedule/ekanban_shikake/print_ticket_joint.blade.php` - DEPRECATED

#### Shield
- **[NEW]** `resources/views/schedule/ekanban_shikake/print_ticket_shield_preview.blade.php` - Shield preview
- **[NEW]** `resources/views/schedule/ekanban_shikake/print_ticket_shield_print.blade.php` - Shield print
- **[DEL]** `resources/views/schedule/ekanban_shikake/print_ticket_shield.blade.php` - DEPRECATED

#### Twist
- **[NEW]** `resources/views/schedule/ekanban_shikake/print_ticket_twist_preview.blade.php` - Twist preview (renamed)
- **[NEW]** `resources/views/schedule/ekanban_shikake/print_ticket_twist_print.blade.php` - Twist print

---

## Routes

- **[MOD]** `routes/web.php` - Updated web routes with defect management routes

---

## Storage

- **[MOD]** `storage/app/.gitignore` - Storage gitignore updates
- **[NEW]** `storage/app/samples/.gitignore` - Sample data directory gitignore

---

## Files to DELETE from Server

These files are obsolete and must be removed:

1. `app/Console/Commands/GenerateShikakeTemplates.php`
2. `resources/views/schedule/ekanban_shikake/print_ticket_bonder.blade.php`
3. `resources/views/schedule/ekanban_shikake/print_ticket_dbl_crimp.blade.php`
4. `resources/views/schedule/ekanban_shikake/print_ticket_joint.blade.php`
5. `resources/views/schedule/ekanban_shikake/print_ticket_shield.blade.php`

---

## Summary Statistics

### By Status
- **New Files:** 40
- **Modified Files:** 53
- **Deleted Files:** 5
- **Total Files in Package:** 98

### By Category
- **Configuration:** 3 files
- **Application Code:** 44 files
- **Database Migrations:** 14 files
- **Documentation:** 3 files
- **Public Assets:** 6 files
- **Views:** 28 files
- **Routes:** 1 file
- **Storage:** 2 files

### By Type
- **PHP Files:** 87
- **Blade Templates:** 28
- **Excel Templates:** 6
- **Markdown Files:** 3
- **Configuration Files:** 3
- **Other:** 1

---

**End of Files Manifest**

Generated on: January 28, 2026
