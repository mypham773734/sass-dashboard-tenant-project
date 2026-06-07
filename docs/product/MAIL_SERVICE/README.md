# Generic Mail Service

**Status:** Planning  
**Last Updated:** 2026-06-06
**Estimated effort:** 12-18 hours

## Overview

Unified mail sending system with config-based email types and pluggable handlers.

- Audit digest (daily scheduled)
- User invitations (on-demand)
- Tenant notifications (on-demand)
- Extensible for more types

## Key: NO Database Tables!

All configuration in config/mail-service.php — no migrations needed.

## Quick Links

- [01-REQUIREMENTS.md](./01-REQUIREMENTS.md) - Email types, config structure, requirements
- [02-ARCHITECTURE.md](./02-ARCHITECTURE.md) - Service design, handlers, data flow  
- [03-IMPLEMENTATION_PLAN.md](./03-IMPLEMENTATION_PLAN.md) - Tasks, phases, file structure

## Architecture

\\\
MailService
  ├── Handlers (AuditDigest, UserInvitation, TenantNotification)
  ├── SendEmailJob (queue)
  ├── Mailable classes
  └── config/mail-service.php
\\\

Each handler:
- Implements EmailHandlerInterface
- Builds email content
- Returns EmailDTO
- Is registered in config

New email type? Just add config + handler. Done.
