<?php
declare(strict_types=1);

namespace UCRM\Routing\Controllers;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

/**
 * Class LoginController
 *
 * @package UCRM\Routing\Controllers
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 *
 */
class AuthenticationController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Twig
     */
    protected $renderer;

    /**
     * AuthenticationController constructor.
     *
     * Dependency Injection passes the container instance.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->renderer = $container->get("renderer");
    }

    public function login(Request $request, Response $response, array $args): Response
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        switch($request->getMethod())
        {
            case "GET":

                if(!isset($_SESSION["authenticated"]) || !$_SESSION["authenticated"])
                    return $response->write(file_get_contents(__DIR__."/../Templates/Login.html"));

                break;

            case "POST":

                $username = $_POST["username"];
                $password = $_POST["password"];

                // TODO: Perform authentication!!!
                $_SESSION["authenticated"] = true;

                header("Location: public.php");
                exit();

                break;

            default:
                return $this->container["notAllowedHandler"]($request, $response);
        }

        return $response;
    }





    public function logout($request, $response, $args)
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        if(isset($_SESSION["authenticated"]) && $_SESSION["authenticated"])
            unset($_SESSION["authenticated"]);

        header("Location: public.php");
        exit();
    }
}