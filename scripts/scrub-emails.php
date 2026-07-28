<?php
/**
 * One-off local-only script: replace every real email address in the DB
 * with a deterministic dummy @yopmail.com address, so no real
 * customer/user/admin can ever receive mail from this local copy.
 *
 * Run via: docker compose exec wpcli wp --path=/var/www/html --allow-root eval-file scripts/scrub-emails.php
 */

global $wpdb;

$dry_run = isset( $args ) && in_array( 'dry-run', $args, true );

$email_regex = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';

$skip_domain_suffixes = [
	'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'css', 'js', 'woff', 'woff2',
	'ttf', 'eot', 'otf', 'pdf', 'zip', 'mp4', 'mp3', 'json', 'xml', 'php',
];

function scrub_email_value( $email ) {
	static $cache = [];
	if ( ! is_string( $email ) ) {
		return $email;
	}
	$email = trim( $email );
	if ( $email === '' || stripos( $email, '@yopmail.com' ) !== false ) {
		return $email;
	}
	if ( isset( $cache[ $email ] ) ) {
		return $cache[ $email ];
	}
	$local = substr( md5( strtolower( $email ) ), 0, 12 );
	return $cache[ $email ] = $local . '@yopmail.com';
}

function looks_like_file_not_email( $match, $skip_domain_suffixes ) {
	$at_pos = strrpos( $match, '@' );
	$domain = substr( $match, $at_pos + 1 );
	$ext    = strtolower( substr( $domain, strrpos( $domain, '.' ) + 1 ) );
	return in_array( $ext, $skip_domain_suffixes, true );
}

function scrub_text_blob( $value, $email_regex, $skip_domain_suffixes, &$count ) {
	return preg_replace_callback( $email_regex, function ( $m ) use ( $skip_domain_suffixes, &$count ) {
		if ( looks_like_file_not_email( $m[0], $skip_domain_suffixes ) ) {
			return $m[0];
		}
		$count++;
		return scrub_email_value( $m[0] );
	}, $value );
}

function scrub_maybe_serialized( $value, $email_regex, $skip_domain_suffixes, &$count ) {
	$unserialized = @unserialize( $value );
	if ( $unserialized === false && $value !== 'b:0;' ) {
		// not serialized, treat as plain text
		return scrub_text_blob( $value, $email_regex, $skip_domain_suffixes, $count );
	}
	$walked = walk_and_scrub( $unserialized, $email_regex, $skip_domain_suffixes, $count );
	return serialize( $walked );
}

function walk_and_scrub( $data, $email_regex, $skip_domain_suffixes, &$count ) {
	if ( is_array( $data ) ) {
		foreach ( $data as $k => $v ) {
			$data[ $k ] = walk_and_scrub( $v, $email_regex, $skip_domain_suffixes, $count );
		}
		return $data;
	}
	if ( is_object( $data ) ) {
		foreach ( $data as $k => $v ) {
			$data->$k = walk_and_scrub( $v, $email_regex, $skip_domain_suffixes, $count );
		}
		return $data;
	}
	if ( is_string( $data ) ) {
		return scrub_text_blob( $data, $email_regex, $skip_domain_suffixes, $count );
	}
	return $data;
}

$total = 0;

// 1. Direct scalar columns -------------------------------------------------

$scalar_targets = [
	[ 'table' => $wpdb->prefix . 'users', 'col' => 'user_email', 'pk' => 'ID' ],
	[ 'table' => $wpdb->prefix . 'comments', 'col' => 'comment_author_email', 'pk' => 'comment_ID' ],
	[ 'table' => $wpdb->prefix . 'wc_customer_lookup', 'col' => 'email', 'pk' => 'customer_id' ],
	[ 'table' => $wpdb->prefix . 'wc_order_addresses', 'col' => 'email', 'pk' => 'id' ],
	[ 'table' => $wpdb->prefix . 'wc_orders', 'col' => 'billing_email', 'pk' => 'id' ],
];

foreach ( $scalar_targets as $t ) {
	$rows = $wpdb->get_results( "SELECT {$t['pk']} AS pk, {$t['col']} AS val FROM {$t['table']} WHERE {$t['col']} IS NOT NULL AND {$t['col']} != '' AND {$t['col']} NOT LIKE '%@yopmail.com'" );
	$n = 0;
	foreach ( $rows as $row ) {
		$new = scrub_email_value( $row->val );
		if ( $new !== $row->val ) {
			$n++;
			if ( ! $dry_run ) {
				$wpdb->update( $t['table'], [ $t['col'] => $new ], [ $t['pk'] => $row->pk ] );
			}
		}
	}
	echo "{$t['table']}.{$t['col']}: {$n} rows" . ( $dry_run ? ' (dry-run)' : '' ) . "\n";
	$total += $n;
}

