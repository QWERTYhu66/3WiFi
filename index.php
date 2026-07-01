<?php

include 'config.php';

error_reporting(E_ALL & ~E_NOTICE);

function validPage($page)
{
    $allowed = [
        'home','rules','faq','apidoc','map','find','ranges',
        'devmac','wpspin','upload','graph','stat','user'
    ];
    return in_array($page, $allowed, true) ? $page : '';
}

function validForm($form)
{
    $allowed = ['win_login','win_reg','win_newpass','win_wait'];
    return in_array($form, $allowed, true) ? $form : '';
}

function preparePage(&$content)
{
    global $page;

    $mb = 'menubtn';
    $mbs = $mb.' mbsel';

    $content = str_replace('%chk_docs%', in_array($page, ['home','faq','apidoc','rules']) ? $mbs : $mb, $content);
    $content = str_replace('%chk_map%', $page == 'map' ? $mbs : $mb, $content);
    $content = str_replace('%chk_find%', $page == 'find' ? $mbs : $mb, $content);
    $content = str_replace('%chk_tool%', in_array($page, ['ranges','devmac','wpspin']) ? $mbs : $mb, $content);
    $content = str_replace('%chk_load%', $page == 'upload' ? $mbs : $mb, $content);
    $content = str_replace('%chk_st%', in_array($page, ['stat','graph']) ? $mbs : $mb, $content);
    $content = str_replace('%chk_user%', $page == 'user' ? $mbs : $mb, $content);

    $sm = 'submbtn';
    $sms = $sm.' smsel';

    $content = str_replace('%chk_home%', $page == 'home' ? $sms : $sm, $content);
    $content = str_replace('%chk_faq%', $page == 'faq' ? $sms : $sm, $content);
    $content = str_replace('%chk_apidoc%', $page == 'apidoc' ? $sms : $sm, $content);
    $content = str_replace('%chk_rul%', $page == 'rules' ? $sms : $sm, $content);
    $content = str_replace('%chk_rang%', $page == 'ranges' ? $sms : $sm, $content);
    $content = str_replace('%chk_dev%', $page == 'devmac' ? $sms : $sm, $content);
    $content = str_replace('%chk_wps%', $page == 'wpspin' ? $sms : $sm, $content);
    $content = str_replace('%chk_stat%', $page == 'stat' ? $sms : $sm, $content);
    $content = str_replace('%chk_grph%', $page == 'graph' ? $sms : $sm, $content);

    global $theme_data, $theme, $themes_str;
    $content = str_replace('%theme_css%', $theme_data['css'] ?? '', $content);
    $content = str_replace('%theme_head%', $theme_data['head'] ?? '', $content);
    $content = str_replace('%theme_ajax%', $theme_data['ajax'] ?? '', $content);

    $content = str_replace('%theme%', $theme ?? '', $content);
    $content = str_replace('%themes%', $themes_str ?? '', $content);

    global $broadcast;
    $content = str_replace('%broadcast%', $broadcast ?? '', $content);

    global $UserManager, $l10n, $ban_reasons, $profile, $lat, $lon, $rad;

    $ViewLogin = $UserManager->Login ?? '';
    $ViewNick = $UserManager->Nick ?? '';
    $ViewLevel = $UserManager->Level ?? 0;
    $ViewInvites = $UserManager->invites ?? 0;
    $ViewRAPI = $UserManager->ReadApiKey ?? '';
    $ViewWAPI = $UserManager->WriteApiKey ?? '';
    $ViewReg = $UserManager->RegDate ?? '';
    $ViewInviter = $UserManager->InviterNickName ?? '';
    $ViewUser = '';

    if (!empty($UserManager->vuID)) {
        $ViewUser = $ViewNick;
        $info = $UserManager->getUserInfo($UserManager->vuID);

        $ViewLogin = $info['login'] ?? '';
        $ViewNick = $info['nick'] ?? '';
        $ViewLevel = (int)($info['level'] ?? 0);
        $ViewInvites = (int)($info['invites'] ?? 0);
        $ViewRAPI = $info['rapikey'] ?? '';
        $ViewWAPI = $info['wapikey'] ?? '';
        $ViewReg = $info['regdate'] ?? '';
        $ViewInviter = $UserManager->getUserNameById($info['puid'] ?? 0);
    }

    if (empty($ViewRAPI)) $ViewRAPI = $l10n['no_access'] ?? '';
    if (empty($ViewWAPI)) $ViewWAPI = $l10n['no_access'] ?? '';

    $content = str_replace('%login_str%', ($UserManager->isLogged() ? ($l10n['menu_logout'] ?? '') : ($l10n['menu_login'] ?? '')), $content);
    $content = str_replace('%ban_reasons%', json_encode($ban_reasons ?? []), $content);
    $content = str_replace('%profile%', $profile ?? '', $content);
    $content = str_replace('%isUser%', (int)$UserManager->isLogged(), $content);

    $content = str_replace('%login%', htmlspecialchars($ViewLogin), $content);
    $content = str_replace('%nick%', htmlspecialchars($ViewNick), $content);
    $content = str_replace('%user_access_level%', $ViewLevel, $content);
    $content = str_replace('%user_invites%', $ViewInvites, $content);
    $content = str_replace('%view_user%', htmlspecialchars($ViewUser), $content);

    $content = str_replace('%rapikey%', $ViewRAPI, $content);
    $content = str_replace('%wapikey%', $ViewWAPI, $content);
    $content = str_replace('%regdate%', $ViewReg, $content);
    $content = str_replace('%refuser%', $ViewInviter, $content);

    $content = str_replace('%var_lat%', $lat ?? 0, $content);
    $content = str_replace('%var_lon%', $lon ?? 0, $content);
    $content = str_replace('%var_rad%', $rad ?? 0, $content);

    $content = str_replace('%var_ymaps_apikey%', YMAPS_APIKEY ?? '', $content);
    $content = str_replace('%var_wait%', GUEST_WAIT ?? '', $content);

    foreach (($l10n ?? []) as $key => $value) {
        $content = str_replace("%l10n_$key%", $value, $content);
    }
}

