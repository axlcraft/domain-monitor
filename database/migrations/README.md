# Database Migrations

## Fresh Installation (v1.1.0+)

For new installations, use the consolidated schema:

- **`000_initial_schema_v1.1.0.sql`** - Complete database schema for v1.1.0

**Install via:** Web installer at `/install`

## Incremental Migrations (v1.0.0 → v1.1.0)

If upgrading from v1.0.0, these incremental migrations will be applied:

- `001_create_tables.sql` - Core tables (domains, groups, channels, logs)
- `002_create_users_table.sql` - Users table
- `003_add_whois_fields.sql` - WHOIS data fields
- `004_create_tld_registry_table.sql` - TLD registry
- `005_update_tld_import_logs.sql` - Import logs updates
- `006_add_complete_workflow_import_type.sql` - Workflow import type
- `007_add_app_and_email_settings.sql` - Application settings
- `008_add_notes_to_domains.sql` - Domain notes field
- `009_add_authentication_features.sql` - Authentication system
- `010_add_app_version_setting.sql` - Version setting

**Upgrade via:** Web updater at `/install/update`

## Migration System

The installer automatically:
- Detects if this is a fresh install or upgrade
- Uses consolidated schema for fresh installs
- Uses incremental migrations for upgrades
- Tracks executed migrations in `migrations` table
- Prevents re-running completed migrations

