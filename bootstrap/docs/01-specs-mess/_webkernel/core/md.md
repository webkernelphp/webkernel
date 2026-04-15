Let's follow the logic. Once an app-owner/super-admin (sometimes it's the organisation
or the person that sets up the infrastructure) has installed his instance
(because it always starts empty), he must be able to declare himself as app-owner
with a prompt: "This Webkernel instance needs its app-owner to be invited"
and a button [I am the app-owner].

From there, the app-owner will delegate the work of his organisation.

Since sending invites and verifying the first account requires email,
there must be a step to configure the mailer.

The app-owner or delegated person will be asked to:

- Map the organisation
- Create Delegates => people who can manage the whole instance with levels
  of privilege based on instance permissions (filtering users, assigning roles, etc.)
- Create Departments => logical grouping of users and roles

Once the organisation is mapped, the Webkernel instance will be used to:

- Use a Marketplace Module (with predefined roles and permissions)
- Import custom modules from GitHub
- Create accounts for third-party developers to add modules
- Manage organisation-wide roles and permissions without exposing infrastructure

In Webkernel there shall be no need to give access to servers or databases
when not extremely necessary. Everything must be doable with no code,
through delegation, modular permissions, and secure instance-level configuration.

Side Note:
It must be possible to manage multiple organisations with the same modules
(a kind of multitenancy, either per row or per database — whichever proves simpler).
Ultimately, routing across multiple domains must also be supported.
