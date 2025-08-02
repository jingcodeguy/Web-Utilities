<?php
/**
 * Simple dynamic cache clearing tool for SiteGround servers.
 *
 * Based on the core logic of the Speed Optimizer WordPress plugin (by SiteGround) and WordPress Core API,
 * but rewritten as a minimal standalone version without UI or WP dependencies.
 *
 * 本工具參考自 SiteGround Speed Optimizer 插件及的核心清除快取邏輯 WordPress 核心相關 API，
 * 並以獨立方式簡化重寫，無需依賴 WordPress 環境，可直接使用。
 *
 * For personal or internal use. Not affiliated with or endorsed by SiteGround.
 */

# Must run in siteground hosting.
const SITE_TOOLS_SOCK_FILE = '/chroot/tmp/site-tools.sock';

function wp_json_encode( $value, $flags = 0, $depth = 512 ) {
	$json = json_encode( $value, $flags, $depth );

	// If json_encode() was successful, no need to do more confidence checking.
	if ( false !== $json ) {
		return $json;
	}

	return json_encode( $value, $flags, $depth );
}


function _wp_translate_php_url_constant_to_key( $constant ) {
	$translation = array(
		PHP_URL_SCHEME   => 'scheme',
		PHP_URL_HOST     => 'host',
		PHP_URL_PORT     => 'port',
		PHP_URL_USER     => 'user',
		PHP_URL_PASS     => 'pass',
		PHP_URL_PATH     => 'path',
		PHP_URL_QUERY    => 'query',
		PHP_URL_FRAGMENT => 'fragment',
	);

	if ( isset( $translation[ $constant ] ) ) {
		return $translation[ $constant ];
	} else {
		return false;
	}
}

function _get_component_from_parsed_url_array( $url_parts, $component = -1 ) {
	if ( -1 === $component ) {
		return $url_parts;
	}

	$key = _wp_translate_php_url_constant_to_key( $component );

	if ( false !== $key && is_array( $url_parts ) && isset( $url_parts[ $key ] ) ) {
		return $url_parts[ $key ];
	} else {
		return null;
	}
}

function wp_parse_url( $url, $component = -1 ) {
	$to_unset = array();
	$url      = (string) $url;

	if ( str_starts_with( $url, '//' ) ) {
		$to_unset[] = 'scheme';
		$url        = 'placeholder:' . $url;
	} elseif ( str_starts_with( $url, '/' ) ) {
		$to_unset[] = 'scheme';
		$to_unset[] = 'host';
		$url        = 'placeholder://placeholder' . $url;
	}

	$parts = parse_url( $url );

	if ( false === $parts ) {
		// Parsing failure.
		return $parts;
	}

	// Remove the placeholder values.
	foreach ( $to_unset as $key ) {
		unset( $parts[ $key ] );
	}

	return _get_component_from_parsed_url_array( $parts, $component );
}


function flush_dynamic_cache( $hostname, $main_path, $url ) {
    // Build the request params.
    $args = array(
        'api'      => 'domain-all',
        'cmd'      => 'update',
        'settings' => array( 'json' => 1 ),
        'params'   => array(
            'flush_cache' => '1',
            'id'          => $hostname,
            'path'        => $main_path,
        ),
    );

    $site_tools_result = call_site_tools_client( $args, true );

    if ( false === $site_tools_result ) {
        return false;
    }

    if ( isset( $site_tools_result['err_code'] ) ) {
        error_log( 'There was an issue purging the cache for this URL: ' . $url . '. Error code: ' . $site_tools_result['err_code'] . '. Message: ' . $site_tools_result['message'] . '.' );
        return false;
    }

    return true;
}

function call_site_tools_client( $args, $json_object = false ) {
    file_put_contents( './site-tools.log', "call_site_tools_client" . "\n", FILE_APPEND );

    // Bail if the socket does not exists.
    if ( ! file_exists( SITE_TOOLS_SOCK_FILE ) ) {
        return false;
    }

    // Bail if no arguments present.
    if ( empty( $args ) ) {
        return false;
    }

    // Open unix socket connection.
    $fp = stream_socket_client( 'unix://' . SITE_TOOLS_SOCK_FILE, $errno, $errstr, 5 );

    // Bail if the connection fails.
    if ( false === $fp ) {
        return false;
    }

    // Build the request params.
    $request = array(
        'api'      => $args['api'],
        'cmd'      => $args['cmd'],
        'params'   => $args['params'],
        'settings' => $args['settings'],
    );

    file_put_contents( './site-tools.log', wp_json_encode($request) . "\n", FILE_APPEND );

    // Generate the json_encode flags based on passed variable.
    $flags = ( false === $json_object ) ? 0 : JSON_FORCE_OBJECT;

    // Sent the params to the Unix socket.
    fwrite( $fp, wp_json_encode( $request, $flags ) . "\n" );

    // Fetch the response.
    $response = fgets( $fp, 32 * 1024 );

    // Close the connection.
    fclose( $fp );

    // Decode the response.
    $result = @json_decode( $response, true );
    file_put_contents( './site-tools.log', wp_json_encode($result). "\n", FILE_APPEND );

    if ( false === $result || isset( $result['err_code'] ) ) {
        return false;
    }

    return $result;
}

function get_home_url($url) {
    $parsed_url = parse_url($url);

    if (!$parsed_url || !isset($parsed_url['host'])) {
        return false; // 無效 URL
    }

    // 組合 Home URL
    $home_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

    // 如果有 Port，加入 Port
    if (isset($parsed_url['port'])) {
        $home_url .= ':' . $parsed_url['port'];
    }

    return $home_url;
}

$url = isset($_GET['url']) ? trim($_GET['url'], " \t\n\r\0\x0B\"") : 'https://example.com';
$home = get_home_url($url);
$main_path = wp_parse_url($url, PHP_URL_PATH) ? wp_parse_url($url, PHP_URL_PATH) : "/";
$hostname   = str_replace( 'www.', '', wp_parse_url( $home, PHP_URL_HOST ) );
flush_dynamic_cache($hostname, $main_path, $url);