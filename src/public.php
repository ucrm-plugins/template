<?php
declare(strict_types=1);
require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/bootstrap.php";

use UCRM\Sessions\SessionUser;

use Slim\Http\Request;
use Slim\Http\Response;

use UCRM\Routing\Middleware\PluginAuthentication;

/**
 * Use an immediately invoked function here, to avoid global namespace pollution...
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 *
 */
(function() use ($app, $container)
{



    $app->get("/{file:.+}.{ext:jpg|png|pdf|txt|css|js}",
        function (Request $request, Response $response, array $args) use ($container)
        {
            $file = $args["file"];
            $ext = $args["ext"];

            // Match the Content-Type given the following extension...
            switch ($ext)
            {
                case "jpg":         $contentType = "image/jpg";                 break;
                case "png":         $contentType = "image/png";                 break;
                case "pdf":         $contentType = "application/pdf";           break;
                case "txt":         $contentType = "text/plain";                break;
                case "css":         $contentType = "text/css";                  break;
                case "js" :         $contentType = "text/javascript";           break;
                default   :         $contentType = "application/octet-stream";  break; // Excluded by URL RegEx!
            }

            $path = realpath(__DIR__ . "/www/" . "$file.$ext");

            if(!$path)
                return $response->withStatus(404, "Asset not found!");

            // Set the response Content-Type header and write the contents of the file to the response body.
            $response = $response
                ->withHeader("Content-Type", $contentType)
                ->write(file_get_contents($path));

            // Then return the response!
            return $response;
        }
    );

    $app->get("/{file:.+}.{ext:htm|html|twig}",
        function (Request $request, Response $response, array $args) use ($container)
        {
            $file = $args["file"] ?? "index";
            $ext = $args["ext"] ?? "html";

            $pathAsset = __DIR__."/www/$file.$ext";
            $pathTemplate = __DIR__."/views/$file.$ext";

            //var_dump($pathAsset);
            //var_dump($pathTemplate);


            if ((file_exists($pathAsset) && !is_dir($pathAsset)) ||
                (file_exists($pathTemplate) && !is_dir($pathTemplate)))
            {
                //var_dump("*");
                return $this->twig->render($response, "$file.$ext");
            }
            elseif(file_exists($pathTemplate.".twig") && !is_dir($pathTemplate.".twig"))
            {
                //var_dump("**");
                return $this->twig->render($response, "$file.$ext.twig");
            }
            else
                return $container->get("notFoundHandler")($request, $response);
        }
    )->add(new PluginAuthentication());


    $app->any("/[{file:.+}.{ext:php}]",
        function (Request $request, Response $response, array $args) use ($container)
        {
            $file = $args["file"] ?? "index";
            $ext = $args["ext"] ?? "php";

            $path = __DIR__."/www/$file.$ext";

            if(!file_exists($path))
                return $container->get("notFoundHandler")($request, $response);

            /** @noinspection PhpUnusedLocalVariableInspection */
            /** @var SessionUser $user */
            $user = $request->getAttribute("user");

            // Pass execution to the specified PHP file.
            include $path;

            // In this case, 'index.php' should handle everything and since there is no Response to return, die()!
            die();

        }
    )->add(new PluginAuthentication());

    // Run the Slim Framework Application!
    $app->run();

})();

