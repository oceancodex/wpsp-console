<?php

namespace WPSPCORE\Console\Commands;

use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WPSPCORE\Console\Traits\CommandsTrait;

class MakeEventCommand extends Command {

	use CommandsTrait;

	protected function configure() {
		$this
			->setName('make:event')
			->setDescription('Create a new Event class.                 | Eg: bin/wpsp make:event SettingsUpdated')
			->addArgument('name', InputArgument::OPTIONAL, 'The class name of the event.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$name = $input->getArgument('name');

		$helper = $this->getHelper('question');
		if (!$name) {
			$q    = new Question('Please enter the name of the event: ');
			$name = $helper->ask($input, $output, $q);
			if (empty($name)) {
				$output->writeln('Missing name for the event. Please try again.');
				return Command::INVALID;
			}
		}

		$this->validateClassName($output, $name);

		$path = $this->mainPath . '/app/Events/' . $name . '.php';
		if (FileSystem::exists($path)) {
			$output->writeln('[ERROR] Event: "' . $name . '" already exists! Please try again.');
			return Command::FAILURE;
		}

		$stub = '';
		$stub = str_replace('{{ rootNamespace }}', $this->rootNamespace, $stub);
		$stub = str_replace('{{ className }}', $name, $stub);

		FileSystem::put($path, $stub);
		$output->writeln('Created new event: "' . $name . '"');

		return Command::SUCCESS;
	}

}