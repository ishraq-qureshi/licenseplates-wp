<?php

namespace Wpai\WordPress;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class AdminNotice
 * @package Wpai\WordPress
 */
abstract class AdminNotice {

	/**
	 * @var
	 */
	protected $message;

	/**
	 * AdminNotice constructor.
	 * @param $message
	 */
	public function __construct($message) {
		$this->message = $message;
	}

	/**
	 *
	 */
	public function showNotice() {
		?>
		<div class="<?php echo esc_attr($this->getType());?>"><p>
				<?php echo wp_kses_post($this->message); ?>
			</p></div>
		<?php
	}

	/**
	 *
	 */
	public function render() {
		add_action('admin_notices', array($this, 'showNotice'));
	}

	/**
	 * @return mixed
	 */
	abstract function getType();
}