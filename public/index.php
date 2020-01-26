<?php

require_once(dirname(__dir__) . "/vendor/autoload.php");

use Sowe\Framework\Database;
use Sowe\Framework\Mailer;
use Sowe\Framework\HTTP\Router;

use Api\Servers;
use Api\Users;
use Api\Auth;
use Playform\Manager;
use Playform\GameManager;

$mailer = new Mailer(
    SMTP_HOSTNAME, SMTP_USERNAME, SMTP_PASSWORD, 
    SMTP_SENDER, SMTP_SENDERNAME, SMTP_AUTH, SMTP_SECURITY
);
$database = new Database(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

/**
 * Serving API
 */
$router = new Router("", $database);
$router
    ->on("api/servers",   Servers::class)
    ->on("api/servers/%", Servers::class)
    ->on("api/auth",      Auth::class)
    ->on("api/users",     Users::class)
    ->on("api/users/%",   Users::class)
    ->on("",              Manager::class)
    ->on("play",          Manager::class)
    ->on("play/%",        Manager::class)
    ->on("play/%/%",      Manager::class)
    ->on("play/%/%",      GameManager::class)
    ->route();


    //TITLE, PORT, ID, USERNAME, TOKEN