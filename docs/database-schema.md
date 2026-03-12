# SEVA SETU KENDRA — Database Schema

## WordPress Core Entities Used
- `wp_users` for Agent and Citizen accounts.
- `wp_usermeta` for profile and verification/approval flags.
- `wp_posts` + `wp_postmeta` for schemes and applications.

## Custom Post Types

### `gov_scheme`
Stored in `wp_posts` with `post_type = gov_scheme`.

Meta keys:
- `_seva_setu_state`
- `_seva_setu_department`
- `_seva_setu_eligibility`
- `_seva_setu_benefits`
- `_seva_setu_documents_required`
- `_seva_setu_application_process`
- `_seva_setu_official_website`
- `_seva_setu_min_income`
- `_seva_setu_max_income`
- `_seva_setu_min_age`
- `_seva_setu_max_age`
- `_seva_setu_gender`
- `_seva_setu_target_category`

### `scheme_application`
Stored in `wp_posts` with `post_type = scheme_application`.

Meta keys:
- `_seva_setu_citizen_name`
- `_seva_setu_agent_name`
- `_seva_setu_scheme_name`
- `_seva_setu_status` (Submitted/Processing/Approved/Rejected)
- `_seva_setu_documents`
- `_seva_setu_service_fee`
- `_seva_setu_agent_commission`
- `_seva_setu_platform_fee`

## User Meta Keys

### Agent (`role = agent`)
- `agent_status` (pending/approved/rejected)
- `email_verified`
- `mobile_verified`
- `agent_mobile`
- `agent_aadhar`
- `agent_pan`
- `agent_state`
- `agent_district`
- `agent_address`
- `agent_education`

### Citizen (`role = citizen`)
- `citizen_mobile`
- `citizen_state`
- `citizen_district`
- `citizen_income`
- `citizen_category`
