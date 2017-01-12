<?php
/*
Plugin Name: Evolution Image
Plugin URI: https://github.com/obiPlabon/evolution-image/
Description: Show the evolution of something through image. It is superb easy to use.
Version: 0.0.7
Author: Obi Plabon
Author URI: http://obiPlabon.im/
License: GPLv2 or later
Text Domain: evolution-image
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright http://obiPlabon.im/
*/

if ( ! defined( 'ABSPATH') ) {
    die;
}

/**
 * Check if the class already exists!
 * Not sure, what if someone already used the same name for his/her project.
 * You know, it is tiny world ;)
 */
if ( ! class_exists( 'Evolution_Image' ) ) :

class Evolution_Image {

    /**
     * Evolution image plugin version number
     * @var string
     */
    private $version = '0.0.7';

    /**
     * Evolution image shortcode tag
     * @var string
     */
    private $shortcode_tag = 'evolution_image';

    /**
     * Consturctor will setup everyting. It is the initializer :D
     */
    public function __construct() {
        // Enqueue dependency assets
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_assets') );

        // Map evolution image shortcode params to Visual Composer
        add_action( 'vc_before_init', array($this, 'vc_inject') );

        // Register shortcode
        add_shortcode( $this->shortcode_tag, array($this, 'render') );

        // Store assets URL, it is convenient
        $this->assets_url = trailingslashit( plugin_dir_url( __FILE__ ) . 'assets' );
    }

    /**
     * Get all the registered image sizes and remap to use
     * as Visual Composer param value
     * @return array Remaped image sizes
     */
    protected function get_image_sizes() {
        $sizes = get_intermediate_image_sizes();
        $map = array();
        for ( $i = 0, $len = count($sizes); $i < $len; $i++ ) {
            $map[ucwords( str_replace( array('-', '_'), ' ', $sizes[$i] ) )] = $sizes[$i];
        }
        return $map;
    }

    /**
     * Map shortcode params to Visual Composer
     * So that Visual Composer create an easy to use UI
     * @return void
     */
    public function vc_inject() {
        vc_map( array(
            'name' => esc_html__( 'Evolution Image', 'evolution-image' ),
            'description' => esc_html__( 'Easily visualize the transition between two image.', 'evolution-image' ),
            'base' => $this->shortcode_tag,
            'params' => array(
                array(
                    'heading' => esc_html__( 'Before Evolution', 'evolution-image' ),
                    'description' => esc_html__( 'This image represent the state of before evolution. Add an image that has be captured before evolution!', 'evolution-image' ),
                    'type' => 'attach_image',
                    'param_name' => 'before',
                    ),
                array(
                    'heading' => esc_html__( 'After Evolution', 'evolution-image' ),
                    'description' => esc_html__( 'This image represent the state of after evolution. Add an image that has be captured after evolution!', 'evolution-image' ),
                    'type' => 'attach_image',
                    'param_name' => 'after',
                    ),
                array(
                    'heading' => esc_html__( 'Size', 'evolution-image' ),
                    'description' => esc_html__( 'Select an image size that is suitable for your need. For better output make sure your selected image size is hard cropped or the before and after images have same size.', 'evolution-image' ),
                    'type' => 'dropdown',
                    'param_name' => 'size',
                    'value' => $this->get_image_sizes(),
                    'admin_label' => true
                    ),
                array(
                    'heading' => esc_html__( 'Orientation', 'evolution-image' ),
                    'description' => esc_html__( 'Select an orientation style. There are only two options and most people loves the horizontal.', 'evolution-image' ),
                    'type' => 'dropdown',
                    'param_name' => 'orientation',
                    'value' => array(
                        esc_html__( 'Horizontal', 'evolution-image' ) => 'horizontal',
                        esc_html__( 'Vertical', 'evolution-image' ) => 'vertical',
                        ),
                    'admin_label' => true
                    ),
                )
            )
        );
    }

    /**
     * Enqueue dependency assets
     * @return void
     */
    public function enqueue_assets() {
        wp_enqueue_style( 'twentytwenty',
            $this->assets_url . 'vendor/css/twentytwenty.css',
            array(),
            $this->version
            );

        wp_enqueue_script(
            'jquery-event-move',
            $this->assets_url . 'vendor/js/jquery.event.move.js',
            array('jquery'),
            $this->version,
            true
            );

        wp_enqueue_script(
            'jquery-twentytwenty',
            $this->assets_url . 'vendor/js/jquery.twentytwenty.js',
            array('jquery'),
            $this->version,
            true
            );

        wp_add_inline_script(
            'jquery-twentytwenty',
            ';(function($){'
                . '$(".evolution-image[data-orientation=\"horizontal\"]").twentytwenty({default_offset_pct: 0.7});'
                . '$(".evolution-image[data-orientation=\"vertical\"]").twentytwenty({default_offset_pct: 0.7, orientation: \'vertical\'});'
            . '}(jQuery));'
            );
    }

    /**
     * Render shortcode html output.
     * @param  array  $atts    Shortcode attributes.
     * @param  string $content 
     * @return string          Shortcode output.
     */
    public function render( $atts, $content = null ) {
        $atts = shortcode_atts( array(
            'before' => 0,
            'after' => 0,
            'size' => 'medium', // For best output make sure image is hard cropped
            'orientation' => 'horizontal' // Only two available - horizontal & vertical
            ), $atts );

        $before = wp_get_attachment_image_url( absint( $atts['before'] ), $atts['size'] );
        $after = wp_get_attachment_image_url( absint( $atts['after'] ), $atts['size'] );

        return sprintf(
            '<div class="evolution-image" data-orientation="%1$s">'
                . '<img src="%2$s" alt="%3$s">'
                . '<img src="%4$s" alt="%5$s">'
            . '</div>',
            esc_attr( $atts['orientation'] ),
            esc_url( $before ),
            esc_attr_x( 'Before Evolution', 'Before Evolution', 'evolution-image' ),
            esc_url( $after ),
            esc_attr_x( 'After Evolution', 'After Evolution', 'evolution-image' )
        );
    }

}

new Evolution_Image;

endif;