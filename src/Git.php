<?php

namespace DeployBot;

class Git {

	private $repoDir;

	public function __construct( $repoDir ) {
		$this->repoDir = $repoDir;
	}

	public function execute( $command ) {
		$cwd = getcwd();

		chdir( $this->repoDir );
		exec( $command, $output, $returnValue );
		chdir( $cwd );

		if ( $returnValue !== 0 ) {
			throw new \RuntimeException( implode( "\n", $output ) );
		}

		return $output;
	}

}
