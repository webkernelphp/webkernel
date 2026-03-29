<p align="center">  <a href="https://webkernelphp.com" target="_blank">
    <img src="https://raw.githubusercontent.com/numerimondes/.github/refs/heads/main/assets/brands/webkernel/identity/logo-webkernel-darkmode.png" width="380" alt="Webkernel">
  </a>
</p>

<p align="center">
    <a href="https://github.com/webkernelphp/webkernel/actions/workflows/php.yml"><img src="https://github.com/webkernelphp/webkernel/actions/workflows/php.yml/badge.svg?branch=main" alt="PHP CI/CD"></a>
    <a href="https://packagist.org/packages/webkernel/webkernel"><img src="https://img.shields.io/packagist/v/webkernel/webkernel" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/webkernel/webkernel"><img src="https://img.shields.io/packagist/dt/webkernel/webkernel" alt="Total Downloads"></a>
    <br>
    <a href="https://github.com/webkernelphp/webkernel?tab=readme-ov-file#license-1-ov-file"><img src="https://img.shields.io/badge/-Eclipse-2C2255?style=flat-square&logo=eclipse&logoColor=white" alt="License EPL-2.0"></a>
    <a href="https://webkernelphp.com"><img src="https://img.shields.io/badge/platform-webkernelphp.com-black" alt="Platform"></a>
</p>

---

## What Is Webkernel

Webkernel is a sovereign application platform built on Laravel. It is the foundation on which complete business software is deployed — ERP systems, recruitment platforms, banking tools, training management suites, industrial compliance engines — without external cloud dependencies, without forced vendor updates, and without exposure to the risk profile of public package ecosystems.

Organizations that deploy Webkernel own their software. Not a subscription. Not a seat license renewed annually under threat of service interruption. The software runs in their infrastructure, under their control, governed by their security policy.

