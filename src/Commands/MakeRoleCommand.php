<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeRoleCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:role')
			->setDescription('Create a new role.                        | Eg: bin/wpsp make:role custom_role')
			->setHelp('This command allows you to create a role...')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the role.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the role: ');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$output->writeln('Missing name for the role. Please try again.');
				return Command::INVALID;
			}
		}

		// Define variables.
		$nameSlugify = Str::slug($name, '_');

		// Check exist.
		$exist = FileSystem::exists($this->mainPath . '/app/Extras/Components/Roles/' . $nameSlugify . '.php');
		if ($exist) {
			$output->writeln('[ERROR] Role: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		// Create class file.
		$content = FileSystem::get(__DIR__ . '/../Stubs/Roles/role.stub');
		$content = str_replace('{{ className }}', $nameSlugify, $content);
		$content = str_replace('{{ name }}', $name, $content);
		$content = str_replace('{{ name_slugify }}', $nameSlugify, $content);
		$content = $this->replaceNamespaces($content);
		FileSystem::put($this->mainPath . '/app/Extras/Components/Roles/' . $nameSlugify . '.php', $content);

		// Prepare new line for find function.
		$func = FileSystem::get(__DIR__ . '/../Funcs/Roles/role.func');
		$func = str_replace('{{ name }}', $name, $func);
		$func = str_replace('{{ name_slugify }}', $nameSlugify, $func);

		// Prepare new line for use class.
		$use = FileSystem::get(__DIR__ . '/../Uses/Roles/role.use');
		$use = str_replace('{{ name }}', $name, $use);
		$use = str_replace('{{ name_slugify }}', $nameSlugify, $use);
		$use = $this->replaceNamespaces($use);

		// Add class to route.
		$this->addClassToRoute('Roles', 'roles', $func, $use);

		// Output message.
		$this->writeln($output, '<green>Created new role: "' . $name . '"</green>');

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