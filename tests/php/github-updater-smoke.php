<?php

declare( strict_types=1 );

/**
 * Standalone smoke tests for GoodBlocks GitHub updater.
 *
 * Run with:
 *   php tests/php/github-updater-smoke.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}

function add_filter(): void {}

function untrailingslashit( $value ): string {
	return rtrim( (string) $value, '/\\' );
}

function trailingslashit( $value ): string {
	return untrailingslashit( $value ) . '/';
}

function wp_normalize_path( $path ): string {
	$path = str_replace( '\\', '/', (string) $path );
	$path = preg_replace( '#/+#', '/', $path ) ?? $path;
	return $path;
}

function plugin_basename( string $file ): string {
	return 'goodblocks/goodblocks.php';
}

function activate_plugin( string $plugin ): bool {
	$GLOBALS['goodblocks_activated'][] = $plugin;
	return true;
}

function is_plugin_active( string $plugin ): bool {
	return ! empty( $GLOBALS['goodblocks_plugin_active'] );
}

class GoodBlocks_Test_Filesystem {
	public array $deleted = [];
	public array $moved = [];
	public ?string $fail_move_from = null;

	public function exists( $file ): bool {
		return file_exists( (string) $file );
	}

	public function delete( $file, $recursive = false ): bool {
		$this->deleted[] = $file;
		if ( is_file( $file ) ) {
			return unlink( $file );
		}
		if ( ! is_dir( $file ) ) {
			return true;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $file, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $iterator as $item ) {
			$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
		}
		return rmdir( $file );
	}

	public function move( $source, $destination ): bool {
		$this->moved[] = [ $source, $destination ];
		if ( $this->fail_move_from && $source === $this->fail_move_from ) {
			return false;
		}
		if ( ! file_exists( $source ) ) {
			return false;
		}
		if ( $source === $destination ) {
			return true;
		}
		return rename( $source, $destination );
	}
}

function is_wp_error( $thing ): bool {
	return $thing instanceof WP_Error;
}

class WP_Error {
	public string $code;
	public string $message;

	public function __construct( string $code = '', string $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}
}

function goodblocks_updater_assert_same( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		fwrite( STDERR, 'Expected: ' . var_export( $expected, true ) . PHP_EOL );
		fwrite( STDERR, 'Actual:   ' . var_export( $actual, true ) . PHP_EOL );
		exit( 1 );
	}
}

require_once dirname( __DIR__, 2 ) . '/inc/github-updater.php';

$updater = new GoodBlocks_GitHub_Updater( '/plugins/goodblocks/goodblocks.php', 'AGoodId/goodblocks' );
$select  = new ReflectionMethod( GoodBlocks_GitHub_Updater::class, 'get_zip_url' );
$select->setAccessible( true );

$rc27_release = (object) [
	'zipball_url' => 'https://api.github.com/repos/AGoodId/goodblocks/zipball/v1.14.0-rc.27',
	'assets'      => [
		(object) [
			'name'                 => 'goodblocks-1.14.0-rc.27-folder.zip',
			'browser_download_url' => 'https://github.com/AGoodId/goodblocks/releases/download/v1.14.0-rc.27/goodblocks-1.14.0-rc.27-folder.zip',
			'url'                  => 'https://api.github.com/repos/AGoodId/goodblocks/releases/assets/1',
		],
		(object) [
			'name'                 => 'goodblocks.zip',
			'browser_download_url' => 'https://github.com/AGoodId/goodblocks/releases/download/v1.14.0-rc.27/goodblocks.zip',
			'url'                  => 'https://api.github.com/repos/AGoodId/goodblocks/releases/assets/2',
		],
	],
];

goodblocks_updater_assert_same(
	'https://github.com/AGoodId/goodblocks/releases/download/v1.14.0-rc.27/goodblocks.zip',
	$select->invoke( $updater, $rc27_release ),
	'get_zip_url must pick the exact goodblocks.zip asset even when *-folder.zip is listed first.'
);

$folder_only = (object) [
	'zipball_url' => 'https://api.github.com/repos/AGoodId/goodblocks/zipball/v1.14.0-rc.27',
	'assets'      => [
		(object) [
			'name'                 => 'goodblocks-1.14.0-rc.27-folder.zip',
			'browser_download_url' => 'https://github.com/AGoodId/goodblocks/releases/download/v1.14.0-rc.27/goodblocks-1.14.0-rc.27-folder.zip',
			'url'                  => 'https://api.github.com/repos/AGoodId/goodblocks/releases/assets/1',
		],
	],
];

goodblocks_updater_assert_same(
	'https://api.github.com/repos/AGoodId/goodblocks/zipball/v1.14.0-rc.27',
	$select->invoke( $updater, $folder_only ),
	'get_zip_url must fall back to the zipball when goodblocks.zip is missing.'
);

$mixed_case = (object) [
	'zipball_url' => 'https://api.github.com/repos/AGoodId/goodblocks/zipball/v1.14.0-rc.27',
	'assets'      => [
		(object) [
			'name'                 => 'goodblocks-1.14.0-rc.27-folder.zip',
			'browser_download_url' => 'https://github.com/AGoodId/goodblocks/releases/download/v1.14.0-rc.27/goodblocks-1.14.0-rc.27-folder.zip',
			'url'                  => 'https://api.github.com/repos/AGoodId/goodblocks/releases/assets/1',
		],
		(object) [
			'name'                 => 'GoodBlocks.zip',
			'browser_download_url' => 'https://github.com/AGoodId/goodblocks/releases/download/v1.14.0-rc.27/GoodBlocks.zip',
			'url'                  => 'https://api.github.com/repos/AGoodId/goodblocks/releases/assets/3',
		],
	],
];

goodblocks_updater_assert_same(
	'https://github.com/AGoodId/goodblocks/releases/download/v1.14.0-rc.27/GoodBlocks.zip',
	$select->invoke( $updater, $mixed_case ),
	'get_zip_url must accept goodblocks.zip case-insensitively.'
);

$plugin_root = sys_get_temp_dir() . '/goodblocks-updater-' . uniqid( '', true );
$plugins_dir = $plugin_root . '/plugins';
$plugin_dir  = $plugins_dir . '/goodblocks';
if ( ! mkdir( $plugin_dir, 0777, true ) && ! is_dir( $plugin_dir ) ) {
	fwrite( STDERR, "Failed to create temp plugin dir.\n" );
	exit( 1 );
}
file_put_contents( $plugin_dir . '/goodblocks.php', "<?php\n// marker\n" );

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', $plugins_dir );
}

$GLOBALS['wp_filesystem']            = new GoodBlocks_Test_Filesystem();
$GLOBALS['goodblocks_activated']     = [];
$GLOBALS['goodblocks_plugin_active'] = true;

$result = $updater->after_install(
	true,
	[ 'plugin' => 'goodblocks/goodblocks.php' ],
	[
		'destination' => $plugin_dir,
	]
);

goodblocks_updater_assert_same(
	true,
	is_file( $plugin_dir . '/goodblocks.php' ),
	'after_install must keep plugins/goodblocks when the zip already extracted there.'
);
goodblocks_updater_assert_same(
	[],
	$GLOBALS['wp_filesystem']->deleted,
	'after_install must not delete the destination when it already matches the extracted folder.'
);
goodblocks_updater_assert_same(
	[],
	$GLOBALS['wp_filesystem']->moved,
	'after_install must not move files when destination already matches the plugin slug.'
);
goodblocks_updater_assert_same(
	[],
	$GLOBALS['goodblocks_activated'],
	'after_install must not reactivate a plugin that is already active.'
);
goodblocks_updater_assert_same(
	true,
	$result,
	'after_install must return the upgrader response, not the result array.'
);

$passthrough = $updater->after_install(
	true,
	[ 'plugin' => 'other/other.php' ],
	[ 'destination' => $plugin_dir ]
);
goodblocks_updater_assert_same(
	true,
	$passthrough,
	'after_install must pass through the upgrader response for other plugins.'
);

$slashed_dir = $plugin_dir . '/';
file_put_contents( $plugin_dir . '/goodblocks.php', "<?php\n// slash\n" );
$GLOBALS['wp_filesystem']            = new GoodBlocks_Test_Filesystem();
$GLOBALS['goodblocks_activated']     = [];
$GLOBALS['goodblocks_plugin_active'] = true;

$updater->after_install(
	true,
	[ 'plugin' => 'goodblocks/goodblocks.php' ],
	[
		'destination' => $slashed_dir,
	]
);

goodblocks_updater_assert_same(
	true,
	is_file( $plugin_dir . '/goodblocks.php' ),
	'after_install must treat a trailing-slash destination as the same plugin folder.'
);
goodblocks_updater_assert_same(
	[],
	$GLOBALS['wp_filesystem']->deleted,
	'after_install must not delete when destination only differs by a trailing slash.'
);

$dot_dir = $plugins_dir . '/./goodblocks';
file_put_contents( $plugin_dir . '/goodblocks.php', "<?php\n// dot\n" );
$GLOBALS['wp_filesystem']            = new GoodBlocks_Test_Filesystem();
$GLOBALS['goodblocks_activated']     = [];
$GLOBALS['goodblocks_plugin_active'] = true;

$updater->after_install(
	true,
	[ 'plugin' => 'goodblocks/goodblocks.php' ],
	[
		'destination' => $dot_dir,
	]
);

goodblocks_updater_assert_same(
	true,
	is_file( $plugin_dir . '/goodblocks.php' ),
	'after_install must treat a /./ destination as the same plugin folder.'
);
goodblocks_updater_assert_same(
	[],
	$GLOBALS['wp_filesystem']->deleted,
	'after_install must not delete when destination only differs by /./.'
);

$other_dir = $plugins_dir . '/AGoodId-goodblocks-abc123';
if ( ! mkdir( $other_dir, 0777, true ) && ! is_dir( $other_dir ) ) {
	fwrite( STDERR, "Failed to create alternate extract dir.\n" );
	exit( 1 );
}
file_put_contents( $other_dir . '/goodblocks.php', "<?php\n// moved\n" );

$GLOBALS['wp_filesystem']            = new GoodBlocks_Test_Filesystem();
$GLOBALS['goodblocks_activated']     = [];
$GLOBALS['goodblocks_plugin_active'] = false;

$moved = $updater->after_install(
	true,
	[ 'plugin' => 'goodblocks/goodblocks.php' ],
	[
		'destination' => $other_dir,
	]
);

goodblocks_updater_assert_same(
	true,
	is_file( $plugin_dir . '/goodblocks.php' ),
	'after_install must leave a plugin at plugins/goodblocks after moving from another extract folder.'
);
goodblocks_updater_assert_same(
	'<?php' . "\n// moved\n",
	file_get_contents( $plugin_dir . '/goodblocks.php' ),
	'after_install must move the newly extracted files onto the plugin slug.'
);
goodblocks_updater_assert_same(
	[ 'goodblocks/goodblocks.php' ],
	$GLOBALS['goodblocks_activated'],
	'after_install must activate the plugin when it is not already active.'
);
goodblocks_updater_assert_same(
	true,
	$moved,
	'after_install must return true after a successful move.'
);

file_put_contents( $plugin_dir . '/goodblocks.php', "<?php\n// keep-me\n" );
$fail_dir = $plugins_dir . '/AGoodId-goodblocks-fail';
if ( ! mkdir( $fail_dir, 0777, true ) && ! is_dir( $fail_dir ) ) {
	fwrite( STDERR, "Failed to create fail extract dir.\n" );
	exit( 1 );
}
file_put_contents( $fail_dir . '/goodblocks.php', "<?php\n// new-copy\n" );

$GLOBALS['wp_filesystem']            = new GoodBlocks_Test_Filesystem();
$GLOBALS['wp_filesystem']->fail_move_from = $fail_dir;
$GLOBALS['goodblocks_activated']     = [];
$GLOBALS['goodblocks_plugin_active'] = false;

$failed = $updater->after_install(
	true,
	[ 'plugin' => 'goodblocks/goodblocks.php' ],
	[
		'destination' => $fail_dir,
	]
);

goodblocks_updater_assert_same(
	true,
	is_file( $plugin_dir . '/goodblocks.php' ),
	'after_install must keep the existing plugin if the replacement move fails.'
);
goodblocks_updater_assert_same(
	"<?php\n// keep-me\n",
	file_get_contents( $plugin_dir . '/goodblocks.php' ),
	'after_install must not replace plugin files when the move fails.'
);
goodblocks_updater_assert_same(
	true,
	is_wp_error( $failed ),
	'after_install must return WP_Error when the replacement move fails.'
);
goodblocks_updater_assert_same(
	[],
	$GLOBALS['goodblocks_activated'],
	'after_install must not activate after a failed replacement.'
);

fwrite( STDOUT, "GitHub updater smoke tests passed.\n" );
