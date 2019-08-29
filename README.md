=== SCSS-Library ===
Contributors: sebaxtian
Tags: SASS, compiler, SCSS
Requires at least: 4.4
Tested up to: 5.2.2
Stable tag: trunk
Requires PHP: 5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Agrega soporte para usar archivos de estilo SCSS con wp_enqueue_style.

== Description ==
Este plugin permite usar arhivos SCSS directamente en `wp_enqueue_style`. Tan solo agregue el archivo a la lista de estilos y el plugin se encargará de compilarlo cuando sea necesario.

La base de este plugin está fuertemente influenciada por el código de [WP-SCSS](https://wordpress.org/plugins/wp-scss/) y extrae algunas ideas de [Sassify](https://wordpress.org/plugins/sassify/). El objetivo es mantener el plugin actualizado con la versión más reciente de [scssphp](https://packagist.org/packages/scssphp/scssphp), eliminar las opciones de configuración desde la interfaz gráfica y usar las capacidades phpcss para crear los archivos de depuración.

Este plugin no está pensado para ser instalado por un usuario convencional, sino para ser requerido por plantillas o plugins que deseen incluir archivos de estilo scss y por lo tanto se espera que la configuración se haga en el código.

== Installation ==
1. Decompress scss-library.zip and upload `/scss-library/` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the __Plugins__ menu in WordPress.

== Frequently Asked Questions ==
= Desempeño =
Este plugin agrega muchos pasos extra para algo tan simple como imprimir una etiqueta de enlace de estilo dentro de un sitio:
* Revisa el tiempo de creación del archivo compilado.
* Interactúa con la base de datos.
* Convierte un archivo SCSS en un archivo de estilo.
Es obvio que va a agregar algunas milésimas de segundo al tiempo de carga del sitio.

= ¿Qué tanto se va a ver afectado el desempeño? =
Depende de cuántos archivos SCSS agregue a la lista de estilos y que tan complejos sean.

= ¿Entonces no lo debo usar en producción? =
Claro que puede usarlo. Si lo que busca es tener un sitio rápido entonces deberá también agregar a su entorno de producción un plugin de cache o de optimización, aunque es muy probable que ya lo haya hecho. En lo personal he trabajado con [Comet Cache](https://wordpress.org/plugins/comet-cache/) y con [Autoptimize](https://wordpress.org/plugins/autoptimize/) sin que haya habido inconvenientes. Cualquier problema que encuentre con otro plugin de caché no dude en escribir con los detalles (mientras más información incluya se será más fácil para mi solucionarlo).

= ¿Entonces qué busca con este plugin? =
Lo que quiero es emular para los archivos de estilo la facilidad de desarrollo que ofrece [Timber](https://wordpress.org/plugins/timber-library/). Que SCSS-Library sea a SCSS lo que Timber es a Twig.

Mi objetivo con este plugin es poder cambiar directamente el archivo SCSS y ver el resultado de forma inmediata. Nada de compilaciones previas ni comandos en una terminal. Está pensado para equipos de desarrollo que incluyen diseñadores gráficos que entienden de CSS y HTML pero que se prefieren no tener que abrir una terminal; y para secundar a los propgramadores perezosos que como yo preferimos dejar las tareas repetitivas a las máquinas.

= Is this plugin bug free? =
I don\'t think so. Feedbacks would be appreciated.

== Changelog ==
= 0.1 =
* First release.
