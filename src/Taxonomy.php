<?php namespace ElContraption\WpTaxonomy;

class Taxonomy {

    /**
     * Taxonomy registration name
     *
     * Lowercase, no spaces
     *
     * @var string
     */
    protected $name;

    /**
     * Name of associated post type
     *
     * @var string
     */
    protected $postType;

    /**
     * Taxonomy name: singular
     *
     * @var string
     */
    protected $singular;

    /**
     * Taxonomy name: plural
     *
     * @var string
     */
    protected $plural;

    /**
     * Taxonomy labels
     *
     * @var array
     */
    protected $labels;

    /**
     * Registration arguments
     *
     * @var array
     */
    protected $args;

    /**
     * If true, only one term per post may be selected
     *
     * @var bool
     */
    protected $single;

    /**
     * Register a new taxonomy
     *
     * @param mixed $names     Taxonomy name or array of names
     * @param string $postType Name of the associated post type
     * @param array  $args     Taxonomy registration arguments
     */
    public function __construct($names, $postType, $args = array())
    {
        // Assign registration, singular, and plural names
        $this->assignNames($names);

        // Set post type
        $this->postType = $postType;

        // Default args
        $this->args = array_replace_recursive($this->defaultArgs(), $args);

        // Enforce one term per post
        // if ($this->args['one_term_per_post'])
        // {
        //     $this->args['meta_box_cb'] = array($this, 'singleCategoryMetaBox');
        // }

        // Register the taxonomy
        if ( ! taxonomy_exists($this->name))
        {
            add_action('init', array($this, 'register'));
        }
    }

    /**
     * Register taxonomy
     */
    public function register()
    {
        $taxonomy = register_taxonomy($this->name, array($this->postType), $this->args);
    }

    /**
     * Default labels
     */
    protected function defaultLabels()
    {
        return array(
            'name'                          => _x($this->plural, 'taxonomy general name'),
            'singular_name'                 => _x($this->singular, 'taxonomy singular name'),
            'menu_name'                     => __($this->plural),
            'all_items'                     => __('All ' . $this->plural),
            'edit_item'                     => __('Edit ' . $this->singular),
            'view_item'                     => __('View ' . $this->singular),
            'update_item'                   => __('Update ' . $this->singular),
            'add_new_item'                  => __('Add New ' . $this->singular),
            'new_item_name'                 => __('New ' . $this->singular . ' Name'),
            'parent_item'                   => __('Parent ' . $this->singular),
            'parent_item_colon'             => __('Parent ' . $this->singular . ':'),
            'search_items'                  => __('Search ' . $this->plural),
            'popular_items'                 => __('Popular ' . $this->plural),
            'separate_items_with_commas'    => __('Separate ' . $this->plural . ' with commas'),
            'add_or_remove_items'           => __('Add or remove ' . $this->plural),
            'choose_from_most_used'         => __('Choose from the most used ' . $this->plural),
            'not_found'                     => __('No ' . $this->plural . ' found.')
        );
    }

    /**
     * Default args
     */
    protected function defaultArgs()
    {
        return array(
            'labels' => $this->defaultLabels(),
        );
    }

    /**
     * Assign registration, singular, and plural names
     *
     * @param mixed $names Array of names
     */
    protected function assignNames($names)
    {
        // If string, assign name
        if (is_string($names))
        {
            $this->name = str_replace('-', '_', sanitize_title($names));

            // Default to human-friendly version of $name
            $this->singular = $this->getFriendlyName($this->name);

            // Default to basic pluralization of $singular
            $this->plural = $this->singular . 's';

            return;
        }

        // If passing an array, both singluar and plural *must* be set
        if ( ! (isset($names['singular']) && isset($names['plural'])))
        {
            throw new Exception("Both 'singular' and 'plural' must be set when passing an array to new Taxonomy().");
        }

        $this->singular = $names['singular'];
        $this->plural = $names['plural'];

        // If 'name' is not set, assign from singular
        if ( ! isset($names['name']))
        {
            $this->name = sanitize_title($this->singular);
            return;
        }
        $this->name = $names['name'];
    }

    protected function getFriendlyName($name)
    {
        return ucwords(strtolower(str_replace('-', ' ', str_replace('_', ' ', $name))));
    }

    public function singleCategoryMetaBox($post, $box)
    {
        $defaults = array('taxonomy' => 'category');

        if ( ! isset($box['args']) || ! is_array($box['args']))
        {
            $args = array();
        }
        else
        {
            $args = $box['args'];
        }

        $r = wp_parse_args($args, $defaults);

        $tax_name = esc_attr($r['taxonomy']);
        $taxonomy = get_taxonomy($r['taxonomy']);

        ?>

            <div id="taxonomy-<?php echo $tax_name; ?>" class="categorydiv">

                <?php wp_dropdown_categories(array(
                    'taxonomy' => $tax_name,
                    'orderby' => 'name',
                    'order' => 'ASC',
                    'hide_empty' => false,
                    'name' => $tax_name . '[]'
                )); ?>

                <?php if ( current_user_can( $taxonomy->cap->edit_terms ) ) : ?>
        			<div id="<?php echo $tax_name; ?>-adder" class="wp-hidden-children">
        				<h4>
        					<a id="<?php echo $tax_name; ?>-add-toggle" href="#<?php echo $tax_name; ?>-add" class="hide-if-no-js">
        						<?php
        							/* translators: %s: add new taxonomy label */
        							printf( __( '+ %s' ), $taxonomy->labels->add_new_item );
        						?>
        					</a>
        				</h4>
        				<p id="<?php echo $tax_name; ?>-add" class="category-add wp-hidden-child">
        					<label class="screen-reader-text" for="new<?php echo $tax_name; ?>"><?php echo $taxonomy->labels->add_new_item; ?></label>
        					<input type="text" name="new<?php echo $tax_name; ?>" id="new<?php echo $tax_name; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $taxonomy->labels->new_item_name ); ?>" aria-required="true"/>
        					<label class="screen-reader-text" for="new<?php echo $tax_name; ?>_parent">
        						<?php echo $taxonomy->labels->parent_item_colon; ?>
        					</label>
        					<?php wp_dropdown_categories( array( 'taxonomy' => $tax_name, 'hide_empty' => 0, 'name' => 'new' . $tax_name . '_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $taxonomy->labels->parent_item . ' &mdash;' ) ); ?>
        					<input type="button" id="<?php echo $tax_name; ?>-add-submit" data-wp-lists="add:<?php echo $tax_name; ?>checklist:<?php echo $tax_name; ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $taxonomy->labels->add_new_item ); ?>" />
        					<?php wp_nonce_field( 'add-' . $tax_name, '_ajax_nonce-add-' . $tax_name, false ); ?>
        					<span id="<?php echo $tax_name; ?>-ajax-response"></span>
        				</p>
        			</div>
        		<?php endif; ?>

            </div>


        <?php
    }
}
