# Plan: Import & Quick-Add Bookmarklet

> Source: grill-me session 2026-03-29

## Architectural decisions

- **Routes**
  - `GET /dashboard/import` — Import-Seite
  - `POST /dashboard/import` — Datei-Upload & Verarbeitung
  - `GET /dashboard/quick-add` — Popup-Seite (minimales Layout, kein Sidebar)
  - `POST /dashboard/links` — bereits vorhanden, wird von Quick-Add wiederverwendet
  - `POST /dashboard/links/fetch-meta` — bereits vorhanden, wird von Quick-Add genutzt

- **Schema**: Keine neuen Tabellen. Import und Quick-Add schreiben in die bestehende `links`-Tabelle.

- **Key models**: `Link` (bestehend), `Bucket` (bestehend)

- **URL-Normalisierung** (ab Phase 2, wiederverwendet in Phase 4):
  - Trailing Slash wird entfernt: `example.com/` → `example.com`
  - Query-Parameter bleiben erhalten, lösen aber nur einen Hinweis aus, keinen harten Block
  - Normalisierung lebt in einer eigenständigen Methode/Service damit Phase 4 sie wiederverwenden kann

- **Layouts**: Quick-Add bekommt ein eigenes minimales Layout ohne Sidebar (analog zu `GuestLayout`, aber hinter Auth)

- **Bookmarklet-URL**: Wird server-seitig mit der konfigurierten `APP_URL` generiert und als Prop an die Import-Seite übergeben

---

## Phase 1: Import — Upload & Erstellen

### Was zu bauen ist

Ein Admin kann eine Browser-Bookmark-Datei (Netscape HTML Format, exportiert aus Chrome/Firefox/Safari) hochladen. Der Server parst die Datei, erstellt Links im Inbox-Bucket und zeigt eine Zusammenfassung an (X Links importiert). Noch kein Duplikat-Check, kein Bucket-Dropdown.

### Acceptance Criteria

- [ ] `GET /dashboard/import` zeigt eine Seite mit File-Upload-Feld
- [ ] Seite ist in der Sidebar verlinkt
- [ ] `POST /dashboard/import` nimmt eine `.html`-Datei entgegen
- [ ] `BookmarkImportService` parst `<a>`-Tags aus dem Netscape-Format (href = URL, Textinhalt = Titel)
- [ ] Alle geparsten Links werden im Inbox-Bucket angelegt
- [ ] Nach dem Import wird die Anzahl der erstellten Links als Zusammenfassung angezeigt
- [ ] Test: `BookmarkImportService` — parst URLs und Titel korrekt
- [ ] Test: `BookmarkImportService` — leere Datei → 0 Links
- [ ] Test: Endpoint erstellt Links und gibt Zusammenfassung zurück

---

## Phase 2: Import — Duplikat-Erkennung & Bucket-Auswahl

**Depends on**: Phase 1

### Was zu bauen ist

Der Import wird um Duplikat-Erkennung und Bucket-Auswahl erweitert. URLs werden normalisiert (Trailing Slash). Links die bereits in der DB vorhanden sind oder innerhalb der Importdatei doppelt vorkommen werden übersprungen. Links mit identischer Basis-URL aber unterschiedlichen Query-Parametern lösen einen Hinweis aus, werden aber trotzdem importiert. Die Zusammenfassung zeigt: importiert / übersprungen / Hinweise. Ein Bucket-Dropdown erlaubt die Auswahl des Ziel-Buckets (Standard: Inbox).

Die URL-Normalisierungslogik wird als eigenständige, testbare Methode extrahiert — Phase 4 baut darauf auf.

### Acceptance Criteria

- [ ] Trailing Slash wird vor dem DB-Abgleich entfernt
- [ ] Link mit identischer normalisierter URL → wird übersprungen, zählt als "übersprungen"
- [ ] Doppelter Link innerhalb der Importdatei → wird nur einmal importiert
- [ ] Link mit gleicher Basis-URL aber anderem Query-String → wird importiert, zählt als "Hinweis"
- [ ] Zusammenfassung zeigt: X importiert, Y übersprungen, Z Hinweise
- [ ] Bucket-Dropdown auf der Import-Seite; Standard ist Inbox
- [ ] URL-Normalisierung ist als isolierte Methode testbar
- [ ] Test: Duplikat gegen DB → wird übersprungen
- [ ] Test: Duplikat innerhalb der Datei → wird nur einmal importiert
- [ ] Test: Gleiche Basis-URL, anderer Query-String → importiert + Hinweis
- [ ] Test: Bucket-Auswahl wird korrekt übernommen

