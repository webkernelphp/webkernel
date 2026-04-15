Let's follow the logic. Once a Super-Admin (the person that sets up the infrastructure) has installed the instance (because it always starts empty), the system boots into a secure setup state. 

To prevent unauthorized hijacking of the public URL, the system displays a prompt: *"This Webkernel instance is empty and requires initialization."* It requires a one-time "Setup Token" (injected via infrastructure environment variables or CLI) to proceed. Once validated, the user is presented with two buttons: **[I am the Super-Admin]** or **[Generate Invite Link for Super-Admin]**. Since the mailer is not yet active, the invite option generates a secure, one-time URL on-screen to be manually securely transmitted to the intended owner.

Once the Super-Admin claims the instance, the immediate next phase is Instance-Level Configuration. The Super-Admin must:
- Configure the Mailer (to allow standard email invites moving forward).
- Configure the encrypted "Instance Secrets Vault" (to securely store global API keys, OAuth tokens, and GitHub Webhooks without exposing them to standard users).

From there, because Webkernel is inherently multi-tenant, the Super-Admin does not map the organisation directly. Instead, they create a Tenant/Organisation Namespace and invite an "Org-Admin" to manage it.

The Org-Admin (or their delegated person) will then log into their isolated tenant and be asked to:
- Map their specific organisation.
- Create Delegates => people who can manage that specific tenant with levels of privilege based on instance permissions (filtering users, assigning roles, etc.) per organisation.
- Create Departments => logical grouping of users and roles within that tenant.

Once the organisation is mapped, the Webkernel instance will allow the Org-Admin to:
- Use a Marketplace Module (with predefined roles and permissions).
- Import custom modules from GitHub (leveraging the global secrets securely stored by the Super-Admin).
- Create accounts for third-party developers to add modules to their specific tenant.
- Manage organisation-wide roles and permissions without exposing infrastructure.

In Webkernel, there shall be no need to give access to servers or databases for daily operations. Everything must be doable with no code, through delegation, modular permissions, and secure instance-level configuration. However, a strict "Break-Glass" CLI failsafe must exist at the infrastructure level (accessible only via server terminal) to reset the Super-Admin account, bypass crashing modules, or repair a broken mailer, ensuring the instance is never permanently bricked.

Side Note: 
It must be possible to manage multiple isolated organisations with the same or different modules (multi-tenancy, either per row or per database — whichever proves simpler). Ultimately, instance-level routing across multiple custom domains (mapping specific domains to specific tenant organisations) must also be supported.

================================================================================
> END_OF_SPECIFICATION
