<?php declare(strict_types = 1);

namespace App;

use Contributte\Bootstrap\ExtraConfigurator;

class Bootstrap
{

	public static function boot(): ExtraConfigurator
	{
		$configurator = new ExtraConfigurator();
		$configurator->setTempDirectory(__DIR__ . '/../var/temp');

		// Disable default extensions
		unset($configurator->defaultExtensions['security']);

		// Enable tracy and configure it
		$configurator->enableTracy(__DIR__ . '/../var/log');

		// Provide some parameters
		$configurator->addStaticParameters([
			'rootDir' => realpath(__DIR__ . '/..'),
			'appDir' => __DIR__,
			'wwwDir' => realpath(__DIR__ . '/../www'),
		]);

		$configurator->addConfig(__DIR__ . '/../config/config.neon');
		$configurator->addConfig(__DIR__ . '/../config/local.neon');

        if ($_SERVER['HTTP_HOST'] && str_contains($_SERVER['HTTP_HOST'], '.localhost'))
        {
            $configurator->setDebugMode(true);
        }

		return $configurator;
	}

}