Webkernel is designed and maintained by [Numerimondes](https://www.numerimondes.com), a registered software company based in Casablanca, Morocco, founded by [El Moumen Yassine](https://www.linkedin.com/in/elmoumenyassine/).

---

## Who This Is For

**Industrial operators** who need process management, compliance tracking, and operational dashboards that integrate with existing infrastructure and never phone home.

**Financial institutions** that operate under regulatory constraints requiring certified software, immutable audit trails, and verified dependency chains.

**Training and certification organizations** that need a complete, white-labeled platform — from applicant management to instructor scheduling to certificate delivery — without building from scratch or paying per-seat to a foreign SaaS vendor indefinitely.

**Government bodies and public institutions** that require full sovereignty over their digital infrastructure, including air-gapped deployment capability and local integrity verification with no outbound traffic to external servers.

**Enterprises modernizing legacy systems** who want a stable, long-lived foundation with a module ecosystem that extends functionality without disrupting the core.

---

## What You Get

A production-ready application foundation with a growing marketplace of first-party modules covering the business domains most in demand across the region.

Webkernel ships with:

- A modular bootstrap architecture where every component is explicit, declared, and integrity-verified
- A module orchestrator that discovers, validates, orders by dependency, and loads business modules at boot with zero manual registration
- A built-in CLI scaffolder to generate complete module structures across four architectural presets (classic, full-structure, DDD, hexagonal)
- A PSR-4 autoloading layer for modules that operates independently from Composer at runtime
- A fingerprint-based cache that regenerates automatically when the module tree changes
- Full Filament, Livewire, and Laravel compatibility out of the box

The platform is open-core. The kernel is free. Value is delivered through first-party modules, professional deployment services, and the sovereign tier for restricted environments.

---

## Module Ecosystem

Modules extend Webkernel instances with complete business functionality. They are installed once, owned permanently, and loaded declaratively via a manifest file that makes every capability explicit.

```bash
composer create-project webkernel/webkernel my-app
cd my-app
cp .env.example .env
php artisan key:generate
php artisan webkernel:require-module https://github.com/webkernelphp/module-slug
```

Each module declares its own routes, views, translations, migrations, commands, Livewire components, and Filament resources. The core loads everything. The module developer controls nothing outside their declared surface area.

Module IDs follow a stable, registry-scoped convention that makes dependency graphs unambiguous across registries and TLDs:

```
webkernelphp-com::vendor/slug
numerimondes-net::numerimondes/crm
```

---

## For Developers

If you are a developer building on or with Webkernel, the CLI gives you everything needed to scaffold a production-grade module in under two minutes:

```bash
php artisan webkernel:make-module
```

You are guided through registry, vendor, slug, namespace, structure preset, author information, and licensing. The result is a complete module skeleton with a `module.php` manifest, a service provider, route stubs, config, lang directories, and a `composer.json`. The module is immediately loadable.

Structure presets available at scaffold time:

| Preset | Description |
|---|---|
| Classic | Standard Laravel layout, one directory per concern |
| Full Structure | Every path explicit, suited for large modules |
| DDD | Domain, application, and infrastructure layers separated |
| Hexagonal | Ports and adapters, core domain isolated from delivery |

Modules are loaded exclusively from their `module.php` manifest. A module without a manifest is completely inert. Nothing is assumed, nothing is implicit.

The module marketplace at [webkernelphp.com](https://webkernelphp.com) is the distribution channel for first-party and third-party modules. Publishing your module there makes it available to the entire Webkernel ecosystem.

---

## Deployment Modes

**Standard** — connected deployment with remote integrity verification via Numerimondes servers. Suitable for most enterprise deployments with standard outbound connectivity.

**Air-Gapped** — all integrity checks performed locally within your infrastructure. No outbound connection to Numerimondes servers required at any point during normal operation. Designed for environments where external network communication is prohibited by regulation, security classification, or operational risk policy.

Air-gapped capability is available under the Sovereign Tier and is not a configuration toggle. It is a distinct deployment architecture backed by cryptographic key management tooling, offline update delivery channels, and direct engineering support from Numerimondes.

---

## Governance

The `bootstrap/` directory is maintained exclusively by Numerimondes. It contains the kernel initialization logic, the module orchestrator, and the integrity verification layer.

This directory is not modified by module developers, system integrators, or deploying organizations. It is the single technical guarantee that the entire module ecosystem remains trustworthy. Any modification voids certification, access to the module marketplace, and all official support.

Extensions, customizations, and application logic live entirely outside `bootstrap/`. The platform is designed so that nothing you need to build requires touching the core.

---

## Licensing

The Webkernel Core is distributed under the [Eclipse Public License 2.0](https://www.eclipse.org/legal/epl-2.0/).

First-Party Modules are licensed individually under the Webkernel Module License. Purchased modules are yours permanently. The version you acquire does not expire, does not require continued subscription, and cannot be revoked. Optional annual update access, where applicable, shall never exceed 25% of the original purchase price inclusive of all applicable taxes.

The complete license framework governing all tiers — free open-source, commercial modules, professional services, and sovereign deployment — is maintained by Numerimondes and available at [webkernelphp.com/license](https://webkernelphp.com/license).

---

## Security

Security vulnerabilities must not be reported via public issues.

Contact Numerimondes directly through [webkernelphp.com](https://webkernelphp.com) or via [LinkedIn](https://www.linkedin.com/in/elmoumenyassine/). Organizations with active support contracts receive advance notification ahead of public disclosure.

---

<p align="center">
  <a href="https://www.numerimondes.com" target="_blank">
    <img src="https://raw.githubusercontent.com/numerimondes/.github/refs/heads/main/assets/brands/numerimondes/identity/logos/v2/faviconV2_Numerimondes.png" width="64" alt="Numerimondes">
  </a>
</p>

<p align="center">
  Designed and maintained by <a href="https://www.numerimondes.com">Numerimondes</a> — Casablanca, Morocco<br>
  <a href="https://webkernelphp.com">webkernelphp.com</a> &nbsp;&middot;&nbsp;
  <a href="https://www.linkedin.com/in/elmoumenyassine/">El Moumen Yassine</a> &nbsp;&middot;&nbsp;
  +212 6 2099 0692
</p>
