<?php
declare(strict_types=1);

namespace App\Middleware;

use MVQN\Common\Strings;
use Psr\Container\ContainerInterface;
use Slim\Route;
use Slim\Router;

/**
 * Class QueryStringRouter
 *
 * @package UCRM\Routing\Middleware
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 *
 */
class QueryStringRouter
{
    // =================================================================================================================
    // CONSTANTS
    // =================================================================================================================

    /**
     * @var array Supported file extensions that can be used for automatic lookup.  Prioritized by the provided order!
     */
    protected const AUTO_EXTENSIONS = [ "php", "html", "twig", "html.twig", "jpg", "png", "pdf", "txt", "css", "js" ];

    // =================================================================================================================
    // PROPERTIES
    // =================================================================================================================

    /** @var ContainerInterface */
    protected $container;

    /** @var Router */
    protected $router;

    /** @var array A collection of paths to search for files when routing. */
    protected $assets;
    protected $templates;

    // =================================================================================================================
    // CONSTRUCTOR/DESTRUCTOR
    // =================================================================================================================

    /**
     * QueryStringRouter constructor.
     *
     * @param ContainerInterface $container
     * @param array $assets
     * @param array $templates
     */
    public function __construct(ContainerInterface $container, array $assets, array $templates)
    {
        $this->container = $container;
        $this->router = $container->router;
        $this->assets = $assets;
        $this->templates = $templates;
    }

    // =================================================================================================================
    // AUTO EXTENSIONS
    // =================================================================================================================

    /**
     * Attempts to determine the correct file extension, when none is provided in the path.
     *
     * @param string $path The path for which to inspect.
     * @param array $extensions An optional array of supported extensions, ordered for detection priority.
     * @return string|null Returns an automatic path, if the file exists OR NULL if no determination could be made!
     */
    protected function autoExtension(string $path, array $extensions = self::AUTO_EXTENSIONS): ?string
    {
        // IF a valid path with extension was provided...
        if(realpath($path))
        {
            // THEN determine the extension part and return it!
            $parts = explode(".", $path);
            $ext = $parts[count($parts) - 1];
            return $ext;
        }
        else
        {
            // OTHERWISE, assume no extension was provided and try suffixing from the list of auto extensions...
            foreach ($extensions as $extension)
            {
                // IF the current path with auto extension exists, THEN return the extension!
                if (realpath($path . ".$extension") && !is_dir($path . ".$extension")) // Handles DIR with '.'
                    return $extension;
            }

            // If all else fails, return NULL!
            return null;
        }
    }


    public static function extractRouteFromQueryString(string &$queryString): string
    {
        var_dump($queryString);
        $parts = explode("&", $queryString);

        $route = "";
        $query = [];

        var_dump($parts);

        foreach($parts as $part)
        {
            if(Strings::startsWith($part, "/"))
                $route = $part;
            else if(Strings::startsWith($part, "route=/"))
                $route = str_replace("route=/", "/", $part);
            else if(Strings::startsWith($part, "r=/"))
                $route = str_replace("r=/", "/", $part);
            else
                $query[] = $part;
        }

        $queryString = implode("&", $query);
        return $route;
    }

    public static function extractRouteFromQueryArray(array &$queryArray): string
    {
        $route = "";
        $query = [];

        foreach($queryArray as $key => $value)
        {
            if(Strings::startsWith($key, "/") && $value === null)
                $route = $key;
            else if($key === "route")
                $route = $value;
            else if($key === "r")
                $route = $value;
            else
                $query[$key] = $value;
        }

        $queryArray = $query;
        return $route;


    }



    // =================================================================================================================
    // ROUTE PARSING
    // =================================================================================================================

