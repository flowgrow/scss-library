<?php
// namespace PluginTest\Stuff;
use ScssLibrary\ScssLibrary;
use \Brain\Monkey\Functions;

class ScssLibraryTest extends \PluginTestCase
{
	public function testAddHooksActuallyAddsHooks()
	{
		$class = ScssLibrary::get_instance();
		self::assertTrue(has_action('plugins_loaded', 'ScssLibrary\ScssLibrary->plugin_setup()'));
	}
}