---

## Phase 3: Quick-Add — Popup-Seite

### Was zu bauen ist

`/dashboard/quick-add?url=…&title=…` ist eine eigenständige Seite mit minimalem Layout (keine Sidebar). Sie öffnet sich als `window.open()`-Popup (~480×560px). URL und Titel werden aus den Query-Parametern vorausgefüllt. MetaFetch wird automatisch beim Laden der Seite ausgelöst (kein Debounce nötig, da URL feststeht). Das vollständige Formular (URL, Titel, Beschreibung, Notizen, Bucket, Tags) ist kompakt dargestellt. Nach erfolgreichem Speichern erscheint eine Bestätigung, das Fenster schließt sich nach 1,5 Sekunden automatisch. Ist der User nicht eingeloggt, wird er zum Login weitergeleitet und danach zurück zur Quick-Add-Seite.

### Acceptance Criteria

- [ ] `GET /dashboard/quick-add` rendert eine Seite ohne Sidebar-Layout
- [ ] URL und Titel aus Query-Parametern werden in die Formularfelder vorausgefüllt
- [ ] MetaFetch wird automatisch beim Laden ausgelöst, befüllt Beschreibung
- [ ] Vollständiges Formular (URL, Titel, Beschreibung, Notizen, Bucket, Tags)
- [ ] Nach Speichern: "Link saved!" Bestätigung sichtbar, Fenster schließt sich nach 1,5s via `window.close()`
- [ ] Nicht eingeloggt → Redirect zu Login → Redirect zurück zu `/dashboard/quick-add` mit ursprünglichen Query-Parametern
- [ ] Test: Seite rendert mit vorausgefüllter URL aus Query-Parameter
- [ ] Test: Nicht eingeloggt → Redirect zu Login

---

## Phase 4: Bookmarklet-Installation & Duplikat-Hinweis

**Depends on**: Phase 2 (URL-Normalisierung), Phase 3 (Quick-Add-Route)

### Was zu bauen ist

Die Import-Seite bekommt einen Abschnitt "Quick-Add Bookmarklet" mit dem generierten Bookmarklet-Code und einem Copy-Button. Der Code enthält die korrekte `APP_URL` und öffnet `/dashboard/quick-add` mit der aktuellen Tab-URL und dem Tab-Titel als Query-Parameter.

Zusätzlich: URL-Felder in `Links.vue` (Erstell-Formular) und `Quick-Add` prüfen in Echtzeit beim Tippen ob eine URL bereits vorhanden ist. Dabei wird dieselbe Normalisierungslogik aus Phase 2 verwendet (Endpoint: `POST /dashboard/links/check-duplicate`). Bei Treffer erscheint ein dezenter Hinweis unter dem URL-Feld — kein harter Block.

### Acceptance Criteria

- [ ] Import-Seite zeigt Bookmarklet-Code mit korrekter `APP_URL`
- [ ] Copy-Button kopiert den Code in die Zwischenablage
- [ ] `POST /dashboard/links/check-duplicate` prüft URL (normalisiert) gegen DB, gibt `{ exists: bool, similar: bool }` zurück
- [ ] URL-Feld in Links.vue zeigt Hinweis "Link already exists" wenn exakte URL vorhanden
- [ ] URL-Feld in Links.vue zeigt Hinweis "Similar link found" bei gleicher Basis-URL mit anderem Query-String
- [ ] URL-Feld in Quick-Add zeigt dieselben Hinweise
- [ ] Hinweis blockiert nicht das Speichern — nur informativer Charakter
- [ ] Test: `check-duplicate` Endpoint — exakte URL → exists: true
- [ ] Test: `check-duplicate` Endpoint — normalisierte URL (Trailing Slash) → exists: true
- [ ] Test: `check-duplicate` Endpoint — gleiche Basis-URL, anderer Query-String → similar: true
- [ ] Test: Endpoint hinter Auth-Middleware
