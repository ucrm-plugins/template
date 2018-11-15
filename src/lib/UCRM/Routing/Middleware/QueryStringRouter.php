<?php
declare(strict_types=1);

namespace UCRM\Routing\Middleware;

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
        $route = array_keys($params)[0];

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
        $discovered = false;

        // Loop through each path provided...
        foreach($this->paths as $templatePath)
        {
            // ---------------------------------------------------------------------------------------------------------
            // EXTENSION CHECK: EXACT!
            // ---------------------------------------------------------------------------------------------------------

            $check = $this->autoExtension($templatePath . $directory . $filename);

            if($check !== null)
            {
                $extension = $check;
                $discovered = true;
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
                    var_dump($directoryTemp);

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
                            $discovered = true;
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




        //if(!$discovered)
        {
            //return new QueryStringRoute("/", "404.html", "twig", []);
        }





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
        $params = $request->getQueryParams();

        // -------------------------------------------------------------------------------------------------------------
        // QUERY PARAMETERS
        // -------------------------------------------------------------------------------------------------------------




        $route = $this->parseRoute($params);

        echo $route;



        $uri = $request->getUri()
            ->withPath($route->getUrl())
            ->withQuery($route->getQueryString());

        $request = $request
            ->withUri($uri)
            ->withQueryParams($params);

        $queryUrl = $route->getFilename() === "index" ? $route->getDirectory() : $route->getUrl();

        // Be sure to clear the PATH from the GET parameters, so as to not confuse the end-user!
        if(isset($_GET) && array_key_exists($queryUrl, $_GET))
            unset($_GET[$queryUrl]);

        $response = $next($request, $response);

        return $response;
    }
}