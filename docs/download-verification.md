# Download-Link-Verifikation & Rate-Limiting

Überarbeitung des Download-Schutzes in `Files::getDownload()` (Branch `develop`, 2026-07).
Dieses Dokument beschreibt Motivation, Design, Implementierung, Konfiguration und Tests.

---

## 1. Ziele

Der Download-Schutz soll:

1. **Herkunft nachweisen** — nur herunterladen, wer vorher den Store oder die OCS-API benutzt hat.
2. **Zähler-Manipulation verhindern** — dieselbe Datei nicht beliebig oft laden, um die Download-Quote zu treiben.
3. **Bulk-Download erschweren** — nicht trivial alle Dateien am Stück abziehen.
4. **Link-Weitergabe begrenzen** — Links nicht (oder nur in engem Zeitfenster) teilbar.

### Rahmenbedingungen

- **Store, API und Fileserver sind getrennte Instanzen.**
- **Keine Cookies** nutzbar.
- Es gibt einen **gemeinsamen Redis** — soll aber **nicht** als vorbefülltes Token-Register dienen (Design bleibt *stateless*).
- **`user_id` kann leer sein** — anonyme Downloads (ohne Login) sind erlaubt.

---

## 2. Kernproblem der alten Lösung

Der alte Link trug **einen** Zeitstempel `t` (= Render-Zeit + 1 h), der **zwei Aufgaben gleichzeitig** erfüllte:

