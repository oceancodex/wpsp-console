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
			if (class_exists('\WPSPCORE\Database\Eloquent')) {
				$this->eloquent->dropAllDatabaseTables();
				$output->writeln('<fg=green>Fresh database tables successfully!</>');
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
//		$seed = $input->getOption('seed');
//		if ($seed) {
//			try {
//				$namespace      = $this->funcs->_getRootNamespace();
//				$databaseSeeder = $namespace . '\\database\\seeders\\DatabaseSeeder';
//				(new $databaseSeeder($this->mainPath, $this->rootNamespace, $this->prefixEnv, [
//					'funcs'              => $this->funcs,
//					'environment'        => $this->environment ?? null
//				]))->run();
//			}
//			catch (\Throwable $e) {
//				$output->writeln('<fg=red>' . $e->getMessage() . '  </>');
//			}
//		}

		return Command::SUCCESS;
	}

}