# Plan: Bulk-Aktionen

> Source PRD: Grill-Me Session — Bulk-Aktionen Feature (2026-04-14)

## Architektur-Entscheidungen

- **Routes**: `POST /dashboard/links/bulk-delete`, `/bulk-restore`, `/bulk-force-delete`, `/bulk-move-bucket`, `/bulk-add-tags`, `/bulk-remove-tags` — alle unter `dashboard`-Middleware
- **Schema**: Kein neues Schema nötig — `links`, `link_tag`, `buckets`, `tags` sind ausreichend
- **Key models**: `Link` (SoftDeletes), `Bucket`, `Tag` (BelongsToMany via `link_tag`)
- **Controller-Pattern**: Single-Action Controller in `app/Http/Controllers/Dashboard/BulkActions/`, je ein `__invoke()`, eigener Form Request pro Aktion
- **Request-Shape**: `link_ids[]` (Array von IDs, aktuelle Seite) — Architektur offen für späteres `select_all + filter`-Pattern
- **Auth**: Bestehende `auth`-Middleware, kein neues Policy-Layer nötig
- **State**: `useBulkSelection`-Composable verwaltet `bulkMode` (bool) + `selectedIds` (Set\<number\>)

---

## Phase 1: Bulk-Modus Foundation

**User stories**: Als Nutzer kann ich den Bulk-Modus aktivieren, mehrere Links auswählen und die Auswahl verwalten — ohne dass Aktionen verfügbar sind.

### What to build

Ein `useBulkSelection`-Composable kapselt den gesamten Selektions-State. `Links.vue` bekommt einen Toggle-Button neben den Filtern. Im aktiven Bulk-Modus erscheinen Checkboxen links neben jeder Link-Karte sowie eine "Alle auswählen"-Zeile zwischen Filtern und Link-Liste. Die fixierte Aktions-Bar am unteren Rand erscheint sobald ≥1 Link ausgewählt ist — Aktions-Buttons sind in dieser Phase noch nicht vorhanden (Bar zeigt nur Anzahl + Close). Beim Deaktivieren des Modus wird die Selektion geleert. Jede Checkbox hat ein zugängliches `aria-label`.

### Acceptance criteria

- [ ] Toggle-Button im Header aktiviert/deaktiviert den Bulk-Modus
- [ ] Im Bulk-Modus erscheint links neben jeder Karte eine Checkbox mit `aria-label="Link auswählen: {title}"`
- [ ] "Alle auswählen"-Zeile zwischen Filtern und Liste zeigt "X von Y ausgewählt"
- [ ] "Alle auswählen"-Checkbox selektiert alle Links der aktuellen Seite
- [ ] Fixierte Aktions-Bar erscheint unten wenn ≥1 Link ausgewählt ist
- [ ] Aktions-Bar zeigt Anzahl der ausgewählten Links
- [ ] Deaktivieren des Bulk-Modus leert die Selektion und blendet Bar + Checkboxen aus
- [ ] Selektion wird geleert wenn die Seite (Pagination) wechselt
- [ ] `useBulkSelection` ist als eigenständiges Composable implementiert

---

## Phase 2: Löschen & Wiederherstellen

**User stories**: Als Nutzer kann ich mehrere ausgewählte Links in den Papierkorb verschieben (Normal-View) oder aus dem Papierkorb wiederherstellen (Trash-View).

### What to build

Zwei Single-Action Controller (`DeleteBulkLinksController`, `RestoreBulkLinksController`) mit je einem Form Request der `link_ids[]` validiert. Entsprechende Routes unter `dashboard`-Middleware. Im Normal-View zeigt die Aktions-Bar einen "Löschen"-Button, im Trash-View einen "Wiederherstellen"-Button. Nach erfolgreicher Aktion erscheint ein Toast ("X Links gelöscht" / "X Links wiederhergestellt") und die Selektion wird geleert.

### Acceptance criteria

- [ ] `DELETE /dashboard/links/bulk-delete` löscht alle übergebenen Links (soft delete)
- [ ] `POST /dashboard/links/bulk-restore` stellt alle übergebenen Links wieder her (nur Trashed)
- [ ] Form Requests validieren `link_ids` als nicht-leeres Array existierender Link-IDs
- [ ] Normal-View Aktions-Bar zeigt "Löschen"-Button
- [ ] Trash-View Aktions-Bar zeigt "Wiederherstellen"-Button
- [ ] Toast nach Aktion: "X Links wurden gelöscht" / "X Links wurden wiederhergestellt"
- [ ] Selektion wird nach Aktion geleert, Bulk-Modus bleibt aktiv
- [ ] Feature-Tests für beide Controller (success + validation error)

