# Plan: Export & Import (JSON)

> Source PRD: grill-me session, 2026-03-30

## Architectural decisions

- **Routes**:
  - `POST /dashboard/export` — triggers JSON file download
  - `POST /dashboard/import/json/parse` — step 1: upload & parse JSON, returns preview
  - `POST /dashboard/import/json` — step 2: execute JSON import with selections
  - `GET|POST /dashboard/import` (existing) — Netscape HTML import, unverändert

- **JSON-Format**:
  ```json
  {
    "version": 1,
    "exported_at": "2026-03-30T12:00:00Z",
    "includes_notes": false,
    "buckets": [
      { "name": "Work", "color": "blue", "is_inbox": false }
    ],
    "tags": [
      { "name": "design", "slug": "design", "color": "red", "description": null, "is_public": false }
    ],
    "links": [
      {
        "url": "https://example.com",
        "title": "Example",
        "description": "...",
        "notes": null,
        "bucket": "Work",
        "tags": ["design"]
      }
    ]
  }
  ```
  - Buckets und Tags werden per **Name** referenziert (nicht per ID) → portabel
  - `notes` ist `null` wenn `includes_notes: false`

- **Dateiname**: `linkshare-export-{YYYY-MM-DD}.json`

- **Conflict resolution beim Import**:
  - Buckets & Tags: merge-by-name (case-insensitive lowercase), skip wenn vorhanden
  - Links: normalisierte URL = Duplikat → überspringen (wie Netscape-Import)
  - Abgewählte Buckets: deren Links werden nicht importiert
  - Abgewählte Tags: werden von Links gestripped, Tag-Entität nicht angelegt

- **Soft-deletes**: werden beim Export ignoriert; UI-Hinweis darauf

- **Services**:
  - `ExportService` — baut JSON-Struktur aus DB
  - `JsonImportService` — parst JSON, führt Import durch

---

## Phase 1: Export — Vollständiger Dump

**User stories**: Als User möchte ich alle meine Links, Buckets und Tags als JSON-Datei herunterladen können.

### What to build

Eine Export-Sektion oben auf der Import-Seite (`/dashboard/import`). Ein „Exportieren"-Button löst einen POST-Request aus, der eine vollständige JSON-Datei aller aktiven (nicht soft-deleted) Links, Buckets und Tags zurückgibt und als Datei-Download ausliefert. Das JSON-Format wird in diesem Schritt etabliert. Noch keine Auswahl, noch keine Notes-Option — alles wird exportiert, Notes werden nicht mitgenommen.

### Acceptance criteria

- [ ] Export-Sektion ist oben auf der Import-Seite sichtbar
- [ ] Download-Button löst einen POST aus und gibt eine `linkshare-export-{YYYY-MM-DD}.json` zurück
- [ ] JSON enthält `version`, `exported_at`, `includes_notes: false`, `buckets`, `tags`, `links`
- [ ] Soft-deleted Einträge sind nicht enthalten
- [ ] Links referenzieren Buckets und Tags per Name
- [ ] `notes` ist in allen exportierten Links `null`
- [ ] Leere App (keine Links) exportiert eine valide JSON-Datei mit leeren Arrays
- [ ] Tests für `ExportService` (unit) und den Export-Route (feature)

---

## Phase 2: Export — Selektiv

**User stories**: Als User möchte ich beim Export auswählen welche Buckets und Tags enthalten sein sollen, und ob private Notizen mitexportiert werden.

### What to build

Die Export-Sektion erhält eine Checkbox-Liste aller aktiven Buckets und Tags (alle standardmäßig angehakt) sowie eine Checkbox „Private Notizen einschließen" (default: aus). Der Hinweis auf nicht enthaltene soft-deleted Items wird angezeigt. Abgewählte Buckets führen dazu, dass deren Links nicht exportiert werden. Abgewählte Tags werden von Links gestripped und erscheinen nicht als Entität im Export. Ist „Private Notizen einschließen" aktiv, werden `notes`-Felder befüllt und `includes_notes: true` gesetzt.

### Acceptance criteria

