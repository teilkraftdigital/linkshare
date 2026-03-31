# Ubiquitous Language

## Core entities

| Term | Definition | Aliases to avoid |
| --- | --- | --- |
| **Link** | A saved web address with title, description, and optional notes, belonging to exactly one Bucket | Bookmark, URL, item |
| **Tag** | A label applied to one or more Links that can optionally be made public, generating a shareable URL | Category, label, folder |
| **Bucket** | A container that groups Links for organisational purposes; every Link belongs to exactly one Bucket | Collection, folder, list |
| **Inbox** | The one special Bucket (`is_inbox: true`) that acts as the default destination for new Links | Default bucket, uncategorised |
| **Slug** | The URL-safe identifier for a Tag, auto-generated from its name and guaranteed to be unique across active and soft-deleted Tags | Handle, URL key, path segment |
| **Notes** | Free-form, private text attached to a Link; excluded from exports by default | Private notes, comments, memo |
| **Favicon** | The small icon fetched from a Link's domain and stored as media, used for visual identification | Icon, logo, site image |

## Visibility & lifecycle

| Term | Definition | Aliases to avoid |
| --- | --- | --- |
| **Public Tag** | A Tag with `is_public: true` whose Links are visible to unauthenticated visitors at `/tags/:slug` | Shared tag, open tag |
| **Private Tag** | A Tag with `is_public: false`; returns 404 to unauthenticated visitors | Hidden tag |
| **Soft-deleted** | A Link, Tag, or Bucket that has been removed from normal views but is retained in the database and still counts for slug collision checks | Archived, trashed, deleted |

## Export & import

| Term | Definition | Aliases to avoid |
| --- | --- | --- |
| **Netscape Export** | A downloadable `.html` file in the Netscape Bookmark File Format that browsers can import, organised with the Tag name as a folder | Bookmark export, HTML export |
| **JSON Export** | A full-data snapshot of Links, Buckets, and Tags in a versioned `.json` file | Data export, backup |
| **JSON Import** | A two-step process: upload a JSON export file, preview and confirm Bucket/Tag selection, then execute the import | Data import, restore |
| **HTML Import** | Import of a Netscape bookmark `.html` file produced by any browser or another Linkshare instance | Netscape import, browser import |
| **Merge-by-name** | The conflict resolution strategy during import: if a Bucket or Tag with the same name (case-insensitive) already exists, the existing record is reused and the import count is skipped | Upsert, overwrite |
| **Duplicate Link** | A Link whose normalised URL already exists in the database; it is silently skipped during import | Existing link, conflicting link |

## People & roles

| Term | Definition | Aliases to avoid |
| --- | --- | --- |
| **Admin** | The single authenticated user who owns and manages all Links, Buckets, and Tags | User, owner, operator |
| **Visitor** | An unauthenticated person viewing a Public Tag page | Guest, anonymous user, reader |

## Relationships

- A **Link** belongs to exactly one **Bucket** and can have zero or more **Tags**.
- A **Tag** can group zero or more **Links**.
- There is exactly one **Inbox** Bucket per installation; it cannot be soft-deleted.
- A **Public Tag** exposes its **Links** to any **Visitor** at `/tags/:slug`.
- A **Slug** is derived from the **Tag**'s name and remains stable after creation unless explicitly changed.
- **Notes** on a **Link** are never included in a **Netscape Export** and are opt-in in a **JSON Export**.

## Example dialogue

> **Dev:** "If a Visitor opens `/tags/php`, what do they see?"
>
> **Domain expert:** "They see a Public Tag page listing all Links tagged with 'php', sorted alphabetically by title. If the Tag is private, they get a 404."
>
> **Dev:** "Can they download those Links?"
>
> **Domain expert:** "Yes — there's an export button that generates a Netscape Export. The Tag name becomes a folder in the bookmark file, so the Links land in an organised folder when the Visitor imports them into their browser."
>
> **Dev:** "What happens when the Admin imports a JSON Export that contains a Bucket named 'PHP' and one already exists?"
>
> **Domain expert:** "Merge-by-name applies: the existing Bucket is reused. The import log shows 'PHP bereits vorhanden' and the Links go into the existing Bucket. No duplicate Bucket is created."
>
> **Dev:** "And if a Link's URL is already in the database?"
>
> **Domain expert:** "That's a Duplicate Link — it gets skipped silently. Same logic as the HTML Import."

## Flagged ambiguities

- **"Export"** appears in two distinct contexts: the **Netscape Export** on the public Tag page (for Visitors) and the **JSON Export** on the dashboard (for the Admin). Always qualify which export format is meant.
- **"Bookmark"** is used informally but is not a domain term. The entity is always a **Link**; the file format is **Netscape Export**.
- **"Folder"** is used in the context of the Netscape bookmark file structure (where a Tag becomes a folder), but it is not a Linkshare domain concept. Prefer **Bucket** for containers and **Tag** for labels.
- **"Notes"** vs **"Description"**: both are text fields on a **Link**. **Description** is public-facing and included in exports by default; **Notes** are private and excluded from exports unless the Admin explicitly opts in.
