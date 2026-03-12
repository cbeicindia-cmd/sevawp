# Hostinger Deployment Guide — SEVA SETU KENDRA

## 1) Install WordPress on Hostinger
1. Login to Hostinger hPanel.
2. Open **Websites → Manage → Auto Installer**.
3. Install latest WordPress and complete admin setup.

## 2) Upload Custom Theme
1. Zip `wp-content/themes/seva-setu-theme`.
2. In WordPress Admin: **Appearance → Themes → Add New → Upload Theme**.
3. Activate **Seva Setu Theme**.

## 3) Upload Custom Plugin
1. Zip `wp-content/plugins/seva-setu-platform`.
2. In WordPress Admin: **Plugins → Add New → Upload Plugin**.
3. Activate **Seva Setu Platform**.

## 4) Install Required Plugins
Install and activate:
- User Registration Plugin
- User Role Editor
- WP User Manager
- Advanced Custom Fields

## 5) Import 3000+ Schemes Dataset
### Option A: WP All Import (UI)
1. Install WP All Import.
2. Upload `data/schemes-3000.csv`.
3. Map to post type `gov_scheme` and meta keys from `docs/database-schema.md`.

### Option B: WP-CLI script
```bash
wp eval-file scripts/import-schemes.php
```

## 6) Create Required Pages
Create pages and place shortcodes:
- Home
- Government Schemes: `[seva_setu_scheme_search]`
- Become Agent: `[seva_setu_agent_registration]`
- Citizen Portal: `[seva_setu_citizen_portal]`
- Contact

Optional shortcodes:
- Agent Apply Form: `[seva_setu_apply_form]`
- AI Finder: `[seva_setu_ai_finder]`

## 7) Configure Roles and Access
- Ensure users have roles: Administrator / Agent / Citizen.
- Approve agents from **Users → Edit User → Agent Approval**.

## 8) Production Hardening
- Enable SSL.
- Configure SMTP for emails.
- Integrate SMS OTP provider for mobile verification.
- Use caching/CDN and daily backups.
