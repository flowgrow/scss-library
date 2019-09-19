<?php
// namespace PluginTest\Stuff;
use ScssLibrary\ScssLibrary;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;

class ScssLibraryTest extends \PluginTestCase
{
	// Borrado recursivo de directorio
	static public function recurseRmdir($dir)
	{
		if(!is_dir("$dir")) return false;
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? self::recurseRmdir("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}

	// Probar inicialización, detectando que se carguen los hook
	public function test_init()
	{
		// Expectativas
		Actions\expectAdded('plugins_loaded');
		Filters\expectAdded('style_loader_src');
		Actions\expectAdded('wp_footer');
		Functions\expect('load_plugin_textdomain')
			->once();

		$stub = ScssLibrary::get_instance();
		$stub->plugin_setup();

		// ¿Se creó la instancia de clase ScssLibrary\ScssLibrary?
		self::assertInstanceOf('ScssLibrary\ScssLibrary', $stub);
	}

	// Múltiples pruebas a la función que crea el archivo css.
	public function test_style_loader_src()
	{
		// Inicializar clase
		$stub = ScssLibrary::get_instance();

		// Ignorar archivo css (fuera de este plugin porque tiene scss en el path)
		$file = 'assets/style.css';
		self::assertTrue($file == $stub->style_loader_src($file, 'test'));

		// Ignorar archivo css
		$file = WP_CONTENT_URL . 'style.css';
		self::assertTrue($file == $stub->style_loader_src($file, 'test'));

		// Ignorar archivo scss externo
		$file = 'http://localhost/assets/style.scss';
		self::assertTrue($file == $stub->style_loader_src($file, 'test'));

		// Crear archivo scss y comprobar que fue creado
		$file_css = WP_CONTENT_URL . 'style.scss';
		$file_scss = $stub->style_loader_src($file_css, 'test');
		self::assertTrue(strpos($file_scss, 'build/scss_library') != false);
		self::assertFileExists($file_scss);

		// Usar el archivo cuando ya existe el archivo
		$file_scss = $stub->style_loader_src($file_css, 'test');
		self::assertTrue(strpos($file_scss, 'build/scss_library') != false);

		// Crear archivo en Multisitio 2 y comprobar que fue creado
		Functions\when('is_multisite')->justReturn(true);
		Functions\when('get_blog_details')->justReturn(PATH_CURRENT_SITE . '/sitio_2/' );
		Functions\when('get_current_blog_id')->justReturn(2);
		$file_scss = $stub->style_loader_src($file_css, 'test');
		self::assertTrue(strpos($file_scss, 'build/scss_library/2') != false);
		self::assertFileExists($file_scss);

		// Borrar archivos compilados
		self::recurseRmdir(WP_CONTENT_DIR . 'build');
	}

	// Probar archivo inexistente
    public function test_archivo_inexistente()
	{
		// Inicializar clase
		$stub = ScssLibrary::get_instance();

		// Archivo css inexsistente
		$file_scss = WP_CONTENT_URL . 'inexistente.scss';
		$file_css = $stub->style_loader_src($file_scss, 'test');
		self::expectOutputRegex("/<div class=[\"']scsslib-error[\"']>/");
		$stub->wp_footer();

		// Borrar archivos compilados
		self::recurseRmdir(WP_CONTENT_DIR . 'build');
	}

	// Probar con problemas al crear el directorio
	public function test_problemas_creacion_directorio()
	{
		// Inicializar clase
		$stub = ScssLibrary::get_instance();

		// Indicar que el directorio no se pudo crear
		Functions\when('wp_mkdir_p')->justReturn(false);

		$file_scss = WP_CONTENT_URL . 'style.scss';
		$file_css = $stub->style_loader_src($file_scss, 'test');
		self::expectOutputRegex("/<div class=[\"']scsslib-error[\"']>/");
		self::expectOutputRegex("/File Permissions Error, unable to create cache directory./");
		$stub->wp_footer();
	}

	// Probar con problemas de escritura en el directorio
	public function test_problemas_escritura_directorio()
	{
		// Inicializar clase
		$stub = ScssLibrary::get_instance();
		$stub_class = get_class($stub);

		$wp_fs_mock = Mockery::mock( 'WP_Filesystem_Direct' );
		$wp_fs_mock
			->shouldReceive( 'is_writable' )
			->once()
			->andReturn(false);

		$file_scss = WP_CONTENT_URL . 'style.scss';
		$file_css = $stub->style_loader_src($file_scss, 'test');
		self::expectOutputRegex("/<div class=[\"']scsslib-error[\"']>/");
		self::expectOutputRegex("/File Permissions Error, permission denied./");
		$stub->wp_footer();
	}

	// Generar un error en la compilación de un archivo
	// public function test_problemas_al_compilar()
	// {
	// 	// Inicializar clase
	// 	$stub = ScssLibrary::get_instance();
	// 	$stub_class = get_class($stub);
	//
	// 	$file_scss = WP_CONTENT_URL . 'error.scss';
	// 	$file_css = $stub->style_loader_src($file_scss, 'test');
	// 	//self::expectException("ScssPhp\ScssPhp\Exception\ParserException::class");
	// }
}
