<?php

class PageTemplate
{
    public static function page($body, $title = "Community Care System")
    {
        $app_url = PageTemplate::app_url();
        $nav = PageTemplate::navBar();
        $body = $nav.$body;

        echo <<<HTML
            <!DOCTYPE html>
            <html lang="en_US">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link rel="icon" href="{$app_url}images/">
                <title>{$title}</title>
                <link rel="stylesheet" type="text/css" href="{$app_url}inc/index.css">
                <script src="{$app_url}inc/index.js"></script>
                <script src="{$app_url}inc/httpRequest.js"></script>
            </head>
            <body>
                <div class="container">{$body}</div>
            </body>
            <footer>
                <p>&copy; 2020 Stay at Home</p>
                <p>Made in Melbourne with love</p>
               
            </footer>
            </html>
HTML;
    }

    public static function navBar(){
        return <<<HTML
        <div id="nav-bar">
            <div id="nav-title">Community Care</div>
            <div id="nav-links">
                <a href="#home" class="active">Home</a>
                <a href="#works">Works</a>
            </div>
        </div>
HTML;

    }

    public static function app_url($getBaseURL=false){
        //current directory that init.php is in
        $directory = realpath(dirname(__FILE__));
        //apache server root path
        $document_root = realpath($_SERVER['DOCUMENT_ROOT']);
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        if($getBaseURL){return $base_url."/";}

        if (strpos($directory, $document_root) === 0) {
            $base_url .= str_replace(DIRECTORY_SEPARATOR, '/', substr($directory, strlen($document_root)));
        }

        //define application server URL
        return str_replace("/inc", "/", $base_url);
    }


}