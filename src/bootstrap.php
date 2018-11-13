<?php
declare(strict_types=1);
require_once __DIR__."/vendor/autoload.php";

use MVQN\Localization\Translator;
use MVQN\REST\RestClient;

use UCRM\Common\Log;
use UCRM\Common\Plugin;

use UCRM\Common\Config;
use UCRM\Plugins\Settings;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * bootstrap.php
 *
 * A common configuration and initialization file.
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */

// IF there is a /.env file, THEN load it!
if(file_exists(__DIR__."/../.env"))
    (new \Dotenv\Dotenv(__DIR__."/../"))->load();

// Initialize the Plugin libraries using this directory as the plugin root!
Plugin::initialize(__DIR__);

// Regenerate the Settings class, in case anything has changed in the manifest.json file.
Plugin::createSettings("UCRM\\Plugins");

// Generate the REST API URL.
$restUrl = (getenv("UCRM_REST_URL_DEV") ?: "http://localhost")."/api/v1.0";

// Configure the REST Client...
RestClient::setBaseUrl($restUrl); //Settings::UCRM_PUBLIC_URL . "api/v1.0");
RestClient::setHeaders([
    "Content-Type: application/json",
    "X-Auth-App-Key: " . Settings::PLUGIN_APP_KEY
]);

// Set the dictionary directory and "default" locale.
try
{
    Translator::setDictionaryDirectory(__DIR__ . "/translations/");
    Translator::setCurrentLocale(str_replace("_", "-", Config::getLanguage()) ?: "en-US", true);
}
catch (\MVQN\Localization\Exceptions\TranslatorException $e)
{
    Log::http("No dictionary could be found!", 500);
}





// Create Slim Framework Application, given the provided settings.
$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true,
        "addContentLengthHeader" => false,
    ],
]);

// Get a reference to the DI Container included with the Slim Framework.
$container = $app->getContainer();


// Configure Twig Renderer
$container['views'] = function (Container $container)
{
    $view = new \Slim\Views\Twig(__DIR__ . "/views/", [
        //'cache' => 'path/to/cache'
        "debug" => true,
    ]);

    // Instantiate and add Slim specific extension
    $router = $container->get("router");
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
    $view->addExtension(new Twig_Extension_Debug());

    //$twig = $view->getEnvironment();

    return $view;
};

// Configure 404 Page
$container['notFoundHandler'] = function (Container $container)
{
    return function(Request $request, Response $response) use ($container): Response
    {
        return $container->views->render($response,"404.html");
    };
};


// Configure MonoLog
$container['logger'] = function (\Slim\Container $container)
{
    $logger = new Monolog\Logger("template-plugin");
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler(
        PHP_SAPI === "cli-server" ? "php://stdout" : __DIR__ . "/logs/app.log",
        \Monolog\Logger::DEBUG
    ));
    return $logger;
};



// Applied in Ascending order, bottom up!
//$app->add(new \UCRM\Routing\Middleware\PluginAuthentication());
$app->add(new \UCRM\Routing\Middleware\QueryStringRouter());

