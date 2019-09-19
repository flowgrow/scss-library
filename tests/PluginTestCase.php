<?php
use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * An abstraction over WP_Mock to do things fast
 * It also uses the snapshot trait
 */
class PluginTestCase extends \PHPUnit\Framework\TestCase
{
	// use MatchesSnapshots;
	// use MockeryPHPUnitIntegration;

	/**
	 * Setup which calls \WP_Mock setup
	 *
	 * @return void
	 */
	public function setUp(): void
	{
		parent::setUp();

		Monkey\setUp();
		// A few common passthrough
		// 1. WordPress i18n functions
		Functions\when('__')
			->returnArg(1);
		Functions\when('_e')
			->returnArg(1);
		Functions\when('_n')
			->returnArg(1);

		// Definción de funciones a ser 'emulada'
		Functions\when('site_url')
			->justReturn('file://');
		Functions\when('wp_upload_dir')
			->justReturn(array(
				'path' => WP_CONTENT_DIR . 'year/month',
				'url' => WP_CONTENT_URL . 'year/month',
				'subdir' => 'year/month',
				'basedir' => WP_CONTENT_DIR,
				'baseurl' => WP_CONTENT_URL,
				'error' => false,
			));
		Functions\when('get_template_directory_uri')
			->justReturn(WP_CONTENT_URL);
		Functions\when('get_stylesheet_directory_uri')
			->justReturn(WP_CONTENT_URL);
		Functions\when('wp_mkdir_p')
			->alias('mkdir');
		Functions\stubs(
			[
				'set_transient',
				'delete_transient',
			],
			true
		);
		Functions\stubs(
			[
				'is_multisite',
				'get_transient',
			],
			false
		);
	}

	/**
	 * Teardown which calls \WP_Mock tearDown
	 *
	 * @return void
	 */
	public function tearDown(): void
	{
		Monkey\tearDown();
		parent::tearDown();
	}
}
