<?php
// Kør: php tests/test_strip_godkendelse.php admin/class-webix-feed-admin.php
if (PHP_SAPI !== 'cli') { exit; }
// Stub-harness: kører Webix_Feed_Admin::strip_approval_rating() og fetch_feed_html()
// uden WordPress. Fixtures er kopieret fra live-svar fra ai.webixaps.com (2026-11-22).

define('MINUTE_IN_SECONDS', 60);
define('DAY_IN_SECONDS', 86400);
$GLOBALS['T'] = array();          // transient-store
$GLOBALS['HTTP'] = null;          // næste HTTP-svar
$GLOBALS['TTL'] = 600;
function add_action() {}
function add_filter() {}
function apply_filters($n, $v) { return $n === 'webix_feed_cache_ttl' ? $GLOBALS['TTL'] : $v; }
function get_transient($k) { return isset($GLOBALS['T'][$k]) ? $GLOBALS['T'][$k] : false; }
function set_transient($k, $v, $t) { $GLOBALS['T'][$k] = $v; return true; }
function wp_remote_get($u, $a = array()) { $GLOBALS['CALLS'][] = $u; return $GLOBALS['HTTP']; }
class WP_Error {}
function is_wp_error($r) { return $r instanceof WP_Error; }
function wp_remote_retrieve_response_code($r) { return is_array($r) ? $r['code'] : 0; }
function wp_remote_retrieve_body($r) { return is_array($r) ? $r['body'] : ''; }

require $argv[1];

$fail = 0; $pass = 0;
function check($name, $cond) { global $fail, $pass; if ($cond) { $pass++; echo "  ok   $name\n"; } else { $fail++; echo "  FAIL $name\n"; } }
function strip($h) { return Webix_Feed_Admin::strip_approval_rating($h); }

// ---------- Fixtures ----------
function card($approval, $trend = 'up-trend') {
    $li = $approval === null ? '' : '<li class="list-group-item pl-0 pr-0 pb-2"><p class="text-wrapper">Godkendelse: ' . $approval . '</p></li>';
    return "<div class=\"card\">\r\n <div class=\"d-md-flex col-lg-6 right-section\">\r\n <div class=\"col-12 col-md-6 align-self-center p-0 mb-2 mb-lg-0\">\r\n <ul class=\"list-group list-group-flush m-0\">\r\n "
        . $li
        . '<li class="list-group-item pl-0 pr-0 pb-2"><p class="trending-wrapper ' . $trend . '"> Trending </p></li><li class="list-group-item pl-0 pr-0 pb-2"><p class="text-wrapper">Ingen skjulte gebyrer</p></li><li class="list-group-item pl-0 pr-0 pb-2"><p class="text-wrapper">Kreditvurdering foretages af långiver</p></li> </ul>'
        . "\r\n </div>\r\n\r\n <div class=\"col-12 col-md-6 align-self-center mb-2 mt-2 mt-lg-0 mb-lg-0 text-center\">\r\n <a href=\"https://laaneportalen.dk/comparison/go/feed/x?a=1&amp;b=2\" target=\"_blank\" rel=\"sponsored\" class=\"btn\">ANSØG HER</a>\r\n <p>Advarsel! Det koster penge at låne penge</p>\r\n </div>\r\n </div>\r\n <p class=\"example\">eks: Samlet kreditbeløb 100.000 kr. ÅOP 3,80 %. Variabel debitorrente 3,48 %.</p>\r\n</div>\r\n";
}
function li($v) { return '<li class="list-group-item pl-0 pr-0 pb-2"><p class="text-wrapper">Godkendelse: ' . $v . '</p></li>'; }

function tm_block($label, $value) {
    return "                    <div class=\"tm-row-inner\">\n                        <div class=\"tm-label\">\n                            $label\n                        </div>\n                        <div class=\"tm-value\">\n                            $value\n                        </div>\n                    </div>\n";
}
function tm_card($with) {
    return "<div class=\"tm-row\">\n" . tm_block('ÅOP', '<span> 8,21 - 16,08 % </span>')
        . ($with ? tm_block('Godkendelse', 'Acceptabel') : '')
        . "                    <div class=\"tm-row-inner\">\n                        <div class=\"tm-label\">\n                           Trending\n                        </div>\n                        <div class=\"tm-value\">\n                            <span class=\"trending-wrapper up-trend\">Trending</span> \n                        </div>\n                    </div>\n</div>\n";
}

