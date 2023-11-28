<?php

declare(strict_types=1);













if (!date_default_timezone_get()) {
date_default_timezone_set('UTC');
}
mb_internal_encoding('UTF-8');

define('SERVER_TMP_DIR', '.cecil');
define('DIRECTORY_INDEX', '/index.html');
define('ERROR_404', '/404.html');
$isIndex = null;
$mediaSubtypeText = ['javascript', 'xml', 'json', 'ld+json', 'csv'];

$path = htmlspecialchars(urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));


if ($path == '/watcher') {
header("Content-Type: text/event-stream\n\n");
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
$flagFile = $_SERVER['DOCUMENT_ROOT'] . '/../' . SERVER_TMP_DIR . '/changes.flag';
if (file_exists($flagFile)) {
echo "event: reload\n";
printf("data: %s\n\n", file_get_contents($flagFile));
unlink($flagFile);
}
exit;
}


if ((empty(pathinfo($path, PATHINFO_EXTENSION)) || $path[-1] == '/') && file_exists($_SERVER['DOCUMENT_ROOT'] . rtrim($path, '/') . DIRECTORY_INDEX)) {
$path = rtrim($path, '/') . DIRECTORY_INDEX;
}


$filename = $_SERVER['DOCUMENT_ROOT'] . $path;


if ((realpath($filename) === false || strpos(realpath($filename), realpath($_SERVER['DOCUMENT_ROOT'])) !== 0) || !file_exists($filename) || is_dir($filename)) {
http_response_code(404);

if ($path == '/favicon.ico') {
header('Content-Type: image/vnd.microsoft.icon');

return logger(false);
}


if (!file_exists($_SERVER['DOCUMENT_ROOT'] . ERROR_404)) {
echo <<<END
        <!doctype html>
        <html>
            <head>
                <title>404 Not Found</title>
                <style>
                    html { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"; }
                    body { background-color: #fcfcfc; color: #333333; margin: 0; padding:0; }
                    h1 { font-size: 1.5em; font-weight: normal; background-color: #eeeeee; min-height:2em; line-height:2em; border-bottom: 1px inset #d6d6d6; margin: 0; }
                    h1, p { padding-left: 10px; }
                    code.url { background-color: #eeeeee; font-family:monospace; padding:0 2px; }
                </style>
                <meta http-equiv="refresh" content="2;URL=$path">
            </head>
            <body>
                <h1>Not Found</h1>
                <p>The requested resource <code class="url">$path</code> was not found on this server.</p>
            </body>
        </html>
        END;

return logger(true);
}
$path = ERROR_404;
$filename = $_SERVER['DOCUMENT_ROOT'] . ERROR_404;
}


$content = file_get_contents($filename);
$pathInfo = getPathInfo($path);

if ($pathInfo['media_maintype'] == 'text' || in_array($pathInfo['media_subtype'], $mediaSubtypeText)) {

$baseurl = explode(';', trim(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/../' . SERVER_TMP_DIR . '/baseurl')));
if (strstr($baseurl[0], 'http') !== false || $baseurl[0] != '/') {
$content = str_replace($baseurl[0], $baseurl[1], $content);
}

if ($pathInfo['media_subtype'] == 'html') {
if (file_exists(__DIR__ . '/livereload.js')) {
$script = file_get_contents(__DIR__ . '/livereload.js');
$content = str_ireplace('</body>', "  <script>$script    </script>\n  </body>", $content);
if (stristr($content, '</body>') === false) {
$content .= "\n<script>$script    </script>";
}
}
}
}


header('Etag: ' . md5_file($filename));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('X-Powered-By: Cecil,PHP/' . phpversion());
foreach ($pathInfo['headers'] as $header) {
header($header);
}
echo $content;

return logger(true);






function logger(bool $return): bool
{
\error_log(
\sprintf("%s:%d [%d]: %s\n", $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'], \http_response_code(), $_SERVER['REQUEST_URI']),
3,
$_SERVER['DOCUMENT_ROOT'] . '/../' . SERVER_TMP_DIR . '/server.log'
);

return $return;
}


function getPathInfo(string $path): array
{
$filename = $_SERVER['DOCUMENT_ROOT'] . $path;
$mediaType = \mime_content_type($filename); 
$info = [
'media_maintype' => explode('/', $mediaType)[0], 
'media_subtype' => explode('/', $mediaType)[1], 
];
$info['headers'] = [
"Content-Type: {$info['media_maintype']}/{$info['media_subtype']}",
];

switch (pathinfo($path, PATHINFO_EXTENSION)) {
case 'htm':
case 'html':
$info = [
'media_maintype' => 'text',
'media_subtype' => 'html',
'headers' => [
'Content-Type: text/html; charset=utf-8',
],
];
break;
case 'css':
$info['headers'] = [
'Content-Type: text/css',
];
break;
case 'js':
$info = [
'media_maintype' => 'application',
'media_subtype' => 'javascript',
'headers' => [
'Content-Type: application/javascript',
],
];
break;
case 'svg':
$info['headers'] = [
'Content-Type: image/svg+xml',
];
break;
case 'xml':
$info['headers'] = [
'Content-Type: application/xml; charset=utf-8',
'X-Content-Type-Options: nosniff',
];
break;
case 'xsl':
$info['headers'] = [
'Content-Type: application/xslt+xml',
];
break;
case 'yml':
case 'yaml':
$info['headers'] = [
'Content-Type: application/yaml',
];
break;
}

switch ($info['media_maintype']) {
case 'video':
case 'audio':
$info['headers'] += [
'Content-Transfer-Encoding: binary',
'Content-Length: ' . filesize($filename),
'Accept-Ranges: bytes',
];
break;
}

return $info;
}
