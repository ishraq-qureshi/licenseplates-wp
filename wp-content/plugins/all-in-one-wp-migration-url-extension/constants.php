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

// ==================
// = Plugin Version =
// ==================
define( 'AI1WMLE_VERSION', '2.81' );

// ===============
// = Plugin Name =
// ===============
define( 'AI1WMLE_PLUGIN_NAME', 'all-in-one-wp-migration-url-extension' );

// ============
// = Lib Path =
// ============
define( 'AI1WMLE_LIB_PATH', AI1WMLE_PATH . DIRECTORY_SEPARATOR . 'lib' );

// ===================
// = Controller Path =
// ===================
define( 'AI1WMLE_CONTROLLER_PATH', AI1WMLE_LIB_PATH . DIRECTORY_SEPARATOR . 'controller' );

// ==============
// = Model Path =
// ==============
define( 'AI1WMLE_MODEL_PATH', AI1WMLE_LIB_PATH . DIRECTORY_SEPARATOR . 'model' );

// ===============
// = Import Path =
// ===============
define( 'AI1WMLE_IMPORT_PATH', AI1WMLE_MODEL_PATH . DIRECTORY_SEPARATOR . 'import' );

// =============
// = View Path =
// =============
define( 'AI1WMLE_TEMPLATES_PATH', AI1WMLE_LIB_PATH . DIRECTORY_SEPARATOR . 'view' );

// ===============
// = Vendor Path =
// ===============
define( 'AI1WMLE_VENDOR_PATH', AI1WMLE_LIB_PATH . DIRECTORY_SEPARATOR . 'vendor' );

// ===================
// = File Chunk Size =
// ===================
define( 'AI1WMLE_DEFAULT_FILE_CHUNK_SIZE', 5 * 1024 * 1024 );

// ===============================
// = Minimal Base Plugin Version =
// ===============================
define( 'AI1WMLE_MIN_AI1WM_VERSION', '7.103' );

// =============================
// = URL Process Download URL =
// =============================
define( 'AI1WMLE_URL_PROCESS_DOWNLOAD', 'https://redirect.wp-migration.com/v1/url/process' );

// ===============
// = Purchase ID =
// ===============
define( 'AI1WMLE_PURCHASE_ID', '56c57c8a-76d9-48fe-b28f-d904f7ba02db' );
