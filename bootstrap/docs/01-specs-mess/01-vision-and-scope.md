# 01 — Vision and Scope

**Author:** El Moumen Yassine — Numerimondes
**Platform:** WebKernel PHP

---

## What We Are Building

We are building a self-hosted, no-code application builder on top of the WebKernel PHP platform.
The goal is to allow any application owner — technical or not — to design, build, and deploy full
web applications without writing a single line of code.

This is not a static site builder. This is a full **application builder** capable of:

- Defining data models (Collections) through a visual interface
- Binding UI blocks to live data from internal collections or external APIs
- Configuring navigation, list-detail patterns, and on-click actions
- Creating forms that write to the database
- Displaying authenticated, private dashboards over collected data
- All of this across an unlimited number of sites and apps per installation

---

## What This Is Not

- Not a page builder that only handles static content
- Not a design tool like Framer that lacks real data handling
- Not a cloud-only SaaS — it is fully self-hosted
- Not dependent on a bespoke JavaScript framework

---

## Why Now, Why Us

Tools like Framer are excellent for visual design but fail at the moment a user needs real data,
real forms, or a private authenticated area. Webflow is closer but remains cloud-locked and
expensive. Budibase is data-first but has weak design capabilities and is not PHP-native.

We sit in a position no existing tool occupies: a data-first, PHP/Laravel-native, self-hosted
application builder with a clean visual editor. Our stack is the most widely deployed web stack
on the planet. Our hosting story is zero-overhead — it runs on whatever server already runs
the WebKernel installation.

---

## Objectives

- Allow users to create and manage database schemas (Collections) visually, with no SQL
- Allow users to bind any UI block to any Collection field or API field
- Allow users to define navigation, forms, and page logic without code
- Deliver fast, reactive interfaces via Livewire and Alpine.js
- Support an unlimited number of sites and apps per platform installation
- Build on top of the existing block layout engine already present in the platform
- Expose every subsystem through PHP interfaces and traits for extensibility

---

## Competitive Summary

| Capability | Framer | Webflow | Budibase | WebKernel Builder |
|---|---|---|---|---|
| Visual design quality | Excellent | Very Good | Basic | Good (Phase 1) |
| Data / Collections | Limited | Good | Good | Full |
| External API data | Very limited | Limited | Good | Full |
| Forms writing to DB | Not supported | Limited | Supported | Supported |
| Private dashboards | Not supported | Limited | Supported | Supported |
| Self-hosted | No | No | Yes | Yes |
| PHP / Laravel native | No | No | No | Yes |
| Multi-site / multi-app | No | No | Limited | Yes, unlimited |
| No JS required from user | Yes | Yes | Yes | Yes |
