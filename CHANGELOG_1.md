# Webix Feed 3.1.36 (2026-08-08)

Optimerings- og sikkerhedsrelease. Ingen ændringer i udseende eller markup — feed-HTML og CSS-regler er uændrede.

## Sikkerhed
- **Hardcoded GitHub-token fjernet** fra `webix-feed.php`. Tokenet i v3.1.35 og ældre lå i klartekst i pluginfilen på alle kundesites og SKAL revokes på GitHub.
  Updateren læser nu tokenet fra `WEBIX_GITHUB_TOKEN` i `wp-config.php` eller optionen `webix_github_token`. Uden token virker opdateringstjek stadig, hvis repoets releases er offentlige.

## Performance
- **Transient-cache på feed-HTML** (alle 3 shortcodes): default 10 min pr. unik kombination af endpoint/side/enhed. Justér med filteret `webix_feed_cache_ttl` (0 = slå fra). Reducerer blokerende API-kald pr. sidevisning til ~1 pr. 10 min pr. variant.
- **Stale-fallback ved API-fejl**: svarer API'et ikke (timeout/fejlkode/tom body), serveres senest kendte HTML (op til 48 t) i stedet for teksten "There be errors, yos!", som kunne blive indekseret af Google.
- `main.css` + `webix-feed-public.css` minificeret (samme regler, kun whitespace/kommentarer fjernet).
- JS flyttet til footer (blokerer ikke længere rendering).
- Timeout på API-kald sat eksplicit (8 s feed / 10 s updater).

## Fejlrettelser
- `webix-feed-public.js` kastede TypeError på ALLE sider i "no rangeslider"-builds (leder efter fjernede slider-elementer). Nu guarded — scriptet gør intet, når elementerne ikke findes.
- Updateren downloadede hele release-zip'en ved hvert opdateringstjek og smed den væk. Fjernet.
- Dubleret valideringsblok i redirect-koden ryddet op (samme adfærd).

## Efter installation
1. Revoke det gamle token på GitHub (Settings → Developer settings → Tokens classic).
2. Purge Cloudflare/Varnish, så nye asset-versioner (?ver=3.1.36) slår igennem.
3. Ved manuel upload: vælg "Replace current with uploaded" hvis WordPress spørger.

---

# Webix Feed 3.1.37 (2026-08-08) — HOTFIX

- **Fatal error rettet i updateren** (`webix-updater.php`): når GitHub API'et ikke returnerer en release-liste (404 ved privat repo uden token, 401 ved revoket token, rate limit), returnerer det et JSON-objekt — og den gamle kode kastede "Cannot use object of type stdClass as array" i wp-admin. Nu tjekkes HTTP-status og svar-struktur, før der læses fra svaret; alle fejlscenarier falder stille tilbage til "ingen opdatering".
- Fejlen var latent i ALLE tidligere versioner (også 3.1.35) og udløses på gamle versioner, i samme øjeblik det gamle token revokes. Udrul derfor 3.1.37 på alle sites FØR eller hurtigst muligt efter token-revoke.
- Regressionstestet mod 9 API-scenarier × 3 transient-tilstande (27 kombinationer, 0 fatals).

---

# Webix Feed 3.1.38 (2026-08-09)

- Kodebasen flyttet til nyt, rent GitHub-repo (det gamle repos historik indeholder det lækkede token og er udfaset).
- Ingen funktionelle ændringer ift. 3.1.37 — version-bump så eksisterende 3.1.37-installationer tilbydes opdateringen og auto-update-kæden kan verificeres ende-til-ende.
- Plugin-identitet omdøbt til `webix-feed-no-rangeslider` (mappe, updater-URL, menu-sti). Nyt repo: webixaps/webix-feed-no-rangeslider.
- Migrationsvagt: aktiveres det nye plugin, mens det gamle "webix-feed" stadig er aktivt, bailer det med en admin-advarsel i stedet for "Cannot redeclare"-fatal.

---

# Webix Feed 3.1.39 (2026-08-09) — HOTFIX

- **3.1.38 var reelt død:** migrationsvagten tjekkede `function_exists('run_webix_feed')`, men PHP definerer filens egne funktioner allerede ved indlæsning — vagten ramte altid sig selv, pluginnet gik i tomgang, og feedet forsvandt fra siderne. Vagten tjekker nu KUN konstanten `WEBIX_FEED_VERSION` (defineres ved kørsel og kun af det gamle plugin).
- Load-path-regressionstest tilføjet: frisk indlæsning SKAL registrere alle 3 shortcodes; legacy-scenarie skal baile med admin-notice. (3.1.38 fejler testen; 3.1.39 består.)

---

# Webix Feed 3.1.40 (2026-08-09)

- Ingen kodeændringer. Rent version-bump som ende-til-ende-test af auto-opdatering fra GitHub Releases (3.1.39 → 3.1.40 via WP-Opdateringer).
