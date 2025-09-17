<?php

namespace WPSPCORE\Console\Traits;

use WPSPCORE\FileSystem\FileSystem;
use Symfony\Component\Console\Command\Command;
use WPSPCORE\Funcs;

trait CommandsTrait {

	public $mainPath      = null;
	public $rootNamespace = null;
	public $prefixEnv     = null;
	public $funcs         = null;
	public $coreNamespace = 'WPSPCORE';

	public function __construct($name = null, $mainPath = null, $rootNamespace = null, $prefixEnv = null) {
		parent::__construct($name);
		$this->mainPath      = $mainPath;
		$this->rootNamespace = $rootNamespace;
		$this->prefixEnv     = $prefixEnv;
		$this->funcs         = new Funcs($this->mainPath, $this->rootNamespace, $this->prefixEnv);
	}

	public function replaceNamespaces($content) {
		$content = str_replace('{{ rootNamespace }}', $this->rootNamespace, $content);
		return str_replace('{{ coreNamespace }}', $this->coreNamespace, $content);
	}

	public function validateClassName($output, $className = null) {
		if (empty($className) || preg_match('/[^A-Za-z0-9_]/', $className)) {
			$output->writeln('[ERROR] The name: "' . $className . '" is invalid! Please try again.');
			exit(Command::INVALID);
		}
	}

	/*
	 *
	 */

	public function getRouteContent($routeName) {
		return FileSystem::get($this->mainPath . '/routes/'.$routeName.'.php');
	}

	/*
	 *
	 */

	public function saveRouteContent($routeName, $content) {
		FileSystem::put($this->mainPath . '/routes/'.$routeName.'.php', $content);
	}

	/*
	 *
	 */

	public function addClassToRoute($routeName, $findFunction, $newLineForFindFunction, $newLineUseClass) {
		$routeContent = $this->getRouteContent($routeName);
		$routeContent = preg_replace('/public function ' . $findFunction . '([\S\s]*?)\{/iu', 'public function ' . $findFunction . "$1{\n" . $newLineForFindFunction, $routeContent);
		if (!strpos($routeContent, $newLineUseClass) !== false) {
			$routeContent = preg_replace('/(\n\s*)class '.$routeName.' extends/iu', "\n" . $newLineUseClass . '$1class '.$routeName.' extends', $routeContent);
		}
		$this->saveRouteContent($routeName, $routeContent);
	}

}