- Authentizitäts-/Frische-Nachweis („kam gerade vom Store")
- Ablaufzeit des Links

Folge: Die Uhr lief **ab dem Rendern der Seite**. Blieb eine Seite offen oder klickte der Nutzer spät, war der Link **abgelaufen, bevor er geklickt wurde**. Das System war zudem instabil.

---

## 3. Lösung: Zwei-Uhren-Modell

Die beiden Aufgaben werden auf **zwei getrennte Uhren** aufgeteilt:

| Uhr | Claim / Speicher | Start | Dauer (Default) | Zweck |
|-----|------------------|-------|-----------------|-------|
| **Recency-Uhr** | `exp` im Token (fällt zurück auf legacy `t`) | Render-Zeit | 48 h (`max_link_age`) | Beweist „kürzlich am Store gewesen", killt alte Bookmark-/Share-Links |
| **Klick-Uhr** | Redis `dl:act:{tokenId}` | **erster Klick** | 15 min (`activation_window`) | Kurzes Nutzungsfenster; behebt „Link abgelaufen vor Klick" |

Eine stundenlang offene Seite funktioniert also weiterhin (nur durch `exp` begrenzt), aber **sobald der Link zum ersten Mal benutzt wird**, lebt er nur noch kurz und ist praktisch Einmal-/Wenig-Gebrauch.

### Stateless

Der Fileserver hält **ausschließlich eigenen Anti-Replay-State** in Redis. Er verlässt sich **nicht** auf ein vom Store vorbefülltes Token-Register. Die Echtheit ergibt sich rein aus:

- **JWT-Signatur** mit gemeinsamem `jwt_secret` (nur Store/API können gültig signieren), plus
- **Download-Hash `s`** = `sha512(downloadSecret[client_id] + collection_id + t)`.

### Soft-IP-Binding

Der Store kann die bei ihm gesehene IP als `stip`-Claim mitgeben. Ein Mismatch wird **nur geloggt, nie abgelehnt** — Mobilfunk/NAT/Roaming würden hartes Binding sonst ständig fälschlich blocken.

### Anonyme Downloads

Rate-Limits hängen **nicht an `user_id`** (kann leer sein), sondern an:

- der **Token-Identität** (`jti`) — pro Link, und
- einem **groben per-IP-Safety-Net**.

---

## 4. Token-Aufbau

Der Download-Link trägt einen JWT (URL-Param `j`) mit folgenden Claims:

| Claim | Bedeutung | Pflicht |
|-------|-----------|---------|
| `id`  | file_id | ja |
| `u`   | user_id (leer / fehlend = anonym) | nein |
| `s`   | Download-Hash `sha512(downloadSecret + collection_id + t)` | ja |
| `t`   | validUntil (legacy Zeitstempel; Fallback für `exp`) | ja |
| `jti` | Nonce, eindeutige Token-ID; Schlüssel für den Server-State | **neu** |
| `iat` | issued-at | **neu** |
| `exp` | Hard-Expiry (Recency-Grenze) | **neu** |
| `stip`| IP, die der Store beim Rendern sah (Soft-Binding) | optional |

### Abwärtskompatibilität (tokenId-Fallback)

Solange der Store `jti`/`iat`/`exp` noch nicht mitschickt, greifen Fallbacks — der Fileserver ist **ohne Store-Änderung deploybar**:

```
tokenId = jti  ?:  sha1(jwt)  ?:  sha1(hashGiven + ':' + validUntil)
linkExp = exp  ?:  t (validUntil)
```

---

## 5. Verifikationsablauf in `getDownload()`

Reihenfolge der Prüfungen (Datei `api_application/controllers/Files.php`, ~Zeile 1125–1202):

| # | Prüfung | Fehlerfall | HTTP |
|---|---------|-----------|------|
| 0 | Download-Hash: `hashGiven != hash` | `link invalid` | **400** |
| 1 | Hard-Expiry: `now > linkExp` | `link expired` | **410** |
| 2 | Sanity-Cap: `(exp - iat) > max_link_age` (nur wenn beide vorhanden) | `link invalid` | **400** |
| 3 | Soft-IP: `stip != remoteIp` | nur Log-Eintrag | — |
| 4 | Per-IP-Safety-Net (`_ipRateLimited`) | `too many requests` + Rate-Limit-Header | **429** |
| 5 | Klick-Uhr (`_withinActivationWindow`) — Fenster verstrichen | `link expired` | **410** |
| 6 | Serve-Limit (`_registerServe`) — mehr als `max_serves_per_token` | `too many requests` + Rate-Limit-Header | **429** |

Danach: `isUniqueDownload = (serveCount === 1)` — **nur der erste Serve** eines Tokens zählt als eindeutiger Download in der öffentlichen Statistik. Weitere Serves (Resume/Retry/Mirror) werden bedient, zählen aber nicht.

### Rate-Limit-Header (429)

Bei 429 werden gesetzt: `Retry-After`, `X-RateLimit-Limit`, `X-RateLimit-Remaining: 0`, `X-RateLimit-Reset`.

---

## 6. Redis-State

Alle Keys über den `RedisCache`-Wrapper (Namespace-Prefix `{namespace}:_`, Werte PHP-`serialize()`d):

| Key | Inhalt | TTL | Zweck |
|-----|--------|-----|-------|
| `dl:act:{tokenId}` | first-seen Timestamp | `activation_window + 60` | Start der Klick-Uhr |
| `dl:cnt:{tokenId}` | Serve-Zähler | `activation_window + 60` | Per-Token-Serve-Limit + Unique-Erkennung |
| `dl:ip:{ip}:{bucket}` | Request-Zähler pro IP | `MIN_TIME + 5` | grober per-IP-Safety-Net |

`bucket = floor(time() / MIN_TIME)` — feste 1-Minuten-Fenster (kein Drift-Bug).

### Degradiert offen

Ist Redis nicht erreichbar, fällt die Verifikation **offen** aus (Download erlaubt) statt alle Downloads zu blockieren. Signatur- und Hash-Prüfung (0–2) greifen weiterhin, da sie ohne Redis auskommen.

---

## 7. Helper-Methoden

Neu in `Files.php` (ersetzen die alten `tooManyRequests`, `tooManyRequestsFromIP`, `uniqueDownload`):

| Methode | Aufgabe |
|---------|---------|
| `_dlConfig(key, default)` | liest `[download]`-Wert aus `application.ini`, sonst Konstante |
| `_withinActivationWindow(tokenId)` | setzt/prüft `dl:act`; `true` beim ersten Klick, danach nur innerhalb des Fensters |
| `_registerServe(tokenId)` | inkrementiert `dl:cnt`, gibt neuen Zählerstand zurück |
| `_ipRateLimited(ip)` | inkrementiert `dl:ip:{ip}:{bucket}`, `['blocked', 'retry_after']` |

---

## 8. Konfiguration

Neue `[download]`-Sektion in `configs/application.ini` (Defaults als Konstanten in `Files.php`, überschreibbar):

```ini
[download]
max_link_age      = 172800   ; 48h – harte Obergrenze fürs Token-Alter (Recency)
activation_window = 900      ; 15 min – Klick-Uhr, startet beim ersten Klick
max_serves_per_token = 3     ; Serves pro Token im Fenster (Resume/Retry/Mirror)
ip_safety_limit   = 60       ; grobe per-IP-Requests/min, NAT-tolerant
```

Konstanten-Defaults in `Files.php`:

```php
const MIN_TIME = 60;              // Fenster-Einheit (1 min) fürs per-IP-Safety-Net
const MAX_LINK_AGE = 172800;      // 48h
const ACTIVATION_WINDOW = 900;    // 15 min
const MAX_SERVES_PER_TOKEN = 3;
const IP_SAFETY_LIMIT = 60;
```

Beispiel-Testwerte (kleine Fenster, damit `testDownloadFlow.sh` schnell läuft) siehe
`.docker/dev/files/configs/application.ini`: `activation_window = 3`, `ip_safety_limit = 5`.

---

## 9. Tests

### `scripts/mintTestToken`

PHP-CLI-Minter, liest **dieselben** Configs (`application.ini` + `clients.ini`) und dieselbe `library/JWT.php` wie die App — die erzeugten Token werden vom Fileserver wie echte Store-Links akzeptiert.

```
docker compose exec -T ocs_files_app \
  php scripts/mintTestToken <client_id> <collection_id> <file_id> [mode]
# Ausgabe: "<jwt> <jti>"
```

| Mode | Erzeugt | Erwartung |
|------|---------|-----------|
| `valid`   | wohlgeformt, `jti`/`iat`/`exp`, ~1 h gültig | 200 |
| `legacy`  | nur `s` + `t` (kein `jti`/`iat`/`exp`) | 200 (Fallback) |
| `expired` | `exp`/`t` in Vergangenheit | 410 |
| `eternal` | `exp - iat` > `max_link_age` | 400 |
| `badhash` | `s` passt nicht (Signatur bleibt gültig) | 400 |
| `anon`    | ohne `u` | 200 |
| `ipbind`  | `stip = 10.99.99.99` | 200 + Log |

### `scripts/testDownloadFlow.sh`

E2E-Suite, treibt den laufenden Docker-Fileserver über HTTP und inspiziert den Redis-State.

```bash
CLIENT=1387085484 CID=12345 FID=67890 FNAME=foo.zip ./scripts/testDownloadFlow.sh
```

**Pflicht-Env (Fixture):** `CLIENT`, `CID`, `FID`, `FNAME` (ein realer, aktiver Datensatz in DB, für 200-Fälle auch auf Platte).

**Optional:** `BASE_URL` (`http://localhost:8086/api`), `DC`, `APP_SVC`, `REDIS_SVC`, `REDIS_DB` (14), `NS` (`ocs-files-2`), `MAX_SERVES` (3), `IP_LIMIT` (5), `RUN_IP_NET` (1), `RUN_REDIS_DOWN` (0).

Abgedeckte Fälle: Token-Lebenszyklus (1. Serve / Resume / Serve-Limit 429), Klick-Fenster verstrichen (410), abgelaufen (410), eternal (400), manipulierte Signatur (≠200), badhash (400), Soft-IP (200 + Log), per-IP-Safety-Net (429), legacy (200), anonym (200), Redis down (200, gated).

> Fenster-verstrichen-Fall wird deterministisch gemacht, indem `dl:act:{jti}` mit einem alten Timestamp (`i:1600000000;`) vorbelegt wird.

---

## 10. Migration: Store & API (separate Repos)

Der Fileserver ist **bereits ohne Änderung an Store/API deploybar** (Fallbacks). Für den vollen Nutzen müssen **beide** — Store *und* OCS-API — die Download-Token um `jti`/`iat`/`exp` (optional `stip`) erweitern. Da **beide dieselbe `JWT.php`** und dieselbe Download-Hash-Logik nutzen, ist die Änderung an beiden Stellen **identisch** und betrifft nur den Payload beim Minten.

### Zu ergänzende Claims

- **`jti`** — Nonce (`bin2hex(random_bytes(16))`), **pro Link neu**; keyt den Server-State (statt `sha1(jwt)`-Fallback).
- **`iat`** — issued-at; aktiviert erst zusammen mit `exp` den Sanity-Cap.
- **`exp`** — echte Hard-Expiry (statt legacy `t`); empfohlen ~48 h.
- optional **`stip`** — IP, die Store/API beim Rendern sah (nur Soft-Binding-Log).

### Copy-Paste-Diff (gilt 1:1 für Store und API)

```diff
 $issued_at   = time();
 $valid_until = $issued_at + 3600;   // 't' UNVERÄNDERT lassen (Basis des Download-Hashes 's')

 $payload = [
     'id'  => $file_id,
     'u'   => $user_id,                 // darf leer/anonym sein
     's'   => hash('sha512', $downloadSecret . $collection_id . $valid_until),
     't'   => $valid_until,
+
+    // Zwei-Uhren-Modell: exp trägt jetzt die lange Recency-Frist, nicht mehr t.
+    'jti' => bin2hex(random_bytes(16)),   // Nonce, pro Link eindeutig
+    'iat' => $issued_at,
+    'exp' => $issued_at + 172800,         // ~48h Hard-Expiry
+
+    // optional: Soft-IP-Binding (Fileserver loggt nur, blockt nie)
+    'stip' => $_SERVER['REMOTE_ADDR'] ?? null,
 ];

 $jwt = JWT::encode($payload, $jwt_secret, 'HS256');
```

### Fallstricke (beide Repos)

1. **`t` NICHT verlängern.** `t` fließt in `s = sha512(downloadSecret + collection_id + t)` und wird exakt gegengeprüft. `t` bei ~1 h belassen; die lange Frist kommt allein über `exp`.
2. **`jti` pro Link neu würfeln** — sonst kollidieren Aktivierungsfenster und Serve-Zähler zweier Links.
3. **Secrets identisch** halten: `jwt_secret` (`application.ini`) und `downloadSecret[client_id]` (`clients.ini`) müssen bei Store, API und Fileserver übereinstimmen (war bereits Voraussetzung).

Referenz-Implementierung: `scripts/generateDownloadLink` bzw. `scripts/mintTestToken`.