// usermeta: billing_email / shipping_email
$rows = $wpdb->get_results( "SELECT umeta_id, meta_value AS val FROM {$wpdb->usermeta} WHERE meta_key IN ('billing_email','shipping_email') AND meta_value != '' AND meta_value NOT LIKE '%@yopmail.com'" );
$n = 0;
foreach ( $rows as $row ) {
	$new = scrub_email_value( $row->val );
	if ( $new !== $row->val ) {
		$n++;
		if ( ! $dry_run ) {
			$wpdb->update( $wpdb->usermeta, [ 'meta_value' => $new ], [ 'umeta_id' => $row->umeta_id ] );
		}
	}
}
echo "{$wpdb->usermeta} (billing_email/shipping_email): {$n} rows" . ( $dry_run ? ' (dry-run)' : '' ) . "\n";
$total += $n;

// wc_orders_meta: known email-bearing keys
$order_meta_keys = [ '_billing_email', '_ppcp_paypal_payer_email', '_ppcp_paypal_contact_email', '_ppcp_paypal_billing_email' ];
$placeholders = implode( ',', array_fill( 0, count( $order_meta_keys ), '%s' ) );
$rows = $wpdb->get_results( $wpdb->prepare(
	"SELECT id, meta_value AS val FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key IN ($placeholders) AND meta_value != '' AND meta_value NOT LIKE '%%@yopmail.com'",
	$order_meta_keys
) );
$n = 0;
foreach ( $rows as $row ) {
	$new = scrub_email_value( $row->val );
	if ( $new !== $row->val ) {
		$n++;
		if ( ! $dry_run ) {
			$wpdb->update( $wpdb->prefix . 'wc_orders_meta', [ 'meta_value' => $new ], [ 'id' => $row->id ] );
		}
	}
}
echo "{$wpdb->prefix}wc_orders_meta (email keys): {$n} rows" . ( $dry_run ? ' (dry-run)' : '' ) . "\n";
$total += $n;

// 2. Generic sweep: postmeta / commentmeta rows containing '@' -------------

foreach ( [ 'postmeta' => [ 'pk' => 'meta_id', 'table' => $wpdb->postmeta ], 'commentmeta' => [ 'pk' => 'meta_id', 'table' => $wpdb->commentmeta ] ] as $label => $info ) {
	$rows = $wpdb->get_results( "SELECT {$info['pk']} AS pk, meta_value AS val FROM {$info['table']} WHERE meta_value LIKE '%@%.%'" );
	$n = 0;
	foreach ( $rows as $row ) {
		$match_count = 0;
		$new = scrub_maybe_serialized( $row->val, $email_regex, $skip_domain_suffixes, $match_count );
		if ( $match_count > 0 && $new !== $row->val ) {
			$n += $match_count;
			if ( ! $dry_run ) {
				$wpdb->update( $info['table'], [ 'meta_value' => $new ], [ $info['pk'] => $row->pk ] );
			}
		}
	}
	echo "{$label} (generic sweep): {$n} email matches" . ( $dry_run ? ' (dry-run)' : '' ) . "\n";
	$total += $n;
}

// 3. wp_options: admin_email (special-cased) + generic sweep --------------

$admin_email = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'admin_email'" );
if ( $admin_email && stripos( $admin_email, '@yopmail.com' ) === false ) {
	$new = scrub_email_value( $admin_email );
	echo "admin_email: {$admin_email} -> {$new}" . ( $dry_run ? ' (dry-run)' : '' ) . "\n";
	if ( ! $dry_run ) {
		$wpdb->update( $wpdb->options, [ 'option_value' => $new ], [ 'option_name' => 'admin_email' ] );
	}
	$total++;
}

$skip_options = [ 'admin_email', 'easy_wp_smtp' ];
$option_placeholders = implode( ',', array_fill( 0, count( $skip_options ), '%s' ) );
$rows = $wpdb->get_results( $wpdb->prepare(
	"SELECT option_name, option_value AS val FROM {$wpdb->options} WHERE option_value LIKE '%%@%%.%%' AND option_name NOT IN ($option_placeholders)",
	$skip_options
) );
$n = 0;
foreach ( $rows as $row ) {
	$match_count = 0;
	$new = scrub_maybe_serialized( $row->val, $email_regex, $skip_domain_suffixes, $match_count );
	if ( $match_count > 0 && $new !== $row->val ) {
		$n += $match_count;
		if ( ! $dry_run ) {
			$wpdb->update( $wpdb->options, [ 'option_value' => $new ], [ 'option_name' => $row->option_name ] );
		}
	}
}
echo "wp_options (generic sweep): {$n} email matches" . ( $dry_run ? ' (dry-run)' : '' ) . "\n";
$total += $n;

echo "\nTOTAL replacements: {$total}" . ( $dry_run ? ' (dry-run, nothing written)' : '' ) . "\n";
