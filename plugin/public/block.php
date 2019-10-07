<?php
namespace Sgdd\Pub\Block;

if ( ! is_admin() ) {
	return;
}

function register() {
	if ( function_exists( 'register_block_type' ) ) {
		add_action( 'init', '\\Sgdd\\Pub\\Block\\add_block' );
	}

	add_action( 'wp_ajax_setPermissions', '\\Sgdd\\Pub\\Block\\ajax_handler' );
}

function add_block() {
	\Sgdd\enqueue_script( 'sgdd_block_js', '/public/js/block.js', [ 'wp-blocks', 'wp-components', 'wp-editor', 'wp-element', 'wp-i18n', 'sgdd_file_selection_js' ] );
	\Sgdd\enqueue_script( 'sgdd_file_selection_js', '/public/js/file-selection.js', [ 'wp-components', 'wp-element', 'sgdd_inspector_js', 'sgdd_settings_base_js' ] );
	\Sgdd\enqueue_script( 'sgdd_inspector_js', '/public/js/inspector.js', [ 'wp-element', 'sgdd_integer_settings_js', 'sgdd_select_settings_js', 'sgdd_button_settings_js' ] );
	\Sgdd\enqueue_script( 'sgdd_settings_base_js', '/public/js/settings-base.js', [ 'wp-element' ] );
	\Sgdd\enqueue_script( 'sgdd_integer_settings_js', '/public/js/integer-setting.js', [ 'wp-element', 'sgdd_settings_base_js' ] );
	\Sgdd\enqueue_script( 'sgdd_select_settings_js', '/public/js/select-setting.js', [ 'wp-element', 'sgdd_settings_base_js' ] );
	\Sgdd\enqueue_script( 'sgdd_button_settings_js', '/public/js/button-setting.js', [ 'wp-element' ] );

	wp_localize_script(
		'sgdd_block_js',
		'sgddBlockJsLocalize',
		[
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'sgdd_block_js' ),
			'noncePerm'        => wp_create_nonce( 'sgdd_block_js_permissions' ),
			'blockName'        => esc_html__( 'Google Drive Documents', 'skaut-google-drive-documents' ),
			'blockDescription' => esc_html__( 'Embed your files from Google Drive', 'skaut-google-drive-documents' ),
			'root'             => esc_html__( 'Google Drive', 'skaut-google-drive-documents' ),
			'rootPath'         => \Sgdd\Admin\Options\Options::$root_path->get(),
			'embedWidth'       => [ esc_html__( 'Width', 'skaut-google-drive-documents' ), \Sgdd\Admin\Options\Options::$embed_width->get() ],
			'embedHeight'      => [ esc_html__( 'Height', 'skaut-google-drive-documents' ), \Sgdd\Admin\Options\Options::$embed_height->get() ],
			'list'             => esc_html__( 'List', 'skaut-google-drive-documents' ),
			'grid'             => esc_html__( 'Grid', 'skaut-google-drive-documents' ),
			'folderType'       => [ esc_html__( 'List folder as', 'skaut-google-drive-documents' ), \Sgdd\Admin\Options\Options::$folder_type->get() ],
			'listWidth'        => [ esc_html__( 'Width', 'skaut-google-drive-documents' ), \Sgdd\Admin\Options\Options::$list_width->get() ],
			'gridCols'         => [ esc_html__( 'Grid columns', 'skaut-google-drive-documents' ), \Sgdd\Admin\Options\Options::$grid_cols->get() ],
			'test'             => esc_html__( 'Set permissions' ),
		]
	);

	\Sgdd\enqueue_style( 'sgdd_block', '/public/css/block.css' );
	register_block_type(
		'skaut-google-drive-documents/block',
		[
			'editor_script'   => 'sgdd_block_js',
			'render_callback' => '\\Sgdd\\Pub\\Block\\display',
		]
	);
}

