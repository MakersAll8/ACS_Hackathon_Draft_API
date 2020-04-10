<?php

require_once './inc/PageTemplate.php';

$title = _("Community Care System API");
$home_title = _("About Me");
$app_url = PageTemplate::app_url();

PageTemplate::page(<<<HTML

HTML
    , $title);