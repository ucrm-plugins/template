<?php
declare(strict_types=1);

namespace App\Middleware;

use MVQN\Common\Strings;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;
use Twig\Loader\FilesystemLoader;

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

    protected $container;

    /** @var array A collection of paths to search for files when routing. */
    protected $paths;

    // =================================================================================================================
    // CONSTRUCTOR/DESTRUCTOR
    // =================================================================================================================

    /**
     * QueryStringRouter constructor.
     *
     * @param ContainerInterface $container
     * @param array $paths
     */
    public function __construct(ContainerInterface $container, array $paths)
    {
        $this->container = $container;
        $this->paths = $paths;
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
                if (realpath($path . ".$extension") && !is_dir($path . ".$extension"))
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
     * @return QueryStringRoute Returns the route determined by the route parsing.
     */
    protected function parseRoute(): ?QueryStringRoute
    {
        $queryString = $_SERVER["QUERY_STRING"];


        $query = [];

        foreach(explode("&", $queryString) as $set)
        {
            $parts = explode("=", $set);

            if(Strings::startsWith($parts[0], "/") && (count($parts) === 1 || $parts[1] === null))
            {
                $query["route"] = $parts[0];
            }
            else if($parts[0] === "route" && (count($parts) === 1 || $parts[1] === null))
            {
                $query["route"] = $parts[0];
            }
            else if($parts[0] === "r" && (count($parts) === 1 || $parts[1] === null))
            {
                $query["route"] = $parts[0];
            }
            else
            {
                $query[$parts[0]] = count($parts) === 2 ? $parts[1] : null;
            }
        }

        if(!array_key_exists("route", $query))
            $query["route"] = "/index";

        var_dump($query);

        /*
        // IF no query parameters were provided OR no routing parameter was specified...
        if ($params === [] || !Strings::startsWith(array_keys($params)[0], "/"))
            // THEN prepend/merge the root to the parameters for later use!
            $params = array_merge([ "/index" => "" ], $params);

        // Get the route from the first query string key.
        $route = array_keys($params)[0];

        // Remove the route from the list of query parameters, so that only the actual query parameters remain.
        array_shift($params);
        */


        // ---------------------------------------------------------------------------------------------------------
        // ROUTE EXAMINATION
        // ---------------------------------------------------------------------------------------------------------

        $route = $query["route"];

        // Explode the route parts.
        $parts = explode("/", $route);

        // Build the directory and filename from the route parts.
        $directory = implode("/", array_slice($parts, 0, -1))."/";
        $filename = $parts[count($parts) - 1] ?: "index";

        var_dump($directory);
        var_dump($filename);

        if(Strings::startsWith($filename, "."))
        {
            $extension = "";
        }
        else if(Strings::contains($filename, "."))
        {
            $parts = explode(".", $filename);

            $extension = array_pop($parts);
            $filename = implode(".", $parts);
        }
        else
        {
            // Start with an empty extension, we will be handling that below.
            $extension = null;
        }

        $params = $query;
        if(array_key_exists("route", $params))
            unset($params["route"]);

        var_dump($params);

        return new QueryStringRoute($directory, $filename, $extension, $params);


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
        // -------------------------------------------------------------------------------------------------------------
        // QUERY PARAMETERS
        // -------------------------------------------------------------------------------------------------------------
        $route = $this->parseRoute();

        echo "<pre>";
        echo $route;
        echo "</pre>";

        $uri = $request->getUri()
            ->withPath($route->getUrl())
            ->withQuery($route->getQueryString());

        $request = $request
            ->withUri($uri)
            ->withQueryParams($route->getQuery());

        $_GET = $route->getQuery();

        var_dump($_GET);

        $response = $next($request, $response);


        return $response;
    }
}