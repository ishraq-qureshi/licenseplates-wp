<?php

class WC_Product_LPTVplate extends WC_Product_Simple
{

	/**
	 * Return the product type
	 * @return string
	 */
	public function get_type()
	{
		return 'lptvplate';
	}

	/**
	 * Generate image on archive page
	 *
	 * @param string $size
	 * @param array $attr
	 * @param bool $placeholder
	 *
	 * @return string
	 */
	public function get_image($size = 'woocommerce_thumbnail', $attr = array(), $placeholder = true)
	{
		$modelId = $this->get_meta('_plate_template_id', true);
		$imageFilename = $this->get_meta('_plate_products_image', true);
		if ($imageFilename) {
			$src = home_url('/wp-content/plugins/lptv-plates/images/' . $imageFilename);
			return '<img src="' . esc_url($src) . '" alt="' . esc_attr($this->get_title()) . '"/>';
		}

		return parent::get_image($size, $attr, $placeholder);
	}

	/**
	 * Replace featured image with plate image
	 */
	public function get_image_id($context = 'view')
	{

		$imageFilename = $this->get_meta('_plate_products_image', true);
		if ($imageFilename) {
			$src = '/wp-content/plugins/lptv-plates/images/' . $imageFilename;
			return $src;
		} 

		return parent::get_image_id($context);
	}

	/**
	 * Get image url
	 */
	public function get_lptv_image_url()
	{
		$modelId = $this->get_meta('_plate_template_id', true);
		$imageFilename = $this->get_meta('_plate_products_image', true);
		if ($imageFilename) {
			$src =  get_bloginfo('url') . '/wp-content/plugins/lptv-plates/images/' . $imageFilename;
			return $src;
		}

		return $this->get_image_id();
	}
}
