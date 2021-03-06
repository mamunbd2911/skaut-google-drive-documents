<?php
/**
 * PathField class
 *
 * @package SGDD
 * @since 1.0.0
 */
namespace Sgdd\Admin\Options\OptionTypes;

require_once __DIR__ . '/class-settingfield.php';

/**
 * An option containing root folder id.
 *
 * @see SettingField
 */
class PathField extends SettingField {
	/**
	 * Register option into WordPress.
	 */
	public function register() {
		register_setting(
			$this->page,
			$this->id,
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => $this->default_value,
			]
		);
	}

	/**
	 * Sanitize the input.
	 *
	 * @param $value The unsanitized input.
	 * @return int Sanitized value.
	 */
	public function sanitize( $value ) {
		if ( is_string( $value ) ) {
			$value = json_decode( $value, true );
		}

		if ( null === $value ) {
			$value = $this->default_value;
		}

		return $value;
	}

	/**
	 * Display field for updating the option
	 */
	public function display() {
		echo "<input id='" . esc_attr( $this->id ) . "' type='hidden' name='" . esc_attr( $this->id ) . "' value='" . esc_attr( wp_json_encode( $this->get(), JSON_UNESCAPED_UNICODE ) ) . "'>";
	}
}
