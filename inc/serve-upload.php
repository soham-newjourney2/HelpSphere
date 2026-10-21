<?php
//serve-upload.php
// Serve a file from an uploads folder, only if it really lives inside that folder
function serve_upload($dir, $stored)
{
    $base=realpath($dir);
    $path=false;
    if($base && $stored!=='')
    {
        $try=realpath($base.DIRECTORY_SEPARATOR.basename($stored));
        if($try && strpos($try, $base.DIRECTORY_SEPARATOR)===0 && is_file($try)) $path=$try;
    }
    if(!$path) { http_response_code(404); exit('Document file not found.'); }

    while(ob_get_level()>0) ob_end_clean();
    @ini_set('zlib.output_compression','Off');
    if(session_status()===PHP_SESSION_ACTIVE) session_write_close();

    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
    $inline=in_array($mime,['application/pdf','image/jpeg','image/png','image/gif','image/webp'],true);
    header('Content-Type: '.$mime);
    header('Content-Length: '.filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="'.rawurlencode(basename($path)).'"');
    readfile($path);
    exit;
}
