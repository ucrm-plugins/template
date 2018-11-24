<?php
declare(strict_types=1);

namespace UCRM\Routing\Middleware;

use MVQN\Common\Strings;
use Slim\Container;
use UCRM\Common\Log;
use UCRM\Common\Plugin;
use UCRM\REST\Endpoints\Client;
use UCRM\REST\Endpoints\User;
use UCRM\Sessions\Session;
use UCRM\Sessions\SessionUser;

class PluginAuthentication
{

    /**
     * Example middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @param  callable $next Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \UCRM\Common\Exceptions\PluginNotInitializedException
     */
    public function __invoke($request, $response, $next)
    {
        // IF this Plugin is in development mode, THEN skip this Middleware!
        if(file_exists(Plugin::getRootPath()."/../.env"))
            return $next($request, $response);

        // IF a Session is not already started, THEN start one!
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        // Get the currently authenticated User, while also capturing the actual '/current-user' response!
        $user = Session::getCurrentUser();

        // Display an error if no user is authenticated!
        if(!$user)
            Log::http("No User is currently Authenticated!", 401);

        // Display an error if the authenticated user is NOT an Admin!
        if($user->getUserGroup() !== "Admin Group")
            Log::http("Currently Authenticated User is not an Admin!", 401);

        return $next($request->withAttribute("user", $user), $response);
    }
}