function display( $attr ) {
	if ( isset( $attr['folderType'] ) || !isset( $attr['fileId'] ) ) {
		//display folder
		$folderId;
		$folderType;
		$content;
		$width;
		$cols;
		$result;

		//set folderId variable
		if ( isset( $attr['folderId'] ) ) {
			$folderId = $attr['folderId'];
		} else {
			$root_path_array = \Sgdd\Admin\Options\Options::$root_path->get();
			$folderId = end( $root_path_array );
		}

		if ( isset( $attr['folderType'] ) ) {
			$folderType = $attr['folderType'];
		} else {
			$folderType = \Sgdd\Admin\Options\Options::$folder_type->get();
		}

		//gdrive request to fetch content of folder
		try {
			$content = fetch_folder_content( $folderId );
		} catch ( \Exception $e ) {
			return '<div class="notice notice-error">Error while fetching folder content! <br> ' . $e->getErrors()[0]['message'] . '</div>';
		}

		if ( 'list' === $folderType ) {
			//display list
			if ( isset( $attr['listWidth'] ) ) {
				$result = build_result( $content, 'list', array( 'width' => $attr['listWidth'] ) );
			} else {
				$result = build_result( $content, 'list', array() );
			}	
		} else {
			//display grid
			if ( isset( $attr['gridCols'] ) ) {
				$cols = $attr['gridCols'];
			} else {
				$cols = \Sgdd\Admin\Options\Options::$grid_cols->get();
			}

			if ( isset( $attr['listWidth'] ) ) {
				$result = build_result( $content, 'grid', array( 'width' => $attr['listWidth'], 'cols' => $cols ) );
			} else {
				$result = build_result( $content, 'grid', array( 'cols' => $cols ) );
			}
		}

		$result .= '</tbody>
								</table>';

		return $result;
	} else {
		//display file
		$size = '';
		$id = $attr['fileId'];

		try {
			$temp = set_file_permissions( $id );
		} catch ( \Exception $e ) {
			return '<div class="notice notice-error">Error while setting permissions! <br> ' . $e->getErrors()[0]['message'] . '</div>';
		}

		if ( isset( $attr['embedWidth'] ) ) {
			$size .= 'width:' . $attr['embedWidth'] . 'px; ';
		}

		if ( isset( $attr['embedHeight'] ) ) {
			$size .= 'height:' . $attr['embedHeight'] . 'px; ';
		}

		$result = '<iframe src="https://drive.google.com/file/d/' . $id . '/preview" style="' . $size . 'border:0;"></iframe>';

		return $result;
	}
}

/**
 * Handles ajax call from JS
 */
function ajax_handler() {
	try {
		set_permissions();
	} catch ( \Sgdd\Vendor\Google_Service_Exception $e ) {
		if ( 'userRateLimitExceeded' === $e->getErrors()[0]['reason'] ) {
			wp_send_json( [ 'error' => esc_html__( 'The maximum number of requests has been exceeded. Please try again in a minute.', 'skaut-google-drive-documents' ) ] );
		} else {
			wp_send_json( [ 'error' => $e->getErrors()[0]['message'] ] );
		}
	} catch ( \Exception $e ) {
		wp_send_json( [ 'error' => $e->getMessage() ] );
	}
}

function set_permissions() {
	check_ajax_referer( 'sgdd_block_js_permissions' );

	if ( $_GET[ 'folderType' ] != '' || $_GET[ 'fileId' ] == '' ) {
		set_permissions_in_folder( $_GET[ 'folderId' ] );
	} else {
		set_file_permissions( $_GET[ 'fileId' ] );
	}
}

function set_file_permissions( $fileId ) {
	$service = \Sgdd\Admin\GoogleAPILib\get_drive_client();
	$domain_permission = new \Sgdd\Vendor\Google_Service_Drive_Permission(
		[
			'role' => 'reader',
			'type' => 'anyone',
		]
	);
	$request = $service->permissions->create( $fileId, $domain_permission, [ 'supportsTeamDrives' => true ] );
}

