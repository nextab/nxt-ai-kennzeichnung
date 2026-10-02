# NXT AI Kennzeichnung

Brennt offizielle EU-KI-Kennzeichen in ausgewählte Mediathek-Bilder, Thumbnails und EWWW-WebP-Dateien.

WordPress 6.1+, PHP 8.0, GD mit JPEG, PNG und WebP. Plugin-Code: GPLv2 oder später.

## Wozu

Die KI-Verordnung verlangt eine Kennzeichnung von Bildern, die mit KI erzeugt oder verändert wurden. Ein CSS-Hinweis auf der Seite reicht nicht. Thumbnails, Download und der WebP-Redirect liefern die Datei selbst aus.

Dieses Plugin schreibt das Kennzeichen in die Pixel. Nur bei Bildern, die du auswählst. Der Rest der Mediathek bleibt unverändert.

Mitgeliefert sind die 12 offiziellen PNG-Vorlagen der Europäischen Kommission:

- AI, AI Generated, AI Modified
- schwarz oder weiß
- deckend oder halbtransparent

Pro Bild wählst du Motiv, Ecke und Höhe. Die Höhe ist fest: klein 30 px, mittel 40 px, groß 50 px. Die Breite folgt aus der Vorlage. Liegt die Markierung nicht mehr im Bild, wird sie nur so weit verkleinert, dass sie mit Abstand innen bleibt.

Beschrieben werden:

- die Datei aus der Mediathek, bei großen Uploads auch die unskalierte Originaldatei
- jede Zwischengröße
- eine vorhandene Geschwisterdatei `dateiname.ext.webp` im selben Ordner (so legt EWWW Image Optimizer die WebP-Variante ab, die per `.htaccess` ausgeliefert wird)

JPEG, PNG und WebP. PDF, SVG und GIF bleiben liegen. Dateien unter 32 px Breite ebenfalls.

Vor dem ersten Stempel legt das Plugin eine Kopie nach `uploads/nxt-ai-label-backup/{attachment-id}/`. Das Verzeichnis ist per `.htaccess` gesperrt. Wechsel von Motiv, Position oder Größe startet immer von dieser Kopie, die Zeichen stapeln sich nicht. Deinstallation schreibt die Kopien zurück und entfernt danach Backup und Meta.

## Installation

1. Den Ordner `nxt-ai-kennzeichnung` nach `wp-content/plugins/` legen.
2. Im Backend unter Plugins aktivieren.
3. Ein Bild in der Mediathek öffnen, Motiv, Position und Größe wählen, **Kennzeichnung setzen**.

## Bedienung

### Einzelnes Bild

In der Mediathek, in der Seitenleiste und auf der Anhang-Seite: Motiv, Position, Größe. Die Vorschau liegt auf dem Bild. **Kennzeichnung setzen** schreibt alle Varianten. Dabei liegt ein Overlay über dem Medien-Dialog, nicht ein Ladekreis am Seitenanfang. **Kennzeichnung entfernen** stellt die Kopien wieder her.

### Mehrere Bilder

In der Listenansicht markieren, Aktion „KI-Kennzeichnung setzen“. Die folgende Maske gilt nur für diese Auswahl. „KI-Kennzeichnung neu erzeugen“ schreibt bereits gesetzte Kennzeichnungen neu und fasst unmarkierte Bilder nicht an.

### Werkzeuge

Unter **Werkzeuge → KI-Kennzeichnung**:

- Automatik für neue Uploads, standardmäßig aus. Sie greift nur, wenn die Datei selbst `trainedAlgorithmicMedia` oder `compositeWithTrainedAlgorithmicMedia` trägt (IPTC Digital Source Type, oft über Content Credentials). Ein entfernter Haken bleibt entfernt.
- Prüfung der bestehenden Mediathek. Bilder mit schon gesetzter Entscheidung bleiben unangetastet.
- Neu erzeugen aller bereits gesetzten Kennzeichnungen.