- [ ] Checkbox-Liste aller aktiven Buckets (alle angehakt)
- [ ] Checkbox-Liste aller aktiven Tags (alle angehakt)
- [ ] Checkbox „Private Notizen einschließen" (default: aus)
- [ ] Hinweis: „Gelöschte Einträge (Papierkorb) sind nicht enthalten"
- [ ] Nur Links aus gewählten Buckets im Export
- [ ] Abgewählte Tags sind weder als Entität noch als Link-Assoziation enthalten
- [ ] `includes_notes: true` + befüllte `notes`-Felder wenn Checkbox aktiv
- [ ] Tests für selektiven Export (verschiedene Kombinationen)

---

## Phase 3: JSON-Import — Upload & Vorschau

**User stories**: Als User möchte ich eine Linkshare-JSON-Datei hochladen und sehen, was importiert werden würde, bevor der Import ausgeführt wird.

### What to build

Eine neue „JSON importieren"-Sektion auf der Import-Seite (unterhalb der Netscape-Sektion). Schritt 1: User lädt eine `.json`-Datei hoch. Der Server parst die Datei und gibt eine Vorschau zurück: gefundene Buckets und Tags als Checkboxen (alle angehakt), Anzahl gefundener Links. Der Two-Step-State wird im Frontend gehalten — nach dem Parse-Response zeigt die Seite die Auswahlmaske statt des Upload-Formulars. Noch kein eigentlicher Import in dieser Phase.

### Acceptance criteria

- [ ] Upload-Feld für `.json`-Dateien in neuer Sektion
- [ ] `POST /dashboard/import/json/parse` parst die Datei und gibt `{ buckets, tags, link_count }` zurück
- [ ] Nach erfolgreichem Parse: Auswahlmaske mit Checkboxen für Buckets und Tags (alle angehakt)
- [ ] Anzeige: „X Links gefunden"
- [ ] Validierung: ungültige JSON-Struktur oder falsche `version` → Fehlermeldung
- [ ] „Zurück"-Option bringt den User zurück zum Upload-Formular
- [ ] Tests für Parse-Route (valides JSON, invalides JSON, falsche Version)

---

## Phase 4: JSON-Import — Ausführen

**User stories**: Als User möchte ich die ausgewählten Buckets und Tags aus einer JSON-Datei importieren und eine Zusammenfassung sehen was importiert, übersprungen oder zusammengeführt wurde.

### What to build

Schritt 2: User bestätigt die Auswahl, `POST /dashboard/import/json` führt den Import durch. `JsonImportService` legt fehlende Buckets und Tags per merge-by-name (case-insensitive) an, überspringt vorhandene. Links aus abgewählten Buckets werden ignoriert, abgewählte Tags von Links gestripped. Link-Duplikate (normalisierte URL) werden übersprungen. Das Ergebnis zeigt: importierte Links, übersprungene Links, zusammengeführte/übersprungene Buckets (mit Namen), zusammengeführte/übersprungene Tags (mit Namen). Für neue Links wird `FetchLinkMeta` dispatched (wie beim Netscape-Import).

### Acceptance criteria

- [ ] `POST /dashboard/import/json` nimmt Datei + Bucket-/Tag-Auswahl entgegen
- [ ] Buckets: merge-by-name (lowercase), fehlende werden angelegt, vorhandene bleiben unverändert
- [ ] Tags: merge-by-name (lowercase), fehlende werden angelegt, vorhandene bleiben unverändert
- [ ] Links aus abgewählten Buckets werden nicht importiert
- [ ] Abgewählte Tags werden von importierten Links gestripped
- [ ] Link-Duplikate (normalisierte URL inkl. `withTrashed`) werden übersprungen
- [ ] `FetchLinkMeta` wird für jeden neu importierten Link dispatched
- [ ] Ergebnis-Zusammenfassung: importierte Links, übersprungene Links, übersprungene Buckets (Namen), übersprungene Tags (Namen)
- [ ] Tests für `JsonImportService` (unit) und Import-Route (feature, verschiedene Konflikt-Szenarien)
