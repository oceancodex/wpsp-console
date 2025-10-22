<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakePostTypeColumnCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:post-type-column')
			->setDescription('Create a new post type column.                  | Eg: bin/wpsp make:post-type-column my_custom_column')
			->setHelp('This command allows you to create a custom column for post type list table.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the post type column.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the post type column: ');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$output->writeln('Missing name for the post type column. Please try again.');
				return Command::INVALID;
			}
		}

		$this->validateClassName($output, $name);

		$path = $this->mainPath . '/app/Extras/Components/PostTypeColumns/' . $name . '.php';
		if (FileSystem::exists($path)) {
			$output->writeln('[ERROR] Post type column: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		$stub = FileSystem::get(__DIR__ . '/../Stubs/PostTypeColumns/post_type_column.stub');
		$stub = str_replace('{{ className }}', $name, $stub);
		$stub = $this->replaceNamespaces($stub);
		FileSystem::put($path, $stub);

		$output->writeln('Created new post type column: "' . $name . '"');

		return Command::SUCCESS;
	}

}