$svg = '<svg width="30px" height="30px" viewBox="0 0 1024 1024" class="icon" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_iconCarrier"><path d="M512 512m-448 0a448 448 0 1 0 896 0 448 448 0 1 0-896 0Z" fill="#2196F3"></path></g></svg>';
function table($with, $label = 'Godkendelse', $extraRow = '') {
    global $svg;
    $th = $with ? "\n <th class=\"text-center\"> $label </th>" : '';
    $h = "<table class=\"campaign-table\">\n<thead>\n <tr class=\"campaign-table-header\">\n <th>Låneudbyder</th>\n <th class=\"text-right\">Lånebeløb</th>\n <th class=\"text-right\">Løbetid</th>\n <th class=\"text-right\">Min. alder</th>\n <th class=\"text-right\">ÅOP</th>\n <th class=\"text-center\"> </th>$th\n <th class=\"text-center\"> Trending </th>\n <th class=\"text-center\"> Rating </th>\n <th class=\"text-center\"> Start Lån Nu </th>\n </tr>\n</thead>\n<tbody>\n";
    foreach (array(array('Facit Lån', 'Acceptabel'), array('Lendo', 'Moderat'), array('Cashper', 'Høj')) as $r) {
        $td = $with ? "<td class=\"text-center\">{$r[1]}</td>" : '';
        $h .= "<tr class=\"campaign-row \"> <td class=\"campaign-info text-left\"> {$r[0]} </td> <td class=\"text-right\">10.000 - 300.000 kr</td> <td class=\"text-right\">36 - 120 mdr.</td> <td class=\"text-right\">21 år</td> <td class=\"text-right\"> <span> 8,21 - 16,08 % </span> </td> <td class=\"text-center\"> <span class=\"ttip\"> $svg <span class=\"tooltiptext\">eks: 40.000 kr. Løbetid 60 måneder. Etableringsomkostninger 1600 kr. Variabel debitorrente 8,21- 16,08 %.</span></span> </td> $td <td class=\"text-center\"><span class=\"trending-wrapper up-trend\">Trending</span> </td> <td class=\"text-center\"><div class=\"rating-wrapper text-center\"><!-- Filled Star SVG --> $svg</div></td> <td class=\"text-center\"> <a target=\"_blank\" class=\"table-custom-btn-success\" rel=\"sponsored\" href=\"https://x.dk/go?id=1\"> ANSØG HER </a> </td></tr>\n";
    }
    return $h . $extraRow . "</tbody>\n</table>\n<p>Advarsel! Det koster penge at låne penge</p>\n";
}

echo "1) Kort (desktop/mobil) — <li> fjernes, alt andet byte-identisk\n";
foreach (array('Acceptabel', 'Moderat', 'Høj', 'Lav') as $v) {
    $in = card($v); $exp = card(null);
    check("værdi '$v' fjernet, rest uændret", strip($in) === $exp);
}
$feed = "<div id=\"campaigns-weedget\">\r\n" . card('Acceptabel') . card('Moderat', 'down-trend') . card('Høj', 'neutral-trend') . "</div>";
$out = strip($feed);
check('3 kort: 0 × Godkendelse tilbage', stripos($out, 'Godkendelse') === false);
check('3 kort: præcis 3 <li> fjernet', substr_count($feed, '<li ') - substr_count($out, '<li ') === 3);
check('ingen tom <li> efterladt', !preg_match('~<li[^>]*>\s*(<p[^>]*>\s*</p>)?\s*</li>~', $out));
check('advarsel/ÅOP/eksempel/CTA bevaret', substr_count($out, 'Advarsel! Det koster penge at låne penge') === 3 && substr_count($out, 'ÅOP 3,80 %') === 3 && substr_count($out, 'ANSØG HER') === 3 && substr_count($out, '?a=1&amp;b=2') === 3);
check('rækkefølge af øvrige punkter bevaret', preg_replace('~\s+~', '', strip(card('X'))) === preg_replace('~\s+~', '', card(null)));
check('mellemrum efter kolon / store bogstaver', strip(card(null) . '<ul><li class="a"><p class="b">GODKENDELSE :  Moderat </p></li></ul>') === card(null) . '<ul></ul>');

echo "2) Tabel, mobil (tm-row-inner)\n";
$in = tm_card(true) . tm_card(true); $exp = tm_card(false) . tm_card(false);
check('Godkendelse-blokke fjernet, rest byte-identisk', strip($in) === $exp);