---

## Phase 3: Endgültig löschen

**User stories**: Als Nutzer kann ich mehrere ausgewählte Links im Trash-View endgültig und unwiderruflich löschen.

### What to build

Ein `ForceDeleteBulkLinksController` mit Form Request. Im Trash-View ergänzt die Aktions-Bar einen "Endgültig löschen"-Button. Klick öffnet das bestehende `ConfirmModal` mit einer klaren Warnung ("X Links werden endgültig gelöscht und können nicht wiederhergestellt werden."). Nach Bestätigung erfolgt die Aktion, ein Toast bestätigt, die Selektion wird geleert.

### Acceptance criteria

- [ ] `DELETE /dashboard/links/bulk-force-delete` löscht alle übergebenen Links permanent (withTrashed)
- [ ] Klick auf "Endgültig löschen" öffnet ConfirmModal mit Anzahl der betroffenen Links
- [ ] Aktion wird nur nach Bestätigung ausgeführt
- [ ] Toast nach Aktion: "X Links wurden endgültig gelöscht"
- [ ] Selektion wird nach Aktion geleert
- [ ] Feature-Test für Controller

---

## Phase 4: Bucket verschieben

**User stories**: Als Nutzer kann ich mehrere ausgewählte Links gleichzeitig in einen anderen Bucket verschieben.

### What to build

Ein `MoveBulkBucketController` mit Form Request (`link_ids[]` + `bucket_id`). In der Normal-View Aktions-Bar erscheint ein "Bucket verschieben"-Button. Klick öffnet ein Modal mit Titel "Bucket verschieben" und einer Bucket-Auswahl (alle verfügbaren Buckets). Nach Bestätigung wird die Aktion ausgeführt, Toast bestätigt.

### Acceptance criteria

- [ ] `PATCH /dashboard/links/bulk-move-bucket` aktualisiert `bucket_id` aller übergebenen Links
- [ ] Form Request validiert `bucket_id` als existierenden Bucket
- [ ] Modal öffnet sich mit Bucket-Auswahl und Titel
- [ ] Toast nach Aktion: "X Links wurden nach {Bucket} verschoben"
- [ ] Selektion wird nach Aktion geleert
- [ ] Feature-Test für Controller

---

## Phase 5: Tags hinzufügen & entfernen

**User stories**: Als Nutzer kann ich Tags auf mehrere ausgewählte Links gleichzeitig hinzufügen oder entfernen.

### What to build

Zwei Controller (`AddBulkTagsController`, `RemoveBulkTagsController`) mit je einem Form Request. Normal-View Aktions-Bar bekommt zwei Buttons: "Tags hinzufügen" und "Tags entfernen".

**Hinzufügen-Modal**: Zeigt alle Tags als auswählbare Liste. Nach Bestätigung werden die gewählten Tags zu allen ausgewählten Links hinzugefügt (ohne bestehende zu überschreiben — `syncWithoutDetaching`).

**Entfernen-Modal**: Zeigt alle Tags die mindestens ein ausgewählter Link hat, als Pills mit Zähler (`[ × Accessibility | 10 ]`). Klick auf `×` entfernt den Tag sofort von allen ausgewählten Links die ihn haben.

### Acceptance criteria

- [ ] `POST /dashboard/links/bulk-add-tags` fügt `tag_ids[]` zu allen übergebenen Links hinzu (ohne bestehende Tags zu entfernen)
- [ ] `POST /dashboard/links/bulk-remove-tags` entfernt `tag_ids[]` von allen übergebenen Links
- [ ] Hinzufügen-Modal zeigt alle verfügbaren Tags als auswählbare Liste
- [ ] Entfernen-Modal zeigt Tags als Pills mit Zähler wie viele ausgewählte Links den Tag haben
- [ ] Toast nach Aktion: "Tags zu X Links hinzugefügt" / "Tags von X Links entfernt"
- [ ] Selektion wird nach Aktion geleert
- [ ] Feature-Tests für beide Controller
