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

	protected function execute(InputInterface $input, OutputInterface $output, $fresh = false, $seed = false): int {

		// Fresh database.
		$fresh = $fresh || $input->getOption('fresh');
		if ($fresh) {
			require_once $this->funcs->_getSitePath() . '/wp-load.php';
			if (class_exists('\WPSPCORE\Database\Eloquent')) {
				(new Eloquent(
					$this->funcs->_getMainPath(),
					$this->funcs->_getRootNamespace(),
					$this->funcs->_getPrefixEnv(),
					[
						'environment'        => $this->environment ?? null,
						'validation'         => null,

						'prepare_funcs'      => true,
						'prepare_request'    => false,

						'unset_funcs'        => false,
						'unset_request'      => true,
						'unset_validation'   => true,
						'unset_environment'  => true,

						'unset_extra_params' => true,
					]
				))->global();
				$this->funcs->_getAppEloquent()->dropAllDatabaseTables();
			}
		}
		else {
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
			$seed = $seed || $input->getOption('seed');
			if ($seed) {
				try {
					$namespace      = $this->funcs->_getRootNamespace();
					$databaseSeeder = $namespace . '\\database\\seeders\\DatabaseSeeder';
					(new $databaseSeeder($output))->run();
				}
				catch (\Throwable $e) {
					$output->writeln('<fg=red>' . $e->getMessage() . '  </>');
				}
			}
			return Command::SUCCESS;
		}

		return Command::FAILURE;
	}

}