echo "3) Tabel, desktop — kolonne fjernes i header + alle rækker\n";
$out = strip(table(true));
check('resultat === tabel uden kolonnen (whitespace-normaliseret)', preg_replace('~\s+~', ' ', $out) === preg_replace('~\s+~', ' ', table(false)));
check('ingen Godkendelse/Acceptabel/Moderat/Høj-celler tilbage', !preg_match('~Godkendelse|>Acceptabel<|>Moderat<|>Høj<~u', $out));
preg_match_all('~<tr\b.*?</tr>~s', $out, $rows);
$counts = array_map(function ($r) { return preg_match_all('~<t[hd]\b~', $r); }, $rows[0]);
check('alle rækker har 9 celler (før 10)', $counts === array_fill(0, 4, 9));
check('tooltip-eksempel, ÅOP og CTA bevaret', substr_count($out, 'eks: 40.000 kr.') === 3 && substr_count($out, '8,21 - 16,08 %') === 3 && substr_count($out, 'ANSØG HER') === 3 && strpos($out, 'Advarsel! Det koster penge at låne penge') !== false);
$colspan = "<tr class=\"note\"><td colspan=\"10\">Godkendelse: se vilkår</td></tr>\n";
$out2 = strip(table(true, 'Godkendelse', $colspan));
check('colspan-række røres ikke', strpos($out2, '<td colspan="10">Godkendelse: se vilkår</td>') !== false);
check('svensk tabel ("Godkännande") uændret', strip(table(true, 'Godkännande')) === table(true, 'Godkännande'));

echo "4) Ingen falske træffere\n";
$article = '<div class="entry-content"><p>Godkendelse af lån kræver en kreditvurdering.</p><ul><li>Godkendelse sker hos långiver</li></ul></div>';
check('brødtekst/FAQ-<li> uden "Godkendelse:"-mønster uændret', strip($article) === $article);
$sv = card(null) . '<li class="list-group-item"><p class="text-wrapper">Godkännande: Hög</p></li>';
check('svensk kort uændret', strip($sv) === $sv);
check('null/ikke-streng returneres uændret', strip(null) === null && strip(false) === false);
check('HTML uden Godkendelse er identisk', strip(table(false) . card(null)) === table(false) . card(null));

echo "5) Store svar (~650 KB, som get_campaigns_by desktop)\n";
$big = str_repeat(card('Acceptabel'), 400) . table(true) . str_repeat(tm_card(true), 200);
$t = microtime(true); $out = strip($big); $ms = (microtime(true) - $t) * 1000;
check(sprintf('%.0f KB behandlet på %.1f ms, preg-fejl: %s', strlen($big) / 1024, $ms, preg_last_error_msg()), $out !== null && preg_last_error() === PREG_NO_ERROR);
check('0 × Godkendelse i stort svar', stripos($out, 'Godkendelse') === false);
check('400 advarsler bevaret', substr_count($out, 'Advarsel! Det koster penge at låne penge') === 401);

echo "6) fetch_feed_html — alle kodestier (cache, frisk, stale, fejl)\n";
$admin = (new ReflectionClass('Webix_Feed_Admin'))->newInstanceWithoutConstructor();
$m = new ReflectionMethod('Webix_Feed_Admin', 'fetch_feed_html'); $m->setAccessible(true);
$params = array('site_url' => 'https://laaneportalen.dk', 'is_mobile' => false, 'device_type' => 'desktop', 'feed_subcategory_id' => 1);
$hash = md5('https://ai.webixaps.com/get_campaigns?' . http_build_query($params));

$GLOBALS['T'] = array('webix_feed_' . $hash => card('Acceptabel')); $GLOBALS['CALLS'] = array();
check('cachet gammel HTML filtreres straks (uden cache-tømning)', $m->invoke($admin, 'https://ai.webixaps.com/get_campaigns', $params) === card(null) && count($GLOBALS['CALLS']) === 0);

$GLOBALS['T'] = array(); $GLOBALS['HTTP'] = array('code' => 200, 'body' => card('Moderat'));
$r = $m->invoke($admin, 'https://ai.webixaps.com/get_campaigns', $params);
check('frisk svar filtreres', $r === trim(card(null)));
check('cache gemmer råt svar (filter køres ved output)', $GLOBALS['T']['webix_feed_' . $hash] === trim(card('Moderat')));

$GLOBALS['T'] = array('webix_feed_stale_' . $hash => card('Høj')); $GLOBALS['HTTP'] = new WP_Error();
check('stale-fallback ved API-fejl filtreres', $m->invoke($admin, 'https://ai.webixaps.com/get_campaigns', $params) === card(null));

$GLOBALS['T'] = array(); $GLOBALS['HTTP'] = array('code' => 500, 'body' => 'x');
check('fejlkommentar uændret', $m->invoke($admin, 'https://ai.webixaps.com/get_campaigns', $params) === '<!-- webix-feed: feed midlertidigt utilgaengeligt -->');

$GLOBALS['TTL'] = 0; $GLOBALS['T'] = array(); $GLOBALS['HTTP'] = array('code' => 200, 'body' => tm_card(true));
check('cache slået fra (TTL 0): filtreres stadig', $m->invoke($admin, 'https://ai.webixaps.com/get_campaigns', $params) === trim(tm_card(false)) && $GLOBALS['T'] === array());

echo "\n$pass bestået, $fail fejlet\n";
exit($fail ? 1 : 0);