function set_permissions_in_folder( $folderId ) {
	$service = \Sgdd\Admin\GoogleAPILib\get_drive_client();
	$page_token = null;

	do {
		$response = $service->files->listFiles(
			array(
				'q'                         => "'" . $folderId . "' in parents",
				'supportsAllDrives'         => true,
				'includeItemsFromAllDrives' => true,
				'pageToken'                 => $page_token,
				'pageSize'                  => 1000,
				'fields'                    => 'files',
			)
		);

		if ( $response instanceof \Sgdd\Vendor\Google_Service_Exception ) {
			return $response;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$page_token = $response->pageToken;
	} while ( null !== $page_token );

	$service = \Sgdd\Admin\GoogleAPILib\get_drive_client();
	$userPermission = new \Sgdd\Vendor\Google_Service_Drive_Permission(
		[
			'role' => 'reader',
			'type' => 'anyone',
		]
	);

	$index = 0;

	$service->getClient()->setUseBatch( true );
	$batch = $service->createBatch();		
	
	foreach($response as $file) {
		$request = $service->permissions->create($file['id'], $userPermission, [ 'supportsTeamDrives' => true ] );
		$batch->add($request, 'perm'.$index);

		$index++;
	}

	//catch errors!!!
	$results = $batch->execute();
	return $results;
}

function fetch_folder_content( $folderId ) {
	$service = \Sgdd\Admin\GoogleAPILib\get_drive_client();
	$page_token = null;

	do {
		$response = $service->files->listFiles(
			array(
				'q'                         => "'" . $folderId . "' in parents",
				'supportsAllDrives'         => true,
				'includeItemsFromAllDrives' => true,
				'pageToken'                 => $page_token,
				'pageSize'                  => 1000,
				'fields'                    => 'files',
			)
		);

		if ( $response instanceof \Sgdd\Vendor\Google_Service_Exception ) {
			throw $response;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$page_token = $response->pageToken;
	} while ( null !== $page_token );

	return $response;
}

function build_result( $content, $type, $arg ) {
	$result;

	if ( empty( $content['files'] ) ) {
		return 'Vybraný priečinok neobsahuje žiadne položky!';
	}

	if ( $type === 'list' ) {
		//build list table
		if ( !empty( $arg ) ) {
			$result = '<table style="width:' . $arg['width'] . 'px"><tbody>';
		} else {
			$result = '<table><tbody>';
		}

		foreach ( $content as $element ) {
			$result .= '<tr>
				<td><img src="' . $element['iconLink'] . '"></td>
				<td><a href="' . $element['webViewLink'] . '" target="_blank">' . $element['name'] . '</a></td>
			</tr>';
		}

		return $result;
	} else {
		//build grid table
		$i = 0;
		$width;
		$cols = $arg['cols'];

		if ( array_key_exists( 'width', $arg ) ) {
			$result = '<table style="table-layout:fixed; border-collapse:separate; width:' . $arg['width'] . 'px"><tbody>';
		} else {
			$result = '<table style="table-layout:fixed; border-collapse:separate;"><tbody>';
		}

		foreach ( $content as $element ) {
			$i % $cols == 0 ? $result .= '<tr>' : $result .= '';

			if ( !$element['hasThumbnail'] || preg_match( '/\b(google-apps)/', $element['mimeType'] ) ){
				$result .= '<td><div class="element"><a href="' . $element['webViewLink'] . '" target="_blank"><div class="image"><img src="' . preg_replace('(16)', '128',$element['iconLink']) . '"></div><div class="caption">' . $element['name'] . '</div></a></div></td>';
			} else {
				$result .= '<td><div class="element"><a href="' . $element['webViewLink'] . '" target="_blank"><div class="image"><img src="' . $element['thumbnailLink'] . '"></div><div class="caption">' . $element['name'] . '</div></a></div></td>';
			}
			$i % $cols  == $cols - 1 ? $result .= '</tr>' : $result .= '';
			$i++;
		}

		return $result;
	}
}