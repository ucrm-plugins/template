<?php
declare(strict_types=1);

namespace UCRM\Routing\Middleware;

use MVQN\Common\Strings;
use Slim\Container;

class QueryStringRouter
{
    /*
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    */

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

        // IF no query parameters were provided OR no path parameter was specified OR the root was specified...
        if ($params === [] || !Strings::startsWith(array_keys($params)[0], "/") || array_keys($params)[0] === "/")
            // THEN set the path to the default 'index.php' file!
            $params = array_merge([ "/index_php" => "" ], $params);

        // Get the path from the first query parameter's key, as the value should be empty.
        $path = array_keys($params)[0];

        // Remove the path from the list of query parameters, so that only the actual query parameters remain.
        array_shift($params);

        preg_match('/(_|.+?)(_[^_]*$|$)/', $path, $matches);

        $file = $matches[1];
        $ext = str_replace("_", "", $matches[2]);

        var_dump($file);
        var_dump($ext);


        // -------------------------------------------------------------------------------------------------------------
        // QUERY STRING
        // -------------------------------------------------------------------------------------------------------------

        $query = [];

        foreach($params as $name => $value)
            $query[] = "$name=$value";

        $query = implode("&", $query);



        $uri = $request->getUri()
            ->withPath($path)
            ->withQuery($query);

        $request = $request
            ->withUri($uri)
            ->withQueryParams($params);

        // Be sure to clear the PATH from the GET parameters, so as to not confuse the end-user!
        if(isset($_GET) && array_key_exists($path, $_GET))
            unset($_GET[$path]);

        // Fix-Up the REQUEST_URI...
        if(isset($_SERVER) && array_key_exists("REQUEST_URI", $_SERVER) &&
            Strings::contains($_SERVER["REQUEST_URI"], "/public.php"))
        {
            $_SERVER["REQUEST_URI"] = str_replace("/public.php", "$file.$ext", $_SERVER["REQUEST_URI"]);
        }

        // Fix-Up the QUERY_STRING...
        if(isset($_SERVER) && array_key_exists("QUERY_STRING", $_SERVER) &&
            Strings::startsWith($_SERVER["QUERY_STRING"], "$file.$ext"))
        {
            $_SERVER["QUERY_STRING"] = str_replace("$file.$ext", "", $_SERVER["QUERY_STRING"]);

            if(Strings::startsWith($_SERVER["QUERY_STRING"], "&"))
                $_SERVER["QUERY_STRING"] = ltrim($_SERVER["QUERY_STRING"], "&");
        }

        var_dump("/www$file.$ext");

        if($ext === "php" && isset($_SERVER))
        {
            if(array_key_exists("SCRIPT_NAME", $_SERVER))
                $_SERVER["SCRIPT_NAME"] = "$file.$ext";

            if(array_key_exists("SCRIPT_FILENAME", $_SERVER))
            {
                // Fix-Up Host Path with equivalent Docker Container Path for realpath() to succeed...
                if(Strings::startsWith($_SERVER["SCRIPT_FILENAME"], "/usr/src/ucrm/web/_plugins/"))
                    $_SERVER["SCRIPT_FILENAME"] = str_replace("/usr/src/ucrm/web/_plugins/",
                        "/data/ucrm/data/plugins/", $_SERVER["SCRIPT_FILENAME"]);

                $_SERVER["SCRIPT_FILENAME"] = realpath(str_replace("public.php", "www$file.$ext", $_SERVER["SCRIPT_FILENAME"]));
            }

            if(array_key_exists("PHP_SELF", $_SERVER))
                $_SERVER["PHP_SELF"] = "$file.$ext";

            if(array_key_exists("DOCUMENT_URI", $_SERVER))
                $_SERVER["DOCUMENT_URI"] = str_replace("/public.php", "/www$file.$ext", $_SERVER["DOCUMENT_URI"]);
        }

        $response = $next($request, $response);

        return $response;
    }
}