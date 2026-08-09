# Webix Feed (WordPress-plugin)

WordPress-plugin der henter færdigrenderet kampagne-HTML fra ai.webixaps.com og viser den via shortcodes. Auto-opdaterer fra dette repos **GitHub Releases**.

## Shortcodes
| Shortcode | Endpoint | Parametre |
|---|---|---|
| `[webix-comparison-feed]` | `/get_campaigns` | konfigureres pr. side i Webix-metaboksen |
| `[webix-feed-by-epc]` | `/get_campaigns_by` (by=epc) | `limit`, `layout` (default/table), `headercolor` |
| `[webix-feed-by-trend]` | `/get_campaigns_by` (by=trend) | `limit`, `layout`, `headercolor` |

Feed-HTML caches i en transient (10 min pr. enheds-variant, filter: `webix_feed_cache_ttl`; 0 = fra). Ved API-fejl serveres senest kendte HTML (op til 48 t) — aldrig fejltekst.

## Sådan udgives en ny version (auto-update til alle sites)
1. Ret koden. Bump versionen **to steder** i `webix-feed.php`: `Version:`-headeren og `WEBIX_FEED_VERSION`.
2. Commit + push til `master`.
3. Opret en **Release** på GitHub med tag = versionsnummeret **uden v-præfiks** (fx `3.1.39` — IKKE `v3.1.39`; updateren sammenligner tag direkte med plugin-versionen).
4. Alle sites ser opdateringen ved næste WP-opdateringstjek og kan opdatere med ét klik.

## Token / privat vs. offentligt repo
- **Offentligt repo (anbefalet):** intet token nødvendigt på sites. Ingen secrets i pluginnet.
- **Privat repo:** hvert site skal have et fine-grained token (kun Contents: Read på dette repo) i `wp-config.php`:
  `define( 'WEBIX_GITHUB_TOKEN', '...' );`

**Hardcod ALDRIG et token i pluginkoden.** Version ≤3.1.35 distribuerede et token i klartekst til alle kundesites — det er revoket, og fejlen må ikke gentages.

## Historik
Se `CHANGELOG.md`. Dette repo (`webixaps/webix-feed-no-rangeslider`) afløser det gamle `webixaps/webix-feed`, hvis git-historik indeholder det lækkede token — det gamle repo bruges ikke længere. Pluginnet installeres i mappen `webix-feed-no-rangeslider` og kan køre side om side med en deaktiveret gammel installation (en indbygget vagt forhindrer fatal fejl, hvis begge aktiveres).