    /**
     * Attempts to generate the correct route from the given path.
     *
     * @param array $params The Query Parameters for which to use when parsing.
     * @return QueryStringRoute Returns the route determined by the route parsing.
     */
    protected function parseRoute(array $params): ?QueryStringRoute
    {
        // IF no query parameters were provided OR no routing parameter was specified...
        if ($params === [] || !Strings::startsWith(array_keys($params)[0], "/"))
            // THEN prepend/merge the root to the parameters for later use!
            $params = array_merge([ "/index" => "" ], $params);

        // Get the route from the first query string key.
        $route = $original = array_keys($params)[0];

        // Remove the route from the list of query parameters, so that only the actual query parameters remain.
        array_shift($params);

        // ---------------------------------------------------------------------------------------------------------
        // ROUTE EXAMINATION
        // ---------------------------------------------------------------------------------------------------------

        // Explode the route parts.
        $parts = explode("/", $route);

        // Build the directory and filename from the route parts.
        $directory = implode("/", array_slice($parts, 0, -1))."/";
        $filename = $parts[count($parts) - 1] ?: "index";

        // Start with an empty extension, we will be handling that below.
        $extension = "";

        // Set a discovery flag.
        $discovered = QueryStringRoute::ROUTE_TYPE_UNKNOWN;

        // Loop through each path provided...
        foreach($this->assets as $assetPath)
        {
            // ---------------------------------------------------------------------------------------------------------
            // EXTENSION CHECK: EXACT!
            // ---------------------------------------------------------------------------------------------------------

            $check = $this->autoExtension($assetPath . $directory . $filename);

            if($check !== null)
            {
                $extension = $check;
                $discovered = QueryStringRoute::ROUTE_TYPE_ASSET;
                break;
            }



            // ---------------------------------------------------------------------------------------------------------
            // EXTENSION CHECK: WITH POSSIBLE '.' CHARACTERS, SINCE THEY ARE PARSED AS '_'
            // ---------------------------------------------------------------------------------------------------------

            // IF the directory OR filename parts of the route contain ANY '_' characters...
            if(Strings::contains($directory, "_") || Strings::contains($filename, "_"))
            {
                // Count the number of '_' characters in the directory part of the route.
                $directoryUnderscores = substr_count($directory, "_");

                // Set a temporary directory for manipulation.
                $directoryTemp = $directory;

                for($i = 0; $i <= $directoryUnderscores; $i++)
                {
                    //var_dump($directoryTemp);

                    $filenameUnderscores = substr_count($filename, "_");

                    $filenameTemp = $filename;

                    for($j = 0; $j <= $filenameUnderscores; $j++)
                    {

                        $filenameTemp = Strings::replaceLast("_", ".", $filenameTemp);

                        $extensionTemp = $this->autoExtension($assetPath . $directoryTemp . $filenameTemp);

                        if($extensionTemp !== null)
                        {
                            $directory = $directoryTemp;
                            $filename = Strings::replaceLast(".$extensionTemp", "", $filenameTemp);
                            $extension = $extensionTemp;
                            $discovered = QueryStringRoute::ROUTE_TYPE_ASSET;
                            break;
                        }
                    }

                    if($discovered)
                        break;

                    if($i !== $directoryUnderscores)
                        $directoryTemp = Strings::replaceLast("_", ".", $directoryTemp);
                }

                if($discovered)
                    break;
            }
        }

        if($discovered)
        {
            $route = new QueryStringRoute($directory, $filename, $extension, $params);
            $route->setOriginal($original);
            $route->setType($discovered);

            return $route;
        }

        // Loop through each path provided...
        foreach($this->templates as $templatePath)
        {
            // ---------------------------------------------------------------------------------------------------------
            // EXTENSION CHECK: EXACT!
            // ---------------------------------------------------------------------------------------------------------

            $check = $this->autoExtension($templatePath . $directory . $filename);

            if($check !== null)
            {
                $extension = $check;
                $discovered = QueryStringRoute::ROUTE_TYPE_TEMPLATE;
                break;
            }



            // ---------------------------------------------------------------------------------------------------------
            // EXTENSION CHECK: WITH POSSIBLE '.' CHARACTERS, SINCE THEY ARE PARSED AS '_'
            // ---------------------------------------------------------------------------------------------------------

            // IF the directory OR filename parts of the route contain ANY '_' characters...
            if(Strings::contains($directory, "_") || Strings::contains($filename, "_"))
            {
                // Count the number of '_' characters in the directory part of the route.
                $directoryUnderscores = substr_count($directory, "_");

                // Set a temporary directory for manipulation.
                $directoryTemp = $directory;

                for($i = 0; $i <= $directoryUnderscores; $i++)
                {
                    //var_dump($directoryTemp);

                    $filenameUnderscores = substr_count($filename, "_");

                    $filenameTemp = $filename;

                    for($j = 0; $j <= $filenameUnderscores; $j++)
                    {

                        $filenameTemp = Strings::replaceLast("_", ".", $filenameTemp);

                        $extensionTemp = $this->autoExtension($templatePath . $directoryTemp . $filenameTemp);

                        if($extensionTemp !== null)
                        {
                            $directory = $directoryTemp;
                            $filename = Strings::replaceLast(".$extensionTemp", "", $filenameTemp);
                            $extension = $extensionTemp;
                            $discovered = QueryStringRoute::ROUTE_TYPE_TEMPLATE;
                            break;
                        }
                    }

                    if($discovered)
                        break;

                    if($i !== $directoryUnderscores)
                        $directoryTemp = Strings::replaceLast("_", ".", $directoryTemp);
                }

                if($discovered)
                    break;
            }
        }

        if($discovered)
        {
            $route = new QueryStringRoute($directory, $filename, $extension, $params);
            $route->setOriginal($original);
            $route->setType($discovered);

            return $route;
        }

        // TODO: Check routes?

        /** @var Route[] $routes */
        $routes = $this->router->getRoutes();

        foreach($routes as $r)
        {
            //echo $r->getPattern()."<br/>";
        }

        $route = new QueryStringRoute($directory, $filename, $extension, $params);
        $route->setOriginal($original);
        $route->setType(QueryStringRoute::ROUTE_TYPE_ROUTE);

        return $route;
    }



    /**
     * Example middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        $params = $request->getQueryParams();

        // -------------------------------------------------------------------------------------------------------------
        // QUERY PARAMETERS
        // -------------------------------------------------------------------------------------------------------------

        $route = $this->parseRoute($params);

        //echo "<pre>$route</pre>";

        $uri = $request->getUri()
            ->withPath($route->getUrl())
            ->withQuery($route->getQueryString());

        $request = $request
            ->withUri($uri)
            ->withQueryParams($route->getQuery())
            ->withAttribute("vRoute", $route);

        // Be sure to clear the PATH from the GET parameters, so as to not confuse the end-user!
        if(isset($_GET) && array_key_exists($route->getOriginal(), $_GET))
            unset($_GET[$route->getOriginal()]);

        if(isset($_SERVER) && array_key_exists("QUERY_STRING", $_SERVER))
            $_SERVER["QUERY_STRING"] = $route->getQueryString();

        //echo "<pre>";
        //var_dump($request);
        //echo "</pre>";

        return $next($request, $response);
    }
}