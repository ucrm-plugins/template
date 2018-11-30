<?php /** @noinspection SpellCheckingInspection */
declare(strict_types=1);

namespace App;

use UCRM\Common\SettingsBase;

/**
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 *
 * @method static bool|null getDevelopment()
 * @method static bool|null getVerboseLogging()
 */
final class Settings extends SettingsBase
{
	/** @const string The name of this Project, based on the root folder name. */
	public const PROJECT_NAME = 'template';

	/** @const string The absolute path to this Project's root folder. */
	public const PROJECT_ROOT_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\ucrm-plugins\template';

	/** @const string The name of this Project, based on the root folder name. */
	public const PLUGIN_NAME = 'template';

	/** @const string The absolute path to the root path of this project. */
	public const PLUGIN_ROOT_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\ucrm-plugins\template\src';

	/** @const string The absolute path to the data path of this project. */
	public const PLUGIN_DATA_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\ucrm-plugins\template\src\data';

	/** @const string The absolute path to the source path of this project. */
	public const PLUGIN_SOURCE_PATH = 'C:\Users\rspaeth\Documents\PhpStorm\Projects\ucrm-plugins\template\src\src';

	/** @const string The publicly accessible URL of this UCRM, null if not configured in UCRM. */
	public const UCRM_PUBLIC_URL = 'https://ucrm.dev.mvqn.net/';

	/** @const string The locally accessible URL of this UCRM, null if not configured in UCRM. */
	public const UCRM_LOCAL_URL = 'https://localhost/';

	/** @const string The publicly accessible URL assigned to this Plugin by the UCRM. */
	public const PLUGIN_PUBLIC_URL = 'https://ucrm.dev.mvqn.net/_plugins/template/public.php';

	/** @const string An automatically generated UCRM API 'App Key' with read/write access. */
	public const PLUGIN_APP_KEY = 'JS/+cPjDrMkueyAArIoUxXxQiH6rOHOK2ML3HR299mx0w6EWqd021yfGfzVSD0bg';

	/**
	 * Development?
	 * @var bool|null If enabled, will override the environment by setting it to Development.
	 */
	protected static $development;

	/**
	 * Verbose Logging?
	 * @var bool|null If enabled, will append additional "verbose" information to the logs.
	 */
	protected static $verboseLogging;
}
