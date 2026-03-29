# Feature Ideas

Ideen für zukünftige Features. Offene Issues (#16, #17) sind hier nicht aufgeführt.

---

## Kern-Features

### Import
Bookmarks aus gängigen Formaten importieren, damit die App zur primären Ablage werden kann.
- Browser-Bookmarks (`bookmarks.html` — Netscape-Format, von Chrome/Firefox exportiert)
- Pocket JSON Export
- Raindrop.io CSV/JSON Export
- Duplikate anhand der URL erkennen und überspringen

### Quick-Add Bookmarklet
Ein-Klick-Speichern aus dem Browser ohne das Dashboard zu öffnen.
- Bookmarklet-Code wird im Dashboard generiert und kann als Lesezeichen gespeichert werden
- Öffnet ein kleines Popup-Fenster mit vorausgefüllter URL + Titel (aus dem Tab)
- Bucket und Tags können direkt im Popup zugewiesen werden

### Link-Archivierung
Seiten-Snapshot speichern damit Links nicht "sterben".
- Integration mit Wayback Machine (save.php API) oder selbst-gehosteter Lösung
- Archiv-Link wird am gespeicherten Link hinterlegt
- Optional: automatisch beim Erstellen eines Links archivieren

### Link-Gesundheit
Regelmäßige Erreichbarkeitsprüfung aller gespeicherten Links.
- Scheduled Job prüft URLs periodisch (z.B. wöchentlich)
- Links mit 404/Gone werden im Dashboard markiert
- Zusammenfassung: "X Links sind nicht mehr erreichbar"

---

## Öffentliche Seite

### Öffentliche Bucket-Seite
Analog zu `/tags/:slug` eine öffentliche Bucket-Seite `/buckets/:slug`.
- Bucket bekommt ein `is_public`-Flag und einen `slug`
- Zeigt alle Links des Buckets ohne Login
- Nützlich für thematische Listen (z.B. "Reading List", "Workshop-Ressourcen")

### RSS-Feed für öffentliche Tags
Abonnenten bekommen neue Links automatisch.
- `GET /tags/:slug/feed` liefert Atom/RSS XML
- Jeder neue Link im Tag erscheint als Eintrag
- `<link>` auf die originale URL, Beschreibung als Content

### Globale öffentliche Übersicht
`/links` zeigt alle Links aus allen öffentlichen Tags — eine Art kuratierte Startseite.
- Alphabetisch oder nach Datum sortiert
- Optional: gruppiert nach Tag

---

## Dashboard / UX

### Dashboard-Startseite
Die aktuelle Dashboard-Seite ist ein Platzhalter — mit echten Zahlen ergibt sie mehr Sinn.
- Anzahl Links, Tags, Buckets
- Zuletzt hinzugefügte Links (letzte 5–10)
- Meist verwendete Tags
- Leere Buckets / Tags ohne Links (zum Aufräumen)

### Bulk-Aktionen
Mehrere Links gleichzeitig bearbeiten.
- Checkboxen in der Link-Liste
- Ausgewählte Links in anderen Bucket verschieben
- Tags hinzufügen/entfernen auf Auswahl
- Ausgewählte Links löschen

### Keyboard Shortcuts
Schnelle Navigation ohne Maus.
- `n` — Fokus auf URL-Feld im Erstell-Formular
- `/` — Fokus auf Suchfeld
- `Esc` — Edit-Modus schließen / Filter zurücksetzen

---

## Technisch / API

### Read-only JSON API für öffentliche Inhalte
Externe Integrationen ohne Scraping ermöglichen.
- `GET /api/tags/:slug/links` — Links eines öffentlichen Tags als JSON
- API-Key nicht nötig (öffentliche Daten)
- Ermöglicht z.B. Widget-Einbindung auf externen Seiten

### 2FA-UI
Fortify unterstützt 2FA bereits — prüfen ob ein vollständiges Setup-UI im Dashboard vorhanden ist (QR-Code, Recovery Codes).
