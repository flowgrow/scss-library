<?php
namespace ScssLibrary;

use \Brain\Monkey\Functions;

class ScssLibraryTest extends \PHPUnit\Framework\TestCase
{
	public function testAddHooksActuallyAddsHooks()
	{
		$class = ScssLibrary::get_instance();

		self::assertTrue(has_action('plugins_loaded', 'ScssLibrary->plugin_setup()'));
	}
}
