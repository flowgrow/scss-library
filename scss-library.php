<?php
/*
Plugin Name: SCSS-Library
Description: Adds support for SCSS stylesheets to wp_enqueue_style.
Author: Juan Sebastián Echeverry
Version: 0.1.3
Text Domain: scsslib

Copyright 2019 Juan Sebastián Echeverry (baxtian.echeverry@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once('vendor/autoload.php');

use ScssPhp\ScssPhp\Compiler;

// Inicializar Plugin
class ScssLibrary
{
	/**
	 * Arreglo para guardar los errores de compliación.
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Crear la instancia.
	 * @return void
	 */
	public function __construct()
	{
		add_action('plugins_loaded', [$this, 'plugin_setup']);
		add_filter('style_loader_src', [$this, 'style_loader_src'], 10, 2);
		add_action('wp_footer', array($this, 'wp_footer'));
	}

	//Función después de activar los plugins
	public function plugin_setup()
	{
		//Activar el traductor
		load_plugin_textdomain('scsslib', false, basename(__DIR__) . '/languages');
	}

	// Función para compilar los estilos SCSS.
	public function style_loader_src($src, $handle)
	{

		// Si el nombre el archivo no tiene el texto scss entonces
		// retornar el estilo sin cambios
		if (strpos($src, 'scss') === false) {
			return $src;
		}

		// Parsear la URL del archivo de estilo
		$url = parse_url($src);
		$pathinfo = pathinfo($url['path']);

		// Revisión detallada para determinar si la extensión corresponde
		if ($pathinfo['extension'] !== 'scss') {
			return $src;
		}

		// Convertir la URL a rutas absolutas.
		$in = preg_replace('/^' . preg_quote(site_url(), '/') . '/i', '', $src);

		// Ignorar SCSS de CDNs, otros dominios y rutas relativos
		if (preg_match('#^//#', $in) || strpos($in, '/') !== 0) {
			return $src;
		}

		// Crear ruta completa
		$in = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $url['path'];

		//Si es parte de un multisitio entonces hay que retirar el 'dominio'
		if ( is_multisite() ) {
            $blog_details   = get_blog_details();
			if($blog_details->path != PATH_CURRENT_SITE) {
				$in = str_replace($blog_details->path, PATH_CURRENT_SITE, $in);
			}
        }

		// Confirmar que el archivo fuente existe
		if (file_exists($in) === false) {
			array_push($this->errors, array(
				'file'    => basename($in),
				'message' => __('Source file not found.', 'scsslib'),
			));
			return $src;
		}

		// Generar nombre único paa el archivo compilado
		$outName = sha1($url['path']) . '.css';

		// Directorio donde se almacenará el cache
		$pathname = '/build/scss_library/';
		if ( is_multisite() ) {
            $blog_id   = get_current_blog_id();
            $pathname .= $blog_id . '/';
        }

		$wp_upload_dir = wp_upload_dir();
		$outputDir = WP_CONTENT_DIR . $pathname;
		$outputUrl = WP_CONTENT_URL .  $pathname . $outName;

		// Crear el directorio de archivos compilados si no existe
		if (is_dir($outputDir) === false) {
			if (wp_mkdir_p($outputDir) === false) {
				array_push($this->errors, array(
					'file'    => 'Cache Directory',
					'message' => __('File Permissions Error, unable to create cache directory. Please make sure the Wordpress Uploads directory is writable.', 'scsslib'),
				));
				delete_transient('scsslib_filemtimes');
				return $src;
			}
		}

		// Revisar que el directorio donde se almacenarán los archivos
		// compilados tiene permisos de escritura
		if (is_writable($outputDir) === false) {
			array_push($this->errors, array(
				'file'    => 'Cache Directory',
				'message' => __('File Permissions Error, permission denied. Please make the cache directory writable.', 'scsslib'),
			));
			delete_transient('scsslib_filemtimes');
			return $src;
		}

		// Ruta comleta para el archivo compilado
		$out = $outputDir . $outName;

		// Bandera para saber si se requiere compilar el archivo. Por defecto suponemos
		// que no es necesario compilar.
		$compileRequired = false;

		// Obtener la fecha que tenemos almacenada como fecha de creación de cada archivos
		if (($filemtimes = get_transient('scsslib_filemtimes')) === false) {
			$filemtimes = array();
		}

		// Compara la fecha de creación del archivo compilado con la fecha de creación del
		// archivo fuente. Si es más reciente entones hay que compilar.
		if ($compileRequired === false) {
			if (isset($filemtimes[$out]) === false || $filemtimes[$out] < filemtime($in)) {
				$compileRequired = true;
			}
		}

		// Obtener las variables variables
		$variables = apply_filters('scsslib_compiler_variables', array(
			'template_directory_uri'   => get_template_directory_uri(),
			'stylesheet_directory_uri' => get_stylesheet_directory_uri(),
		));

		// Si las variables no coinciden entonces hay que compilar
		if ($compileRequired === false) {
			$signature = sha1(serialize($variables));
			if ($signature !== get_transient('scsslib_variables_signature')) {
				$compileRequired = true;
				set_transient('scsslib_variables_signature', $signature);
			}
		}

		//Si el archivo no existe entonces hay que compilar
		if(!file_exists($outputDir . $outName)) {
			$compileRequired = true;
		}

		// Tipo de formato por defecto
		$formatter = 'ScssPhp\ScssPhp\Formatter\Expanded';

		// ¿Debemos o no compilar?
		if ($compileRequired) {
			// Inicializar compilador
			$compiler = new Compiler();

			// Determinar las varianles para el archivo de depuración
			$srcmap_data = array(
				// Ruta absoluta donde se escribirá el archivo .map
				'sourceMapWriteTo'  => $outputDir . $outName . ".map",
				// URL completa o relativa al archivp .map
				'sourceMapURL'      => $outputUrl . ".map",
				// (Opcional) URL relativa o completa al archivo .css compilado
				'sourceMapFilename' => $outputUrl,
				// Ruta parcial (raiz del servidor) para crear la URL relativa
				'sourceMapBasepath' => rtrim(ABSPATH, '/'),
				// (Opcional) Antepuesto a las entradas de campo 'fuente' para reubicar archivos fuente
				'sourceRoot'        => $src,
			);

			// Configuración para crear el archivo .map de depuración.
			$compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);
			$compiler->setSourceMapOptions($srcmap_data);

			// Configuración para inicializar el compilador.
			$compiler->setFormatter($formatter);
			$compiler->setVariables($variables);
			$compiler->setImportPaths(dirname($in));

			try {
				// Compilar de SCSS a CSS
				$css = $compiler->compile(file_get_contents($in), $in);
			} catch (Exception $e) {
				array_push($this->errors, array(
					'file'    => basename($in),
					'message' => $e->getMessage(),
				));
				return $src;
			}

			// Transformar las rutas relativas para que funcionen correctamente
			$css = preg_replace('#(url\((?![\'"]?(?:https?:|/))[\'"]?)#miu', '$1' . dirname($url['path']) . '/', $css);

			// Guardar el archivo compilado.
			file_put_contents($out, $css);

			// Guardar el tiempo de creación del archivo.
			$filemtimes[$out] = filemtime($out);
			set_transient('scsslib_filemtimes', $filemtimes);
		}

		// Construir URL del archivio compilado con las cadenas de consulta que
		// venían en la URL del archivo fuente.
		return empty($url['query']) ? $outputUrl : $outputUrl . '?' . $url['query'];
	}

	// Publicar errores
	public function wp_footer()
	{
		//Si hay errores, visualizarlos
		if (count($this->errors)) {
			$this->displayErrors();
		}
	}

	// HTML para visualizar errores.
	protected function displayErrors()
	{
		?>
		<style>
		#scsslib {
			position: fixed;
			top: 0;
			z-index: 99999;
			width: 100%;
			padding: 20px;
			overflow: auto;
			background: #f5f5f5;
			font-family: 'Source Code Pro', Menlo, Monaco, Consolas, monospace;
			font-size: 18px;
			color: #666;
			text-align: left;
			border-left: 5px solid #DD3D36;
		}
		body.admin-bar #scsslib {
			top: 32px;
		}
		#scsslib .scsslib-title {
			margin-bottom: 20px;
			font-size: 120%;
		}
		#scsslib .scsslib-error {
			margin: 10px 0;
		}
		#scsslib .scsslib-file {
			font-weight: bold;
			white-space: pre;
			white-space: pre-wrap;
			word-wrap: break-word;
		}
		#scsslib .scsslib-message {
			white-space: pre;
			white-space: pre-wrap;
			word-wrap: break-word;
		}
		</style>
		<div id="scsslib">
			<div class="scsslib-title"><?php _e('Sass Compiling Error', 'scsslib'); ?></div>
			<?php foreach ($this->errors as $error): ?>
				<div class="scsslib-error">
					<div class="scsslib-file"><?php print $error['file'] ?></div>
					<div class="scsslib-message"><?php print $error['message'] ?></div>
				</div>
			<?php endforeach ?>
		</div>
		<?php
	}
}

new ScssLibrary;
