# Linkshare

> **Dieses Projekt befindet sich noch in aktiver Entwicklung.**

Barrierefreies, selbst gehostetes Link-Archiv — eine Laravel + Vue 3 SPA für eine einzelne Admin-Person. Links werden mit Tags und Buckets organisiert; öffentliche Tags erzeugen teilbare URLs.

## Features

- **Link-Verwaltung** — Links speichern mit Titel, Beschreibung, Notizen, automatischem Favicon-Abruf und Duplikatserkennung
- **Buckets & Tags** — Links in Buckets sortieren und mit Tags versehen; Tags können öffentlich oder privat sein
- **Öffentliche Tag-Seiten** — Öffentliche Tags erzeugen teilbare URLs (`/tags/:slug`) ohne Login-Pflicht
- **Import / Export** — JSON-Backup sowie Netscape-Bookmark-Format (HTML) für den Browser-Import
- **Papierkorb** — Soft-Delete für Links, Tags und Buckets mit Wiederherstellung
- **Authentifizierung** — Fortify-basiertes Login mit Zwei-Faktor-Authentifizierung (TOTP)
- **Dark Mode** — Erscheinungsbild über Cookie gespeichert
- **Warteschlangen** — Hintergrundjobs für Favicon- und Metadaten-Abruf via Horizon

## Tech Stack

| Bereich       | Technologien                                         |
| ------------- | ---------------------------------------------------- |
| Backend       | PHP 8.4, Laravel 13, Inertia.js v3, Fortify, Horizon |
| Frontend      | Vue 3, TypeScript, Tailwind CSS v4, Reka UI          |
| Build         | Vite, Laravel Vite Plugin, Wayfinder                 |
| Tests         | Pest 4, PHPUnit 12                                   |
| Code-Qualität | Pint, ESLint, Prettier                               |

## Voraussetzungen

- PHP 8.3+
- Composer
- Node.js 18+
- SQLite (Standard) oder MySQL/PostgreSQL
- Redis (empfohlen für die Queue) — Favicon-Abruf und Metadaten-Extraktion laufen als Hintergrundjobs

## Installation

```bash
# 1. Repository klonen
git clone <repo-url> linkshare
cd linkshare

# 2. Ersteinrichtung (Abhängigkeiten, .env, Key, Migration, Frontend-Build)
composer run setup

# 3. Admin-Account anlegen
php artisan admin:create

# 4. Entwicklungsserver starten (PHP, Queue, Logs, Vite – parallel)
composer run dev
```

## Konfiguration

`.env.example` wird beim Setup automatisch nach `.env` kopiert. Wichtige Werte:

```env
APP_URL=http://linkshare.test

# Datenbank (Standard: SQLite)
DB_CONNECTION=sqlite

# Mail (Standard: log — für Password-Reset etc.)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=hello@example.com
```

## Befehle

```bash
# Entwicklung
composer run dev          # PHP-Server + Queue + Log-Tail + Vite (parallel)

# Queue (wird von composer run dev automatisch gestartet, alternativ manuell)
php artisan queue:listen --tries=1 --timeout=0

# Tests
php artisan test --compact

# PHP Code-Stil
vendor/bin/pint --dirty --format agent

# Frontend
npm run build         # Produktions-Build
npm run lint          # ESLint
npm run format        # Prettier
npm run types:check   # TypeScript-Prüfung

# Wayfinder (nach Änderungen an Routen/Controllern)
php artisan wayfinder:generate
```

## Architektur

```
app/
├── Http/Controllers/Dashboard/  # Link-, Tag-, Bucket-, Import-/Export-Controller
├── Http/Requests/Dashboard/     # Form Requests (Validierung)
├── Jobs/                        # FetchFaviconJob, FetchLinkMetaJob
├── Models/                      # User, Link, Tag, Bucket
└── Services/                    # SlugGenerator, MetaFetchService, ExportService, …

resources/js/
├── pages/                       # Inertia-Seiten (auth/, dashboard/, settings/)
├── components/                  # UI-Komponenten (Reka UI + eigene)
├── composables/                 # Vue Composables (useToast, useAppearance, …)
├── layouts/                     # AppLayout, AuthLayout, GuestLayout
└── lib/                         # colors.ts, utils.ts, datetime.ts
```

## Lizenz

[MIT License](./LICENSE.md)
