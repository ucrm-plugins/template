<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';

use UCRM\Common\Plugin;

/**
 * composer.php
 *
 * A shared script that handles composer script execution from the command line.
 *
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */

if($argc === 1)
{
    $usage = "\n".
        "Usage:\n".
        "    composer.php [create|bundle]\n";

    die($usage);
}

$pluginPath = realpath(__DIR__."/../../");
$pluginName = baseName($pluginPath);

if(file_exists(__DIR__."/../../.env"))
    (new \Dotenv\Dotenv(__DIR__."/../../"))->load();

Plugin::initialize(__DIR__."/../");

// Handle the different command line arguments...
switch ($argv[1])
{
    // Perform initialization of the Plugin libraries and create the auto-generated Settings class.
    case "create":
        Plugin::createSettings("App", "Settings", __DIR__."/../");
        break;

    // Bundle the 'zip/' directory into a package ready for Plugin installation on the UCRM server.
    case "bundle":
        Plugin::bundle(__DIR__."/../", $pluginName, ".zipignore", __DIR__."/../../");
        break;

    case "sync":
        $host = getenv("UCRM_SFTP_HOSTNAME");
        $port = 22;
        $user = getenv("UCRM_SFTP_USERNAME");
        $pass = getenv("UCRM_SFTP_PASSWORD");

        $sftp = new \MVQN\SFTP\SftpClient($host, $port);
        $sftp->login($user, $pass);
        $sftp->setRemoteBasePath("/home/ucrm/data/ucrm/ucrm/data/plugins/$pluginName/");
        $sftp->setLocalBasePath(__DIR__."/../");

        foreach([ "/ucrm.json", "/data/config.json" ] as $file)
            $data = $sftp->download($file);

        break;

    // TODO: More commands to come!

    default:
        break;
}
