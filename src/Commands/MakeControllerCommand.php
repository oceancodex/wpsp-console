<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Traits\CommandsTrait;

class MakeControllerCommand extends Command {

	use CommandsTrait;

	protected function configure(): void {
		$this
			->setName('make:controller')
			->setDescription('Create a new controller.                  | Eg: bin/wpsp make:controller MyController')
			->setHelp('This command allows you to create a controller.')
			->addArgument('name', InputArgument::OPTIONAL, 'The name of the controller.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$nameQuestion = new Question('Please enter the name of the controller: ');
			$name         = $helper->ask($input, $output, $nameQuestion);

			if (empty($name)) {
				$output->writeln('Missing name for the controller. Please try again.');
				return Command::INVALID;
			}
		}

		// Validate class name.
		$this->validateClassName($output, $name);

		// Create class file.
		$content = FileSystem::get(__DIR__ . '/../Stubs/Controllers/controller.stub');
		$content = str_replace('{{ className }}', $name, $content);
		$content = $this->replaceNamespaces($content);
		FileSystem::put($this->mainPath . '/app/Http/Controllers/' . $name . '.php', $content);

		// Output message.
		$output->writeln('Created new controller: "' . $name . '"');

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