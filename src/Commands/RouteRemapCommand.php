<?php

namespace WPSPCORE\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\Console\Traits\CommandsTrait;
use WPSPCORE\FileSystem\FileSystem;

class RouteRemapCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('route:remap')
			->setDescription('Remap routes.                             | Eg: bin/wpsp route:remap')
			->setHelp('This command is used to remap routes...')
			->addOption('ide', null, InputOption::VALUE_OPTIONAL, 'Choose IDE to auto-reload. Supported: phpstorm');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		$wpConfig = $this->funcs->_getWPConfig();
		$host     = $wpConfig['DB_HOST'] ?? $this->funcs->_env('DB_HOST', true) ?? null;
		$user     = $wpConfig['DB_USER'] ?? $this->funcs->_env('WPSP_DB_USERNAME', true) ?? null;
		$password = $wpConfig['DB_PASSWORD'] ?? $this->funcs->_env('DB_PASSWORD', true) ?? null;

		if ($host) {
			try {
				$test = @mysqli_connect($host, $user, $password);
				if (!$test) {
					$this->writeln($output, '<red>Unable to connect to database, please check your wp-config.php or .env to make sure the database connection information is declared correctly.</red>');
					return Command::INVALID;
				}
			}
			catch (\Throwable $e) {
				$this->writeln($output, '<red>Database server not found. Please make sure your database server is running and the database connection information in wp-config.php or .env is correct.</red>');
				return Command::INVALID;
			}
		}
		else {
			$this->writeln($output, '<red>WP Config not found or database connection information in .env file is not configured.</red>');
			return Command::INVALID;
		}

		require $this->funcs->_getSitePath('/wp-config.php');

		$routeMap = $this->mapRoutes->mapIdea ?? [];

		if (empty($routeMap)) {
			$output->writeln('<error>No routes found!</error>');
			$output->writeln('<info>You must make sure that your Database Server is running.</info>');
			return Command::INVALID;
		}

		$pluginDirName = $this->funcs->_getPluginDirName();

		$prepareMap           = [];
		$prepareMap['scope']  = $pluginDirName;
		$prepareMap['routes'] = $routeMap;
		$prepareMap           = json_encode($prepareMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
//		$prepareMap = json_encode($prepareMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		// Write file.
		FileSystem::put($this->mainPath . '/.wpsp-routes.json', $prepareMap);

		// Handle IDE auto-reload
		$ide = strtolower($input->getOption('ide') ?? null);
		if ($ide === 'phpstorm') {
			$this->writeln($output, '[IDE] Auto reload triggered for PHPStorm');
			$psScript = $this->funcs->_getMainPath('/bin/phpstorm-auto-reload.ps1');
			exec('pwsh ' . escapeshellarg($psScript));
		}

		// Output message.
		$this->writeln($output, '<green>Remap routes successfully!</green>');

		// this method must return an integer number with the "exit status code"
		// of the command. You can also use these constants to make code more readable

		// return this if there was no problem running the command
		// (it's equivalent to returning int(0))
		return Command::SUCCESS;

		// or return this if some error happened during the execution
		// (it's equivalent to returning int(1))
//		 return Command::FAILURE;

		// or return this to indicate incorrect command usage; e.g. invalid options
		// or missing arguments (it's equivalent to returning int(2))
		// return Command::INVALID
	}

}