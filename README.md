# NXT AI Label

[Deutsch](#deutsch) · [English](#english)

## Deutsch

Brennt offizielle EU-KI-Kennzeichen in ausgewählte Mediathek-Bilder, Thumbnails und EWWW-WebP-Dateien.

WordPress 6.1+, PHP 8.0, GD mit JPEG, PNG und WebP. Plugin-Code: GPLv2 oder später.

### Wozu

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

### Installation

1. Den Ordner `nxt-ai-label` nach `wp-content/plugins/` legen.
2. Im Backend unter Plugins aktivieren.
3. Ein Bild in der Mediathek öffnen, Motiv, Position und Größe wählen, **Kennzeichnung setzen**.

### Bedienung

#### Einzelnes Bild

In der Mediathek, in der Seitenleiste und auf der Anhang-Seite: Motiv, Position, Größe. Die Vorschau liegt auf dem Bild. **Kennzeichnung setzen** schreibt alle Varianten. Dabei liegt ein Overlay über dem Medien-Dialog, nicht ein Ladekreis am Seitenanfang. **Kennzeichnung entfernen** stellt die Kopien wieder her.

#### Mehrere Bilder

In der Listenansicht markieren, Aktion „KI-Kennzeichnung setzen“. Die folgende Maske gilt nur für diese Auswahl. „KI-Kennzeichnung neu erzeugen“ schreibt bereits gesetzte Kennzeichnungen neu und fasst unmarkierte Bilder nicht an.

#### Werkzeuge

Unter **Werkzeuge → KI-Kennzeichnung**:

- Automatik für neue Uploads, standardmäßig aus. Sie greift nur, wenn die Datei selbst `trainedAlgorithmicMedia` oder `compositeWithTrainedAlgorithmicMedia` trägt (IPTC Digital Source Type, oft über Content Credentials). Ein entfernter Haken bleibt entfernt.
- Prüfung der bestehenden Mediathek. Bilder mit schon gesetzter Entscheidung bleiben unangetastet.
- Neu erzeugen aller bereits gesetzten Kennzeichnungen.

#### Cover-Blöcke

`object-fit: cover` schneidet Ränder ab. Die eingebrannte Ecke liegt dann außerhalb des sichtbaren Ausschnitts. Hat das Hintergrundbild eines `core/cover` eine Kennzeichnung, setzt das Plugin dieselbe Markierung zusätzlich in den sichtbaren Bereich des Covers, gleiche Ecke, gleiche Höhe. Ein Script blendet diese zweite Markierung aus, wenn die eingebrannte noch vollständig im Ausschnitt liegt, Fokuspunkt inklusive. Video-Cover bleiben unverändert. Den Cover-Block musst du dafür nicht anfassen.

### Entwickler

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

#### WP-CLI

```bash
wp nxt-ai-label regenerate
wp nxt-ai-label regenerate --id=12,34
wp nxt-ai-label detect
```

`detect` prüft nur Anhänge ohne gesetzte Entscheidung.

### Grenzen

- Cache und CDN behalten die alten Bytes, bis sie geleert werden. Das Plugin leert keinen Cache.
- Jeder Stempel ist eine neue JPEG- oder WebP-Datei aus der unmarkierten Kopie.
- Die Automatik erkennt keine KI-Bilder an den Pixeln. Ohne IPTC-Herkunft oder ohne Aufruf von `nxt_ai_label_mark()` passiert nichts.
- GD mit WebP wird vorausgesetzt. Imagick wird nicht gebraucht.

Die Kennzeichen-PNGs sind die offiziellen Vorlagen der Europäischen Kommission und werden unverändert ausgeliefert.

### Fragen

**Markiert das Plugin alle Uploads?**

Nein. Ohne gesetzte Kennzeichnung am einzelnen Bild, ohne Bulk-Aktion auf einer Auswahl und ohne eingeschaltete Automatik bleibt die Datei wie sie ist.

**Warum liegt die Markierung im Cover doppelt?**

Nur solange das Script die zweite Markierung noch nicht ausgeblendet hat, oder wenn der Ausschnitt die eingebrannte Ecke noch vollständig zeigt. Im zweiten Fall ist die Cover-Markierung absichtlich aus.

**Was passiert mit der EWWW-WebP?**

Liegt `bild.jpg.webp` neben `bild.jpg`, wird sie mit gekennzeichnet. Die Qualität kommt aus der EWWW-Option `webp_quality`, sonst 75. Fehlt die WebP-Datei, legt das Plugin keine an.

**Kann ich die Kennzeichnung später ändern?**

Ja. Andere Vorlage, andere Ecke oder andere Größe wählen und erneut setzen. Oder unter Werkzeuge „Gesetzte Kennzeichnungen neu erzeugen“. Die Quelle ist immer die unmarkierte Kopie.

### Sprachen

Quellsprache ist Englisch. Mitgeliefert sind Übersetzungen für Deutsch (`de_DE`), Polnisch (`pl_PL`), Italienisch (`it_IT`), Spanisch (`es_ES`) und Französisch (`fr_FR`) in `languages/`. Die Oberfläche folgt der Sprache des WordPress-Benutzers. Die Schriftzüge auf den EU-Zeichen bleiben Englisch, weil das die offiziellen Vorlagen sind.

### Changelog

#### 1.0.0

- Kennzeichnung pro Bild, als Auswahl in der Mediathek und per WP-CLI.
- Thumbnails, skalierte Originale und vorhandene `.webp`-Geschwister.
- Unmarkierte Kopien, Wiederherstellen, Aufräumen bei Deinstallation.
- Optionale Erkennung von IPTC Digital Source Type.
- Zweite Markierung auf Cover-Blöcken, wenn `object-fit: cover` die eingebrannte Ecke abschneidet.

## English

Burns official EU AI labels into selected media images, thumbnails and EWWW WebP files.

WordPress 6.1+, PHP 8.0, GD with JPEG, PNG and WebP. Plugin code: GPLv2 or later.

### Purpose

The AI Act requires a label on images that were generated or modified with AI. A CSS note on the page is not enough. Thumbnails, downloads and the WebP redirect serve the file itself.

This plugin writes the label into the pixels. Only for images you select. The rest of the library stays unchanged.

It ships the 12 official PNG templates from the European Commission:

- AI, AI Generated, AI Modified
- black or white
- solid or semi-transparent

Per image you choose the mark, the corner and the height. Heights are fixed: small 30 px, medium 40 px, large 50 px. Width follows the template. If the mark would leave the image, it is scaled down only until it sits inside, with a margin.

The plugin stamps:

- the media file, and on large uploads the unscaled original as well
- every intermediate size
- an existing sibling `filename.ext.webp` in the same directory (that is how EWWW Image Optimizer stores the WebP variant served through `.htaccess`)

JPEG, PNG and WebP. PDF, SVG and GIF are skipped. Files narrower than 32 px are skipped too.

Before the first stamp the plugin copies the files to `uploads/nxt-ai-label-backup/{attachment-id}/`. That directory is denied by `.htaccess`. A later change of mark, position or size always starts from this copy, so labels do not stack. Uninstall restores the copies, then deletes the backup and the meta.

### Installation

1. Place the `nxt-ai-label` folder in `wp-content/plugins/`.
2. Activate it under Plugins.
3. Open an image in the library, choose mark, position and size, then **Apply label**.

### Usage

#### Single image

In the library sidebar and on the attachment screen: mark, position, size. The preview sits on the image. **Apply label** writes every variant. An overlay covers the media dialog while that runs, instead of a spinner at the top of the page. **Remove label** restores the copies.

#### Several images

Select them in the list view and choose **Apply AI label**. The screen that follows applies only to that selection. **Regenerate AI label** rewrites labels that are already applied and leaves unmarked images alone.

#### Tools

Under **Tools → AI label**:

- Automatic labeling for new uploads, off by default. It runs only when the file itself contains `trainedAlgorithmicMedia` or `compositeWithTrainedAlgorithmicMedia` (IPTC Digital Source Type, often via Content Credentials). A label you remove stays removed.
- A scan of the existing library. Images that already have a decision stay untouched.
- Regeneration of every label that is already applied.

#### Cover blocks

`object-fit: cover` crops the edges. The burned-in corner can fall outside the visible area. When a `core/cover` background image has a label, the plugin adds the same mark inside the visible cover, same corner, same height. A script hides that second mark when the burned-in one is still fully inside the crop, focal point included. Video covers are left alone. You do not edit the cover block for this.

### Developers

After creating an attachment you already know is AI-generated:

```php
nxt_ai_label_mark( $attachment_id );
```

Optional `slug`, `position` (`top-left`, `top-right`, `bottom-left`, `bottom-right`) and `scale` (`small`, `medium`, `large`).

While automatic labeling is on, other code can flag an upload as AI-generated:

```php
add_filter( 'nxt_ai_label_attachment_is_generated', function ( $declared, $attachment_id, $metadata ) {
	return $declared;
}, 10, 3 );
```

`$declared` is already true when the file contains either IPTC code.

#### WP-CLI

```bash
wp nxt-ai-label regenerate
wp nxt-ai-label regenerate --id=12,34
wp nxt-ai-label detect
```

`detect` checks only attachments that have no decision yet.

### Limits

- Cache and CDN keep the old bytes until they are purged. The plugin does not flush any cache.
- Every stamp is a new JPEG or WebP written from the unmarked copy.
- Automatic labeling does not detect AI images from pixels. Without an IPTC origin, or a call to `nxt_ai_label_mark()`, nothing happens.
- GD with WebP is required. Imagick is not used.

The label PNGs are the official European Commission templates and are shipped unchanged.

### Questions

**Does the plugin label every upload?**

No. Without a label on that image, without a bulk action on a selection, and without automatic labeling turned on, the file stays as it is.

**Why does the cover show the mark twice?**

Only until the script has hidden the second mark, or when the crop still shows the burned-in corner in full. In the second case the cover mark is hidden on purpose.

**What happens to the EWWW WebP?**

If `image.jpg.webp` sits next to `image.jpg`, it is labeled too. Quality comes from the EWWW option `webp_quality`, otherwise 75. If the WebP file is missing, the plugin does not create one.

**Can the label be changed later?**

Yes. Pick another template, corner or size and apply again. Or use **Regenerate applied labels** under Tools. The source is always the unmarked copy.

### Languages

Source language is English. Translations for German (`de_DE`), Polish (`pl_PL`), Italian (`it_IT`), Spanish (`es_ES`) and French (`fr_FR`) ship in `languages/`. The admin UI follows the WordPress user’s language. The words on the EU marks stay English, because those are the official templates.

### Changelog

#### 1.0.0

- Label per image, for a library selection, and through WP-CLI.
- Thumbnails, scaled originals and existing `.webp` siblings.
- Unmarked copies, restore, and cleanup on uninstall.
- Optional detection of IPTC Digital Source Type.
- A second mark on cover blocks when `object-fit: cover` crops the burned-in corner.
