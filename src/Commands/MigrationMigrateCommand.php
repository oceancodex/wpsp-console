<?php

namespace WPSPCORE\Console\Commands;

use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WPSPCORE\Console\Commands\Helpers\FilteredOutput;
use WPSPCORE\Console\Traits\CommandsTrait;

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
				$output->writeln('<fg=yellow>Dropping all database tables...</>');
				$dropDatabaseTables = $this->eloquent->dropAllDatabaseTables();
				$output->writeln('');
				$output->writeln('<fg=green>> All database tables have been dropped successfully!</>');
			}
		}

		// Migrate.
		$this->runDoctrineMigrations($output);

		// Seeders.
		$seed = $input->getOption('seed');
		if ($seed) {
			try {
				$output->writeln('');
				$output->writeln('<fg=yellow>Running seeders...</>');
				$output->writeln('');

				$namespace      = $this->funcs->_getRootNamespace();
				$databaseSeeder = $namespace . '\\database\\seeders\\DatabaseSeeder';
				(new $databaseSeeder($this->mainPath, $this->rootNamespace, $this->prefixEnv, [
					'funcs'       => $this->funcs,
					'environment' => $this->environment,
					'output'      => $output,
				]))->run();

				$output->writeln('');
				$output->writeln('<fg=green>> Seeders completed successfully!</>');
			}
			catch (\Throwable $e) {
				$output->writeln('');
				$output->writeln('<fg=red>Seeder error: ' . $e->getMessage() . '</>');
			}
		}

		return Command::SUCCESS;
	}

	protected function runDoctrineMigrations(OutputInterface $output) {
		$output->writeln('');
		$output->write('<fg=yellow>Running Doctrine migrations directly...</>');

		// 1️⃣ Lấy DependencyFactory từ file cấu hình
		$configFile        = $this->funcs->_getMainPath('/cli-config.php');
		$dependencyFactory = require $configFile;

		// 2️⃣ Khởi tạo command
		$migrate = new MigrateCommand($dependencyFactory);

		// ✅ Giả lập nhập "yes"
		$input       = new ArrayInput([]);
		$inputStream = fopen('php://memory', 'r+', false);
		fwrite($inputStream, "yes\n");
		rewind($inputStream);

		// Gắn helperset để command có thể đọc input
		$helperSet = $migrate->getHelperSet();
		if (!$helperSet) {
			$helperSet = new HelperSet();
			$migrate->setHelperSet($helperSet);
		}

		// Trick để ép Symfony dùng stream này làm input
		if (method_exists($input, 'setStream')) {
			$input->setStream($inputStream);
		}
		else {
			// Symfony < 5: dùng reflection
			$ref = new \ReflectionObject($input);
			if ($ref->hasProperty('stream')) {
				$prop = $ref->getProperty('stream');
				$prop->setAccessible(true);
				$prop->setValue($input, $inputStream);
			}
		}

		// 🔥 Bọc output bằng FilteredOutput để ẩn cảnh báo & câu hỏi
		$filteredOutput = new FilteredOutput($output);

		$exitCode = $migrate->run($input, $filteredOutput);

		fclose($inputStream);

		if ($exitCode === 0) {
			$output->writeln('<fg=green>> Migrations completed successfully!</>');
		}
		else {
			$output->writeln('<fg=red>Migrations exited with code ' . $exitCode . '</>');
		}
	}

}