function setFloat($in, &$out)
{
    if ($in === null || $in === '') return;
    $in = str_replace(',', '.', $in);
    if (is_numeric($in)) $out = (float)$in;
}

function getNews()
{
    $project_news = json_decode(@file_get_contents('project_news.json'), true) ?? [];
    $service_news = json_decode(@file_get_contents('service_news.json'), true) ?? [];

    $news = array_merge_recursive($service_news, $project_news);
    krsort($news);

    $out = "<ul>\n";
    foreach ($news as $date => $list) {
        $a = array_merge(["<b>$date</b>"], (array)$list);
        $out .= '<li>' . implode("<br>\n", $a) . "</li>\n";
    }
    $out .= '</ul>';

    return $out;
}

/* ---------------- ROUTING ---------------- */

if (!empty($_GET['redir'])) {
    $page = validPage($_GET['redir']);
    if ($page !== '') {
        header('HTTP/1.1 303 See Other');
        header('Location: ' . $page);
        exit();
    }
    exit();
}

require_once 'user.class.php';
require_once 'utils.php';

$UserManager = new User();
$UserManager->load();

if (($UserManager->Level ?? 0) == -2) {
    $UserManager->out();
    $UserManager->setUser();
}

$incscript = @file_get_contents('counter.txt') ?? '';

$page = validPage($_GET['page'] ?? 'home');
$form = validForm($_GET['fetch'] ?? '');

if ($page === '') $page = '404';

/* ---------------- LOCATION SAFE PARSE ---------------- */

$lat = DEFAULT_LAT;
$lon = DEFAULT_LON;
$rad = DEFAULT_RAD;

$uselocation = [];

if (!empty($_COOKIE['uselocation'])) {
    parse_str($_COOKIE['uselocation'], $uselocation);
}

if (isset($uselocation['lat'])) setFloat($uselocation['lat'], $lat);
if (isset($uselocation['lon'])) setFloat($uselocation['lon'], $lon);
if (isset($uselocation['rad'])) setFloat($uselocation['rad'], $rad);

if (isset($_GET['lat'])) setFloat($_GET['lat'], $lat);
if (isset($_GET['lon'])) setFloat($_GET['lon'], $lon);
if (isset($_GET['rad'])) setFloat($_GET['rad'], $rad);

/* ---------------- THEME ---------------- */

include_once loadLanguage();

$theme_base = 'themes';
$themes = array_filter(scandir($theme_base), function ($t) use ($theme_base) {
    $t = preg_replace("/[^a-zA-Z0-9\-_]+/", "", $t);
    return file_exists("$theme_base/$t/theme.php");
});

$themes = array_values($themes);

$theme = $_COOKIE['theme'] ?? '';

$theme_data = ['css'=>'','head'=>'','ajax'=>''];

if ($theme && in_array($theme, $themes, true)) {
    require_once "$theme_base/$theme/theme.php";
} else {
    $theme_data['css'] = 'css/style.css?' . @filemtime('css/style.css');
}

/* ---------------- RENDER ---------------- */

if ($form !== '') {
    $content = @file_get_contents($form.'.html');
    preparePage($content);
    echo $content;
    exit();
}

if (!file_exists($page.'.html')) $page = '404';

$hfile = file_get_contents($page.'.html');

$title = getStringBetween($hfile, '<title>', '</title>');
$head = getStringBetween($hfile, '<head>', '</head>');
$content = getStringBetween($hfile, '<body>', '</body>');

$content = str_replace('%content%', $content, file_get_contents('index.html'));
$content = str_replace('%title%', $title ?: ($l10n['title'] ?? ''), $content);
$content = str_replace('%head%', $head, $content);
$content = str_replace('%page%', $page, $content);

if (strpos($content, '%news%') !== false) {
    $content = str_replace('%news%', getNews(), $content);
}

preparePage($content);

echo str_replace('</body>', ($incscript ?? '').'</body>', $content);