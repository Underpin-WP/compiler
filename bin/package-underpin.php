#!/usr/bin/env php
<?php

/**
 * Class Underpin_Compiler.
 * This utility will replace all mentions of Underpin in your vendor directory with a customized name.
 * This makes it possible to use Underpin in a plugin or theme, and package it up without worry of namespace collisions.
 */
class Underpin_Compiler {

	/**
	 * Errors.
	 *
	 * @since 1.0.0
	 *
	 * @var array list of errors while running this command.
	 */
	protected $errors = [];

	/**
	 * The command arguments passed into the constructor
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $argv;

	/**
	 * The name with which Underpin will be replaced.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The root plugin directory.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $root_dir;

	/**
	 * The vendor directory.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $vendor_dir;

	/**
	 * Underpin_Compiler constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $argv List of arguments passed by the command.
	 */
	public function __construct( $argv ) {
		$this->argv     = $argv;
		$this->name     = isset( $this->argv[1] ) ? $this->argv[1] : '';
		$this->root_dir = getcwd();


		if ( ! isset( $this->argv[1] ) ) {
			$this->errors[] = "Missing required argument, \"name\"\nexample:\npackage-underpin.php test";
		}

		if ( 0 !== preg_match( '/^[_0-9]|[^a-zA-Z_]/', $this->name ) ) {
			$this->errors[] = "Invalid name. Name can only have letters or underscores.";
		}

		// Construct the vendor directory.
		$composer_file = $this->root_dir . '/composer.json';

		if ( file_exists( $composer_file ) ) {
			$composer = json_decode( $composer_file );
		} else {
			$composer = new stdClass();
		}

		if ( isset( $composer->config ) && isset( $composer->config->vendor_dir ) ) {
			$this->vendor_dir = $this->root_dir . $composer->config->vendor_dir;
		} else {
			$this->vendor_dir = $this->root_dir . '/vendor';
		}

		if ( ! is_dir( $this->vendor_dir ) ) {
			$this->errors[] = 'Could not find the vendor directory. Have you installed your dependencies?';
		}
	}

	/**
	 * Returns the list of errors for output in the terminal.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_error_output() {
		return implode( "\r", $this->errors );
	}

	/**
	 * Returns true if any errors have occured while running this factory.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_errors() {
		return count( $this->errors ) > 0;
	}

	/**
	 * Renames files and file contents.
	 *
	 * @since 1.0.0
	 */
	public function prepare_files() {

		foreach ( $this->get_files() as $file ) {
			if ( ! $file instanceof splFileInfo ) {
				continue;
			}

			if ( $file->isDir() ) {
				continue;
			}

			$contents = file_get_contents( $file->getPathName() );
			$contents = str_replace( 'Underpin', ucfirst( $this->name ), $contents );
			$contents = str_replace( 'underpin', $this->name, $contents );
			file_put_contents( $file, $contents );

			// If the file contains the word underpin, replace it.
			if ( false !== strpos( $file->getFilename(), 'Underpin' ) ) {
				$new_name = str_replace( 'Underpin', ucfirst( $this->name ), $file->getFilename() );
				rename( $file->getPathName(), $file->getPath() . DIRECTORY_SEPARATOR . $new_name );
			}

			// If the file contains the word underpin, replace it.
			if ( false !== strpos( $file->getFilename(), 'underpin' ) ) {
				$new_name = str_replace( 'underpin', $this->name, $file->getFilename() );
				rename( $file->getPathName(), $file->getPath() . DIRECTORY_SEPARATOR . $new_name );
			}
		}
	}

	/**
	 * Renames directories.
	 *
	 * @since 1.0.0
	 */
	public function prepare_dirs() {
		foreach ( $this->get_directories() as $dir ) {
			$path = explode( '/', $dir );
			array_pop( $path );
			array_pop( $path );
			$path = implode( '/', $path );
			if ( $dir->isDir() && is_dir( $path ) ) {

				// If the file contains the word underpin, replace it.
				if ( false !== strpos( $dir->getPath(), 'Underpin' ) ) {
					$this->recurse_copy( $dir->getPath(), $path . DIRECTORY_SEPARATOR . ucfirst( $this->name ) );
					$this->delete_directory( $dir->getPath() );
				}

				//If the file contains the word underpin, replace it.
				if ( false !== strpos( $dir->getPath(), 'underpin' ) ) {
					$this->recurse_copy( $dir->getPath(), $path . DIRECTORY_SEPARATOR . $this->name );
					$this->delete_directory( $dir->getPath() );
				}

			}
		}
	}

	/**
	 * Compiles the files using the specified name.
	 *
	 * @since 1.0.0
	 */
	public function compile() {
		$this->prepare_files();
		$this->prepare_dirs();
	}

	/**
	 * Fetches all PHP files in the vendor directory.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_files() {
		$path = $this->vendor_dir;

		// Bail early if the path does not exist.
		if ( ! is_dir( $path ) ) {
			return [];
		}

		// :dizzy-face:
		$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ) );

		$files = array();
		foreach ( $rii as $file ) {
			if ( $file->getExtension() === 'php' && ! $file->isDir() ) {
				$files[] = $file;
			}
		}

		return $files;
	}

	/**
	 * Fetches all PHP directories in the vendor directory.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	function get_directories() {
		$path = $this->vendor_dir;
		$rii  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ) );
		$dirs = [];

		foreach ( $rii as $file ) {
			if ( $file->isDir() &&
					 ( false !== strpos( basename( $file->getPath() ), 'underpin' ) ||
						 false !== strpos( basename( $file->getPath() ), 'Underpin' ) ) ) {
				$dirs[] = $file;
			}
		}


		usort( $dirs, function ( $a, $b ) {
			return count( explode( '/', $a ) ) < count( explode( '/', $b ) );
		} );


		return $dirs;
	}

	/**
	 * Recursive copy
	 * Inspired by https://stackoverflow.com/a/5424235
	 *
	 * @since 1.0.0
	 */
	function recurse_copy( $src, $dst ) {
		$dir = opendir( $src );
		@mkdir( $dst );
		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( ( $file != '.' ) && ( $file != '..' ) ) {
				if ( is_dir( $src . '/' . $file ) ) {
					$this->recurse_copy( $src . '/' . $file, $dst . '/' . $file );
				} else {
					copy( $src . '/' . $file, $dst . '/' . $file );
				}
			}
		}
		closedir( $dir );
	}

	/**
	 * Deletes directories recursively.
	 *
	 * @param $dir
	 *
	 * @return bool
	 */
	function delete_directory( $dir ) {
		if ( ! file_exists( $dir ) ) {
			return true;
		}

		if ( ! is_dir( $dir ) ) {
			return unlink( $dir );
		}

		foreach ( scandir( $dir ) as $item ) {
			if ( $item == '.' || $item == '..' ) {
				continue;
			}

			if ( ! $this->delete_directory( $dir . DIRECTORY_SEPARATOR . $item ) ) {
				return false;
			}

		}

		return rmdir( $dir );
	}

}

$compiler = new Underpin_Compiler( $argv );

if ( true === $compiler->has_errors() ) {
	echo $compiler->get_error_output();
	return;
} else {
	$compiler->compile();

	if ( $compiler->has_errors() ) {
		echo $compiler->get_error_output();
		return;
	}

	echo 'Successfully renamed packages';

	return;
}
