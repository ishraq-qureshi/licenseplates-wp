<?php
/**
 * Copyright (C) 2014-2020 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

class Ai1wmle_Import_Url {

	public static function execute( $params, Ai1wmle_URL_Client $url = null ) {

		// Set progress
		Ai1wm_Status::info( __( 'Connecting to URL...', AI1WMLE_PLUGIN_NAME ) );

		// Create empty archive file
		$archive = new Ai1wm_Compressor( ai1wm_archive_path( $params ) );
		$archive->close();

		// Set URL client
		if ( is_null( $url ) ) {
			$url = new Ai1wmle_URL_Client( $params['file_url'] );
		}

		$url->set_file_url( $url->fetch_effective_url() );

		// Rewrite URLs
		switch ( 1 ) {
			case preg_match( '/(eapi\.pcloud\.com|api\.pcloud\.com)$/', $url->get_hostname() ):
				$url->set_file_url( $url->get_pcloud_direct_link() );
				break;
		}

		// Set file URL
		$params['file_url'] = $url->get_file_url();

		// Set file size
		$params['file_size'] = $url->get_file_size();

		// Set file ranges
		$params['file_ranges'] = $url->get_file_ranges();

		// Set progress
		Ai1wm_Status::info( __( 'Done connecting to URL.', AI1WMLE_PLUGIN_NAME ) );

		return $params;
	}
}
