<?php

namespace WPSPCORE\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\Console\Traits\CommandsTrait;
use WPSPCORE\Database\Eloquent;

class MigrationMigrateCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('migration:migrate')
			->setDescription('Migration migrate.')
			->setHelp('This command allows you to run migration migrate.')
			->addOption('fresh', 'fresh', InputOption::VALUE_NONE, 'Fresh database or not?.')
			->addOption('seed', 'seed', InputOption::VALUE_NONE, 'Run seeders or not?.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		// Fresh database.
		$fresh = $input->getOption('fresh');
		if ($fresh) {
			require_once $this->funcs->_getSitePath() . '/wp-load.php';
			if (class_exists('\WPSPCORE\Database\Eloquent')) {
				(new Eloquent(
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv()
				))->global();
				$this->funcs->_getAppEloquent()->dropAllDatabaseTables();
			}
		}

		// Migrate.
		exec('php bin/migrations migrate -n', $execOutput, $exitCode);

		foreach ($execOutput as $execOutputKey => $execOutputItem) {
			if (empty($execOutputItem)) {
				unset($execOutput[$execOutputKey]);
			}
		}

		foreach ($execOutput as $execOutputItem) {
			$execOutputItem = trim($execOutputItem);
			if (preg_match('/\[OK|\[Success/iu', $execOutputItem)) {
				$output->writeln('<fg=green>' . $execOutputItem . '  </>');
			}
			else {
				$output->writeln($execOutputItem);
			}
		}

		// Seeders.
		$seed = $input->getOption('seed');
		if ($seed) {
			try {
				$namespace      = $this->funcs->_getRootNamespace();
				$databaseSeeder = $namespace . '\\database\\seeders\\DatabaseSeeder';
				(new $databaseSeeder($output))->run();
			}
			catch (\Exception $e) {
				$output->writeln('<fg=red>' . $e->getMessage() . '  </>');
			}
		}

		// Output message.
//		$output->writeln('Migrated.');

		// this method must return an integer number with the "exit status code"
		// of the command. You can also use these constants to make code more readable

		// return this if there was no problem running the command
		// (it's equivalent to returning int(0))
		return Command::SUCCESS;

		// or return this if some error happened during the execution
		// (it's equivalent to returning int(1))
		// return Command::FAILURE;

		// or return this to indicate incorrect command usage; e.g. invalid options
		// or missing arguments (it's equivalent to returning int(2))
		// return Command::INVALID
	}

}