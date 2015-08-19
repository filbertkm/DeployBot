<?php

namespace DeployBot\Command;

use DeployBot\Git;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wikibot\HttpClient;

class BranchInfoCommand extends Command {

	private $repoDir;

	private $output;

	private $packages = array();

	protected function configure() {
		$this->setName( 'branch-info' )
			->setDescription( 'Get branch info' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$this->repoDir = "/home/katie/src/Wikidata";
		$this->output = $output;

		$oldBranch = 'wmf/1.26wmf16';
		$newBranch = 'wmf/1.26wmf19';

		$this->extractVersions( 'new', $newBranch );
		$this->extractVersions( 'old', $oldBranch );

		ksort( $this->packages );

		foreach( $this->packages as $name => $package ) {
			$git = new Git( __DIR__ );
			$gitDir = str_replace( '/', '-', $name );

			$this->output->writeln( $gitDir );

			$git->execute( 'git clone ' . $package['git-url'] . " /tmp/deploy/$gitDir" );

			$packageGit = new Git( "/tmp/deploy/$gitDir" );

			$packageGit->execute( "git fetch --all" );
			$packageGit->execute( "git checkout -b " . $package['new'] . 'origin/' . $package['new'] );
			$packageGit->execute( "git log --pretty=oneline " . $package['old'] . '...' . $package['new'] );
		}
	}

	private function extractVersions( $key, $branch ) {
		$git = new Git( $this->repoDir );

		$git->execute( "git fetch --all" );
		$git->execute( "git checkout master" );
		$git->execute( "git branch -D $branch" );

		$result = $git->execute( "git checkout -b $branch origin/$branch" );

		$json = file_get_contents( $this->repoDir . '/composer.lock' );
		$data = json_decode( $json, true );

		foreach ( $data['packages'] as $package ) {
			$name = $package['name'];

			if ( !array_key_exists( $name, $this->packages ) ) {
				$this->packages[$name] = array(
					'git-url' => $package['source']['url']
				);
			}

			if ( $name === 'wikibase/constraints' || $name === 'wikibase/quality' ) {
				$version = 'v1';
			} else {
				$version = preg_replace( '/^dev\-/', '', $package['version'] );
				$version = preg_replace( '/\-dev$/', '', $version );
			}

			$this->output->writeln( $version );

			$this->packages[$name][$key] = $version;
		}
	}

}
