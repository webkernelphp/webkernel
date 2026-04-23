# Webkernel™ Foundation

Webkernel™ is a modular, open-core ecosystem engineered for building multi-brand and multi-platform applications. By merging the bootstrap layer with Webkernel's source code, it provides a reference-grade foundation where initialization, dependencies, and runtime services are pre-configured for immediate deployment.

### Philosophy & Sovereignty

The project is held by **Yassine El Moumen** to address the need for digital sovereignty. Webkernel™ serves as a strategic lever, allowing businesses and organizations to master their own software infrastructure. It is built on the principle that software should act as a reliable, automated workforce under your direct command, free from the restrictive roadmaps or pricing models of major cloud conglomerates.

**Connect with the Architect:** [LinkedIn](https://www.linkedin.com/in/elmoumenyassine/) | [Website](https://webkernelphp.com) | [+212 6 2099 0692](tel:00212620990692)

<img src="https://raw.githubusercontent.com/numerimondes/.github/refs/heads/main/assets/brands/webkernel/identity/webkernel-small-banner-FOSS.png" width="100%">

---

## Release Information

The current release data is maintained in `webkernel/fast-boot.php` and stamped automatically by the release tool. The release tool (`webkernel/Makefile.php`) reads exact runtime requirements directly from `composer.lock` and minimum compatibility constraints from `composer.json` — no manual editing required.

```
version          1.0.1
semver           1.0.1+1000
codename         sovereign
channel          stable
requires         php 8.4.x · laravel 13.x · filament 5.x · livewire 4.x
compatible_with  php 8.3.0 · laravel 13.0.0 · filament 5.0.0
```

To release a new version (authorized Numerimondes personnel only):

```bash
php bootstrap/webkernel/Makefile.php keygen
php bootstrap/webkernel/Makefile.php patch --key=<generated-key>
```

---

## Core Integrity & Governance

The bootstrap directory is the trust anchor of every Webkernel instance. It contains the initialization layer, integrity verification system, and runtime configuration that guarantees the authenticity, security, and interoperability of all modules loaded into the instance.

**Any modification to the bootstrap directory:**

- Voids eligibility for Official Recognition and certification
- Removes entitlement to support from Numerimondes
- Invalidates the Core integrity chain for the affected instance
- May affect module activation and remote verification

This is not a commercial constraint — it is a technical necessity that protects the licensee as much as it protects Numerimondes. The integrity chain is what makes it possible to safely load modules from diverse sources, including public registries, private repositories, and the Webkernel marketplace.

Webkernel supports two integrity verification modes:

- **Remote verification** — standard mode, communicates with Numerimondes verification servers. Available on all tiers.
- **Local verification** — fully air-gapped mode, no outbound connection required. Available exclusively under the Sovereign Tier for governments, critical infrastructure operators, and regulated institutions.

---

## Structure

```
bootstrap/
├── app.php                           application entry point
├── composer.json                     runtime dependencies
├── composer-root.json                root-level merge hints for the host project
├── docs/                             specifications and working documents
│   ├── 00-older-files/               archived legacy docs and style rules
│   ├── 01-specs-mess/                in-progress specs (webapp-builder, aptitudes …)
│   └── XX-TheDocs/                   official documentation source
├── tests/                            PHPUnit suite (Feature + Unit)
└── webkernel/
    ├── fast-boot.php                 kernel boot — constants, PSR-4, release data
    ├── installer-guard.php           first-boot installation guard
    ├── Makefile.php                  release tool (authorized personnel only)
    ├── aptitudes/                    cross-cutting capabilities (Webkernel\* namespaces)
    │   ├── async/                    Webkernel\Async\      — Pool, Promise
    │   ├── components/               Webkernel\Components\ — Filament + WebsiteBuilder
    │   ├── connectors/               Webkernel\Connectors\ — Git, email, SMS, chat …
    │   │   ├── contracts/
    │   │   ├── facades/              SourceRegistry and other connector facades
    │   │   ├── src/                  adapter implementations by domain
    │   │   └── traits/
    │   ├── generators/               Webkernel\Generators\ — GradientImage, UniqueId
    │   ├── instance/                 Webkernel\Businesses\ / Webkernel\Users\
    │   ├── pages/                    Webkernel\Pages\
    │   ├── panels/                   Webkernel\Panels\
    │   ├── plugins/                  Webkernel\Plugins\
    │   ├── query-builder/            Webkernel\Query\
    │   └── traits/                   Webkernel\Traits\
    ├── platform/
    │   ├── _backend/                 Webkernel\System\ — WebkernelAPI + managers
    │   ├── _back_office/             Filament panels (sys_core, sys_biz, installer)
    │   ├── _config/                  stubs and platform configuration
    │   ├── arcanes/                  Webkernel\Arcanes\ — CLI, scaffold, module tools
    │   ├── database/                 migrations and seeders
    │   ├── providers/                service providers (view paths, render hooks …)
    │   └── routing/                  Webkernel\Routes\ — RootController + routes
    ├── src/                          Webkernel\ root namespace (Process, WebApp …)
    └── support/
        ├── boot/                     boot sequence — loaded by fast-boot.php
        │   ├── boot-actions/         010-050 sequential boot steps
        │   ├── boot-constants/       010-070 constant definitions
        │   ├── boot-services/        010-090 service bootstrappers
        │   ├── platform-actions/     platform helper loaders
        │   ├── app-models/           App\Models\ — SQLite-backed app models
        │   └── _dist/                compiled static assets (SVG, fonts)
        ├── commands/                 Webkernel\Commands\ — artisan commands
        ├── quick_touch/              Webkernel\QuickTouch\ — context-menu system
        └── views/                   Blade components, pages, panel layouts
```

External modules are installed at `modules/{registry}/{vendor}/{name}/` at the project root, with registries including `webkernelphp.com`, `github.com`, `gitlab.com`, `git.numerimondes.com`, and others.

---

## Licensing & Collaboration

### Dual-Licensing Model

Webkernel™ is available under two distinct paths. Without a commercial agreement, the software is governed by the **Eclipse Public License 2.0 (EPL-2.0)**, which allows for open-source use and modification provided that modified files remain under the same license. For enterprises, governments, and institutions requiring official support, updates, and certifications, a **Webkernel™ Commercial License** is issued by Numerimondes, with a Sovereign Tier available for critical infrastructure and air-gapped deployments.

For the full license terms, see the [Webkernel Unified License v1.2](https://webkernelphp.com/license).

### Join the Ecosystem

Contributions are welcome and encouraged. To maintain the integrity of the foundation, all contributions are accepted via official pull requests and undergo a validation process by Numerimondes. Whether you are looking to integrate new features or build custom modules, we invite you to reach out and help expand the ecosystem.

**Official Documentation:** [webkernelphp.com](https://webkernelphp.com)

---

*Numerimondes — Registered Company, Casablanca, Morocco*
*[www.numerimondes.com](https://www.numerimondes.com) · [+212 6 2099 0692](tel:00212620990692)*
