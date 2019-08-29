<?php
/*
Plugin Name: SCSS-Library
Description: Adds support for SCSS stylesheets to wp_enqueue_style.
Author: Juan Sebastián Echeverry
Version: 0.1.0
Text Domain: sasslib

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
class SassLibrary
{
	/**
	 * An array of any compilation errors.
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Create an instance.
	 * @return void
	 */
	public function __construct()
	{
		add_filter('style_loader_src', [$this, 'style_loader_src'], 10, 2);
		add_action('wp_footer', array($this, 'wp_footer'));
	}

	/**
	 * Hook into wp_enqueue_style to compile stylesheets
	 */
	public function style_loader_src($src, $handle)
	{

		// Quick check for SCSS files
		if (strpos($src, 'scss') === false) {
			return $src;
		}

		$url = parse_url($src);
		$pathinfo = pathinfo($url['path']);

		// Detailed check for SCSS files
		if ($pathinfo['extension'] !== 'scss') {
			return $src;
		}

		// Convert site URLs to absolute paths
		$in = preg_replace('/^' . preg_quote(site_url(), '/') . '/i', '', $src);

		// Ignore SCSS from CDNs, other domains and relative paths
		if (preg_match('#^//#', $in) || strpos($in, '/') !== 0) {
			return $src;
		}

		// Create a complete path
		$in = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $url['path'];

		// Check and make sure the file exists
		if (file_exists($in) === false) {
			array_push($this->errors, array(
				'file'    => basename($in),
				'message' => 'Source file not found.',
			));
			return $src;
		}

		// Generate unique filename for output
		$outName = sha1($src) . '.css';

		//Directorio donde se almacenará el cache
		$pathname = '/cache/scss_library/';
		if ( is_multisite() ) {
            $blog_id   = get_current_blog_id();
            $pathname .= $blog_id . '/';
        }

		$wp_upload_dir = wp_upload_dir();
		$outputDir = WP_CONTENT_DIR . $pathname;
		$outputUrl = WP_CONTENT_URL .  $pathname . $outName;

		// Create the output directory if it doesn't exisit
		if (is_dir($outputDir) === false) {
			if (wp_mkdir_p($outputDir) === false) {
				array_push($this->errors, array(
					'file'    => 'Cache Directory',
					'message' => 'File Permissions Error, unable to create cache directory. Please make sure the Wordpress Uploads directory is writable.',
				));
				return $src;
			}
		}

		// Check that the output directory is writable
		if (is_writable($outputDir) === false) {
			array_push($this->errors, array(
				'file'    => 'Cache Directory',
				'message' => 'File Permissions Error, permission denied. Please make the cache directory writable.',
			));
			return $src;
		}

		// Full output path
		$out = $outputDir . $outName;

		// Flag if a compile is required
		// $compileRequired = $this->admin->get_setting('always_compile', false);
		$compileRequired = false;

		// Retrieve cached filemtimes
		if (($filemtimes = get_transient('scsslib_filemtimes')) === false) {
			$filemtimes = array();
		}

		// Check if compile is required based on file modification times
		if ($compileRequired === false) {
			if (isset($filemtimes[$out]) === false || $filemtimes[$out] < filemtime($in)) {
				$compileRequired = true;
			}
		}

		// Retrieve variables
		$variables = apply_filters('scsslib_compiler_variables', array(
			'template_directory_uri'   => get_template_directory_uri(),
			'stylesheet_directory_uri' => get_stylesheet_directory_uri(),
		));

		// If variables have been updated then recompile
		if ($compileRequired === false) {
			$signature = sha1(serialize($variables));
			if ($signature !== get_transient('scsslib_variables_signature')) {
				$compileRequired = true;
				set_transient('scsslib_variables_signature', $signature);
			}
		}

		// $formatter = $this->admin->get_setting('compiling_mode', 'Leafo_ScssPhp_Formatter_Expanded');
		$formatter = 'ScssPhp\ScssPhp\Formatter\Expanded';

		// Check if the stylesheet needs to be recompiled
		if ($compileRequired) {
			$compiler = new Compiler();

			$srcmap_data = array(
				// absolute path to write .map file
				'sourceMapWriteTo'  => $outputDir . $outName . ".map",
				// relative or full url to the above .map file
				'sourceMapURL'      => $outputUrl . ".map",
				// (optional) relative or full url to the .css file
				'sourceMapFilename' => $outputUrl,
				// partial path (server root) removed (normalized) to create a relative url
				'sourceMapBasepath' => rtrim(ABSPATH, '/'),
				// (optional) prepended to 'source' field entries for relocating source files
				'sourceRoot'        => $src,
			);

			$compiler->setSourceMap(Compiler::SOURCE_MAP_FILE);  // SOURCE_MAP_NONE, SOURCE_MAP_INLINE, or SOURCE_MAP_FILE
			$compiler->setSourceMapOptions($srcmap_data);

			$compiler->setFormatter($formatter);
			$compiler->setVariables($variables);
			$compiler->setImportPaths(dirname($in));

			try {
				// Compile the SCSS to CSS
				$css = $compiler->compile(file_get_contents($in), $in);
			} catch (Exception $e) {
				array_push($this->errors, array(
					'file'    => basename($in),
					'message' => $e->getMessage(),
				));
				return $src;
			}

			// Transform relative paths so they still work correctly
			$css = preg_replace('#(url\((?![\'"]?(?:https?:|/))[\'"]?)#miu', '$1' . dirname($url['path']) . '/', $css);

			// Save the CSS
			file_put_contents($out, $css);

			// Cache the filemtime for the destination file
			$filemtimes[$out] = filemtime($out);
			set_transient('scsslib_filemtimes', $filemtimes);
		}

		// Build URL with query string
		return empty($url['query']) ? $outputUrl : $outputUrl . '?' . $url['query'];
	}

	/**
	 * Output any errors in the footer.
	 */
	public function wp_footer()
	{
		// $error_mode = $this->admin->get_setting('errors_mode');
		$error_mode = 'error_display';

		if (count($this->errors)) {
			switch ($error_mode) {
				case 'error_log':
					$this->logErrors();
					break;

				default:
					$this->displayErrors();
			}
		}
	}

	/**
	 * Display HTML formatted errors.
	 */
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
			<div class="scsslib-title">Sass Compiling Error</div>
			<?php foreach ($this->errors as $error): ?>
				<div class="scsslib-error">
					<div class="scsslib-file"><?php print $error['file'] ?></div>
					<div class="scsslib-message"><?php print $error['message'] ?></div>
				</div>
			<?php endforeach ?>
		</div>
		<?php
	}

	/**
	 * Log errors.
	 */
	protected function logErrors()
	{
		foreach ($this->errors as $error) {
			error_log($error['file'] . ': ' . $error['message']);
		}
	}
}

new SassLibrary;