### Cover-Blöcke

`object-fit: cover` schneidet Ränder ab. Die eingebrannte Ecke liegt dann außerhalb des sichtbaren Ausschnitts. Hat das Hintergrundbild eines `core/cover` eine Kennzeichnung, setzt das Plugin dieselbe Markierung zusätzlich in den sichtbaren Bereich des Covers, gleiche Ecke, gleiche Höhe. Ein Script blendet diese zweite Markierung aus, wenn die eingebrannte noch vollständig im Ausschnitt liegt, Fokuspunkt inklusive. Video-Cover bleiben unverändert. Den Cover-Block musst du dafür nicht anfassen.

## Entwickler

Nach dem Anlegen eines bekannten KI-Bildes:

```php
nxt_ai_label_mark( $attachment_id );
```

Optional `slug`, `position` (`top-left`, `top-right`, `bottom-left`, `bottom-right`) und `scale` (`small`, `medium`, `large`).

Solange die Automatik an ist, kann fremder Code den Upload zusätzlich als KI-Bild melden:

```php
add_filter( 'nxt_ai_label_attachment_is_generated', function ( $declared, $attachment_id, $metadata ) {
	return $declared;
}, 10, 3 );
```

`$declared` ist bereits wahr, wenn die Datei einen der beiden IPTC-Codes enthält.

### WP-CLI

```bash
wp nxt-ai-label regenerate
wp nxt-ai-label regenerate --id=12,34
wp nxt-ai-label detect
```

`detect` prüft nur Anhänge ohne gesetzte Entscheidung.

## Grenzen

- Cache und CDN behalten die alten Bytes, bis sie geleert werden. Das Plugin leert keinen Cache.
- Jeder Stempel ist eine neue JPEG- oder WebP-Datei aus der unmarkierten Kopie.
- Die Automatik erkennt keine KI-Bilder an den Pixeln. Ohne IPTC-Herkunft oder ohne Aufruf von `nxt_ai_label_mark()` passiert nichts.
- GD mit WebP wird vorausgesetzt. Imagick wird nicht gebraucht.

Die Kennzeichen-PNGs sind die offiziellen Vorlagen der Europäischen Kommission und werden unverändert ausgeliefert.

## Fragen

**Markiert das Plugin alle Uploads?**

Nein. Ohne gesetzte Kennzeichnung am einzelnen Bild, ohne Bulk-Aktion auf einer Auswahl und ohne eingeschaltete Automatik bleibt die Datei wie sie ist.

**Warum liegt die Markierung im Cover doppelt?**

Nur solange das Script die zweite Markierung noch nicht ausgeblendet hat, oder wenn der Ausschnitt die eingebrannte Ecke noch vollständig zeigt. Im zweiten Fall ist die Cover-Markierung absichtlich aus.

**Was passiert mit der EWWW-WebP?**

Liegt `bild.jpg.webp` neben `bild.jpg`, wird sie mit gekennzeichnet. Die Qualität kommt aus der EWWW-Option `webp_quality`, sonst 75. Fehlt die WebP-Datei, legt das Plugin keine an.

**Kann ich die Kennzeichnung später ändern?**

Ja. Andere Vorlage, andere Ecke oder andere Größe wählen und erneut setzen. Oder unter Werkzeuge „Gesetzte Kennzeichnungen neu erzeugen“. Die Quelle ist immer die unmarkierte Kopie.

## Changelog

### 1.0.0

- Kennzeichnung pro Bild, als Auswahl in der Mediathek und per WP-CLI.
- Thumbnails, skalierte Originale und vorhandene `.webp`-Geschwister.
- Unmarkierte Kopien, Wiederherstellen, Aufräumen bei Deinstallation.
- Optionale Erkennung von IPTC Digital Source Type.
- Zweite Markierung auf Cover-Blöcken, wenn `object-fit: cover` die eingebrannte Ecke abschneidet.
