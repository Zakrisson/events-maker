<?php
if(!defined('ABSPATH')) exit;

new Events_Maker_Settings($events_maker);

class Events_Maker_Settings
{
	private $capabilities = array();
	private $defaults = array();
	private $pages = array();
	private $errors = array();
	private $options = array();
	private $orders = array();
	private $sortings = array();
	private $supports = array();
	private $calendar_displays = array();
	private $calendar_contents = array();
	private $tabs = array();
	private $currencies = array();
	private $events_maker;
	private $transient_id = '';


	public function __construct($events_maker)
	{
		// passed vars
		$this->events_maker = $events_maker;
		$this->options = $events_maker->get_options();
		$this->defaults = $events_maker->get_defaults();
		$this->transient_id = $events_maker->get_session_id();

		//actions
		add_action('init', array(&$this, 'update_nav_menu'));
		add_action('admin_menu', array(&$this, 'settings_page'));
		add_action('admin_init', array(&$this, 'register_settings'));
		add_action('after_setup_theme', array(&$this, 'set_currencies'));
		add_action('after_setup_theme', array(&$this, 'load_defaults'));

		//filters
		add_filter('plugin_action_links', array(&$this, 'plugin_settings_link'), 10, 2);
	}


	/**
	 * 
	*/
	public function set_currencies()
	{
		$this->currencies = $this->events_maker->get_currencies();
	}


	/**
	 * 
	*/
	public function update_nav_menu()
	{
		if($this->options['general']['rewrite_rules'] === TRUE && $this->options['general']['event_nav_menu']['show'] === TRUE)
		{
			$this->update_menu($this->options['general']['event_nav_menu']['menu_id'], $this->options['general']['event_nav_menu']['menu_name']);
		}
	}


	/**
	 * 
	*/
	public function load_defaults()
	{
		$this->sortings = array(
			'publish' => __('Publish date', 'events-maker'),
			'start' => __('Events start date', 'events-maker'),
			'end' => __('Events end date', 'events-maker')
		);

		$this->orders = array(
			'asc' => __('Ascending', 'events-maker'),
			'desc' => __('Descending', 'events-maker')
		);

		$this->calendar_displays = array(
			'page' => __('selected page', 'events-maker'),
			'manual' => __('manually', 'events-maker')
		);

		$this->calendar_contents = array(
			'before' => __('before the content', 'events-maker'),
			'after' => __('after the content', 'events-maker')
		);

		$this->pages = get_pages(
			array(
				'sort_column' => 'post_title',
				'sort_order' => 'asc',
				'number' => 0
			)
		);

		$this->supports = array(
			'title' => __('title', 'events-maker'),
			'editor' => __('editor', 'events-maker'),
			'author' => __('author', 'events-maker'),
			'thumbnail' => __('thumbnail', 'events-maker'),
			'excerpt' => __('excerpt', 'events-maker'),
			'custom-fields' => __('custom fields', 'events-maker'),
			'comments' => __('comments', 'events-maker'),
			'trackbacks' => __('trackbacks', 'events-maker'),
			'revisions' => __('revisions', 'events-maker')
		);

		$this->errors = array(
			'settings_gene_saved' => __('General settings saved.', 'events-maker'),
			'settings_temp_saved' => __('Templates settings saved.', 'events-maker'),
			'settings_caps_saved' => __('Capabilities settings saved.', 'events-maker'),
			'settings_perm_saved' => __('Permalinks settings saved.', 'events-maker'),
			'settings_gene_reseted' => __('General settings restored to defaults.', 'events-maker'),
			'settings_temp_reseted' => __('Templates settings restored to defaults.', 'events-maker'),
			'settings_caps_reseted' => __('Capabilities settings restored to defaults.', 'events-maker'),
			'settings_perm_reseted' => __('Permalinks settings restored to defaults.', 'events-maker'),
			'no_such_menu' => __('There is no such menu.', 'events-maker'),
			'empty_menu_name' => __('Menu name can not be empty.', 'events-maker')
		);

		$this->tabs = array(
			'general' => array(
				'name' => __('General', 'events-maker'),
				'key' => 'events_maker_general',
				'submit' => 'save_em_general',
				'reset' => 'reset_em_general'
			),
			'templates' => array(
				'name' => __('Templates', 'events-maker'),
				'key' => 'events_maker_templates',
				'submit' => 'save_em_templates',
				'reset' => 'reset_em_templates'
			),
			'capabilities' => array(
				'name' => __('Capabilities', 'events-maker'),
				'key' => 'events_maker_capabilities',
				'submit' => 'save_em_capabilities',
				'reset' => 'reset_em_capabilities'
			),
			'permalinks' => array(
				'name' => __('Permalinks', 'events-maker'),
				'key' => 'events_maker_permalinks',
				'submit' => 'save_em_permalinks',
				'reset' => 'reset_em_permalinks'
			)
		);

		$this->capabilities = array(
			'publish_events' => __('Publish Events', 'events-maker'),
			'edit_events' => __('Edit Events', 'events-maker'),
			'edit_others_events' => __('Edit Others Events', 'events-maker'),
			'edit_published_events' => __('Edit Published Events', 'events-maker'),
			'delete_published_events' => __('Delete Published Events', 'events-maker'),
			'delete_events' => __('Delete Events', 'events-maker'),
			'delete_others_events' => __('Delete Others Events', 'events-maker'),
			'read_private_events' => __('Read Private Events', 'events-maker'),
			'manage_event_categories' => __('Manage Event Categories', 'events-maker')
		);

		if($this->options['general']['use_tags'] === TRUE)
			$this->capabilities['manage_event_tags'] = __('Manage Event Tags', 'events-maker');

		$this->capabilities['manage_event_locations'] = __('Manage Event Locations', 'events-maker');

		if($this->options['general']['use_organizers'] === TRUE)
			$this->capabilities['manage_event_organizers'] = __('Manage Event Organizers', 'events-maker');
	}


	/**
	 * Adds link to Settings page
	*/
	public function plugin_settings_link($links, $file) 
	{
		if(!is_admin() || !current_user_can('manage_options'))
			return $links;

		static $plugin;

		$plugin = plugin_basename(__FILE__);

		if($file == $plugin)
		{
			$settings_link = sprintf('<a href="%s">%s</a>', admin_url('options-general.php').'?page=events-maker-options', __('Settings', 'events-maker'));
			array_unshift($links, $settings_link);
		}

		return $links;
	}


	/**
	 * Adds options page as submenu to events
	*/
	public function settings_page()
	{
		add_submenu_page('edit.php?post_type=event', __('Settings', 'events-maker'), __('Settings', 'events-maker'), 'manage_options', 'events-settings', array($this, 'options_page'));
	}


	/**
	 * 
	*/
	public function options_page()
	{
		$tab_key = (isset($_GET['tab']) ? $_GET['tab'] : 'general');

		echo '
		<div class="wrap">'.screen_icon().'
			<h2>'.__('Events Maker', 'events-maker').'</h2>
			<h2 class="nav-tab-wrapper">';

		foreach($this->tabs as $key => $name)
		{
			echo '
			<a class="nav-tab '.($tab_key == $key ? 'nav-tab-active' : '').'" href="'.esc_url(admin_url('edit.php?post_type=event&page=events-settings&tab='.$key)).'">'.$name['name'].'</a>';
		}

		echo '
			</h2>
			<div class="events-maker-settings">
				<div class="df-credits">
					<h3 class="hndle">'.__('Events Maker', 'events-maker').' '.$this->defaults['version'].'</h3>
					<div class="inside">
						<h4 class="inner">'.__('Need support?', 'events-maker').'</h4>
						<p class="inner">'.__('If you are having problems with this plugin, checkout plugin', 'events-maker').'  <a href="http://www.dfactory.eu/docs/events-maker-plugin/?utm_source=events-maker-settings&utm_medium=link&utm_campaign=documentation" target="_blank" title="'.__('Documentation', 'events-maker').'">'.__('Documentation', 'events-maker').'</a> '.__('or talk about them in the', 'events-maker').' <a href="http://www.dfactory.eu/support/?utm_source=events-maker-settings&utm_medium=link&utm_campaign=support" target="_blank" title="'.__('Support forum', 'events-maker').'">'.__('Support forum', 'events-maker').'</a></p>
						<hr />
						<h4 class="inner">'.__('Do you like this plugin?', 'events-maker').'</h4>
						<p class="inner"><a href="http://wordpress.org/support/view/plugin-reviews/events-maker" target="_blank" title="'.__('Rate it 5', 'events-maker').'">'.__('Rate it 5', 'events-maker').'</a> '.__('on WordPress.org', 'events-maker').'<br />'.
						__('Blog about it & link to the', 'events-maker').' <a href="http://www.dfactory.eu/plugins/events-maker/?utm_source=events-maker-settings&utm_medium=link&utm_campaign=blog-about" target="_blank" title="'.__('plugin page', 'events-maker').'">'.__('plugin page', 'events-maker').'</a><br />'.
						__('Check out our other', 'events-maker').' <a href="http://www.dfactory.eu/plugins/?utm_source=events-maker-settings&utm_medium=link&utm_campaign=other-plugins" target="_blank" title="'.__('WordPress plugins', 'events-maker').'">'.__('WordPress plugins', 'events-maker').'</a>
						</p>            
						<hr />
						<p class="df-link inner">'.__('Created by', 'events-maker').' <a href="http://www.dfactory.eu/?utm_source=events-maker-settings&utm_medium=link&utm_campaign=created-by" target="_blank" title="dFactory - Quality plugins for WordPress"><img src="'.EVENTS_MAKER_URL.'/images/logo-dfactory.png'.'" title="dFactory - Quality plugins for WordPress" alt="dFactory - Quality plugins for WordPress" /></a></p>
					</div>
				</div>
			
				<form action="options.php" method="post">';

		wp_nonce_field('update-options');
		settings_fields($this->tabs[$tab_key]['key']);
		do_settings_sections($this->tabs[$tab_key]['key']);

		echo '
					<p class="submit">';

		submit_button('', 'primary', $this->tabs[$tab_key]['submit'], FALSE);

		echo ' ';

		if($this->tabs[$tab_key]['reset'] !== FALSE)
			submit_button(__('Reset to defaults', 'events-maker'), 'secondary', $this->tabs[$tab_key]['reset'], FALSE);

		echo '
					</p>
				</form>
			</div>
			<div class="clear"></div>
		</div>';
	}


	/**
	 * 
	*/
	public function register_settings()
	{
		// general
		register_setting('events_maker_general', 'events_maker_general', array(&$this, 'validate_general'));
		add_settings_section('events_maker_general', __('General settings', 'events-maker'), '', 'events_maker_general');
		add_settings_field('em_available_functions', __('Events features support', 'events-maker'), array(&$this, 'em_available_functions'), 'events_maker_general', 'events_maker_general');
		add_settings_field('em_use_tags', __('Tags', 'events-maker'), array(&$this, 'em_use_tags'), 'events_maker_general', 'events_maker_general');
		add_settings_field('em_use_organizers', __('Organizers', 'events-maker'), array(&$this, 'em_use_organizers'), 'events_maker_general', 'events_maker_general');
		add_settings_field('em_use_event_tickets', __('Tickets', 'events-maker'), array(&$this, 'em_use_event_tickets'), 'events_maker_general', 'events_maker_general');
		add_settings_field('em_full_calendar_display', __('Full Calendar display', 'events-maker'), array(&$this, 'em_full_calendar_display'), 'events_maker_general', 'events_maker_general');
		add_settings_field('em_deactivation_delete', __('Deactivation', 'events-maker'), array(&$this, 'em_deactivation_delete'), 'events_maker_general', 'events_maker_general');

		// currencies
		add_settings_section('events_maker_currencies', __('Currency settings', 'events-maker'), '', 'events_maker_general');
		add_settings_field('em_tickets_currency_code', __('Currency', 'events-maker'), array(&$this, 'em_tickets_currency_code'), 'events_maker_general', 'events_maker_currencies');
		add_settings_field('em_tickets_currency_position', __('Currency Position', 'events-maker'), array(&$this, 'em_tickets_currency_position'), 'events_maker_general', 'events_maker_currencies');
		add_settings_field('em_tickets_currency_symbol', __('Currency Symbol', 'events-maker'), array(&$this, 'em_tickets_currency_symbol'), 'events_maker_general', 'events_maker_currencies');
		add_settings_field('em_tickets_currency_format', __('Currency display format', 'events-maker'), array(&$this, 'em_tickets_currency_format'), 'events_maker_general', 'events_maker_currencies');

		// query
		add_settings_section('events_maker_query', __('Query settings', 'events-maker'), '', 'events_maker_general');
		add_settings_field('em_order_by', __('Order by', 'events-maker'), array(&$this, 'em_order_by'), 'events_maker_general', 'events_maker_query');
		add_settings_field('em_order', __('Sort order', 'events-maker'), array(&$this, 'em_order'), 'events_maker_general', 'events_maker_query');
		add_settings_field('em_show_past_events', __('Past events', 'events-maker'), array(&$this, 'em_show_past_events'), 'events_maker_general', 'events_maker_query');
		add_settings_field('em_expire_current', __('Current events', 'events-maker'), array(&$this, 'em_expire_current'), 'events_maker_general', 'events_maker_query');
		add_settings_field('em_show_occurrences', __('Occurrences', 'events-maker'), array(&$this, 'em_show_occurrences'), 'events_maker_general', 'events_maker_query');

		// other
		add_settings_section('events_maker_other', __('Other settings', 'events-maker'), '', 'events_maker_general');
		add_settings_field('em_event_nav_menu', __('Link in menu', 'events-maker'), array(&$this, 'em_event_nav_menu'), 'events_maker_general', 'events_maker_other');
		add_settings_field('em_date_format', __('Date and time format', 'events-maker'), array(&$this, 'em_date_format'), 'events_maker_general', 'events_maker_other');
		add_settings_field('em_first_weekday', __('First day of the week', 'events-maker'), array(&$this, 'em_first_weekday'), 'events_maker_general', 'events_maker_other');

		// templates
		register_setting('events_maker_templates', 'events_maker_templates', array(&$this, 'validate_templates'));
		add_settings_section('events_maker_templates', __('Templates settings', 'events-maker'), '', 'events_maker_templates');
		add_settings_field('em_default_templates', __('Default templates', 'events-maker'), array(&$this, 'em_default_templates'), 'events_maker_templates', 'events_maker_templates');
		add_settings_field('em_template_archive', __('Events archive', 'events-maker'), array(&$this, 'em_template_archive'), 'events_maker_templates', 'events_maker_templates');
		add_settings_field('em_template_content_archive_event', __('Archive event content', 'events-maker'), array(&$this, 'em_template_content_archive_event'), 'events_maker_templates', 'events_maker_templates');
		add_settings_field('em_template_single', __('Single event', 'events-maker'), array(&$this, 'em_template_single'), 'events_maker_templates', 'events_maker_templates');
		add_settings_field('em_template_content_single_event', __('Single event content', 'events-maker'), array(&$this, 'em_template_content_single_event'), 'events_maker_templates', 'events_maker_templates');
		add_settings_field('em_template_tax_categories', __('Categories', 'events-maker'), array(&$this, 'em_template_tax_categories'), 'events_maker_templates', 'events_maker_templates');

		if($this->options['general']['use_tags'])
			add_settings_field('em_template_tax_tags', __('Tags', 'events-maker'), array(&$this, 'em_template_tax_tags'), 'events_maker_templates', 'events_maker_templates');

		add_settings_field('em_template_tax_locations', __('Locations', 'events-maker'), array(&$this, 'em_template_tax_locations'), 'events_maker_templates', 'events_maker_templates');

		if($this->options['general']['use_organizers'])
			add_settings_field('em_template_tax_organizers', __('Organizers', 'events-maker'), array(&$this, 'em_template_tax_organizers'), 'events_maker_templates', 'events_maker_templates');

		// capabilities
		register_setting('events_maker_capabilities', 'events_maker_capabilities', array(&$this, 'validate_capabilities'));
		add_settings_section('events_maker_capabilities', __('Capabilities settings', 'events-maker'), array(&$this, 'em_capabilities_table'), 'events_maker_capabilities');

		// permalinks
		register_setting('events_maker_permalinks', 'events_maker_permalinks', array(&$this, 'validate_permalinks'));
		add_settings_section('events_maker_permalinks', __('Permalinks settings', 'events-maker'), array(&$this, 'em_permalinks_desc'), 'events_maker_permalinks');
		add_settings_field('em_archive_event', __('Events base', 'events-maker'), array(&$this, 'em_archive_event'), 'events_maker_permalinks', 'events_maker_permalinks');
		add_settings_field('em_single_event', __('Single event', 'events-maker'), array(&$this, 'em_single_event'), 'events_maker_permalinks', 'events_maker_permalinks');
		add_settings_field('em_category_event', __('Categories', 'events-maker'), array(&$this, 'em_category_event'), 'events_maker_permalinks', 'events_maker_permalinks');

		if($this->options['general']['use_tags'])
			add_settings_field('em_tag_event', __('Tags', 'events-maker'), array(&$this, 'em_tag_event'), 'events_maker_permalinks', 'events_maker_permalinks');

		add_settings_field('em_location_event', __('Locations', 'events-maker'), array(&$this, 'em_location_event'), 'events_maker_permalinks', 'events_maker_permalinks');

		if($this->options['general']['use_organizers'])
			add_settings_field('em_organizer_event', __('Organizers', 'events-maker'), array(&$this, 'em_organizer_event'), 'events_maker_permalinks', 'events_maker_permalinks');
	}


	/**
	 * 
	*/
	public function em_available_functions()
	{
		echo '
		<div id="em_available_functions">
			<fieldset>';

		foreach($this->supports as $val => $trans)
		{
			echo '
				<input id="em-available-function-'.$val.'" type="checkbox" name="events_maker_general[supports][]" value="'.esc_attr($val).'" '.checked(true, $this->options['general']['supports'][$val], false).' /><label for="em-available-function-'.$val.'">'.$trans.'</label>';
		}

		echo '
				<br />
				<span class="description">'.__('Select which features would you like to enable for your events.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_use_tags()
	{
		echo '
		<div id="em_use_tags">
			<fieldset>
				<input id="em-use-tags" type="checkbox" name="events_maker_general[use_tags]" '.checked($this->options['general']['use_tags'], true, false).' /><label for="em-use-tags">'.__('Enable Event Tags', 'events-maker').'</label>
				<br />
				<span class="description">'.__('Enable if you want to use Event Tags.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_use_organizers()
	{
		echo '
		<div id="em_use_organizers">
			<fieldset>
				<input id="em-use-organizers" type="checkbox" name="events_maker_general[use_organizers]" '.checked($this->options['general']['use_organizers'], true, false).' /><label for="em-use-organizers">'.__('Enable Event Organizers', 'events-maker').'</label>
				<br />
				<span class="description">'.__('Enable if you want to use Event Organizers (including organizer contact details).', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_use_event_tickets()
	{
		echo '
		<div id="em_use_event_tickets">
			<fieldset>
				<input id="em-use-event-tickets" type="checkbox" name="events_maker_general[use_event_tickets]" '.checked($this->options['general']['use_event_tickets'], true, false).' /><label for="em-use-event-tickets">'.__('Enable Event Tickets', 'events-maker').'</label>
				<br />
				<span class="description">'.__('Enable if you want to use Event Tickets (including free events, paid events and multiple ticket types).', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_full_calendar_display()
	{
		echo '
		<div id="em_full_calendar_display">
			<fieldset>';

		foreach($this->calendar_displays as $val => $trans)
		{
			echo '
				<input id="em-full-calendar-display-'.$val.'" type="radio" name="events_maker_general[full_calendar_display][type]" value="'.esc_attr($val).'" '.checked($val, $this->options['general']['full_calendar_display']['type'], false).' /><label for="em-full-calendar-display-'.$val.'">'.$trans.'</label>';
		}

		echo '
				<div id="event-full-calendar-display-page"'.($this->options['general']['full_calendar_display']['type'] === 'page' ? '' : ' style="display: none;"').'>';

		if(!empty($this->pages))
		{
			echo '
					<select name="events_maker_general[full_calendar_display][page]">';

			foreach($this->pages as $page)
			{
				echo '
						<option value="'.$page->ID.'" '.selected($page->ID, $this->options['general']['full_calendar_display']['page'], false).'>'.esc_html($page->post_title).'</option>';
			}

			echo '
					</select>
					<div>';

			foreach($this->calendar_contents as $val => $trans)
			{
				echo '
						<input id="em-full-calendar-content-'.$val.'" type="radio" name="events_maker_general[full_calendar_display][content]" value="'.esc_attr($val).'" '.checked($val, $this->options['general']['full_calendar_display']['content'], false).' /><label for="em-full-calendar-content-'.$val.'">'.$trans.'</label>';
			}

			echo '
					</div>';
		}
		else
			echo __('There are no pages.', 'events-maker');

		echo '
				</div>
				<span class="description">'.__('Select how and where would you like to display events full calendar. Use <code>[em-full-calendar]</code> shortcode for manual display.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_deactivation_delete()
	{
		echo '
		<div id="em_deactivation_delete">
			<fieldset>
				<input id="em-deactivation-delete" type="checkbox" name="events_maker_general[deactivation_delete]" '.checked($this->options['general']['deactivation_delete'], true, false).' /><label for="em-deactivation-delete">'.__('Enable delete on deactivation', 'events-maker').'</label>
				<br />
				<span class="description">'.__('Enable if you want all plugin data to be deleted on deactivation.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_tickets_currency_code()
	{
		echo '
		<div id="em_tickets_currency_code">
			<fieldset>
				<select id="em-tickets-currency-code" name="events_maker_general[currencies][code]">';

		foreach($this->currencies['codes'] as $code => $currency)
		{
			echo '
					<option value="'.esc_attr($code).'" '.selected($code, $this->options['general']['currencies']['code'], FALSE).'>'.$currency.'</option>';
		}

		echo '
				</select>
				<br />
				<span class="description">'.__('Choose the currency that will be used for ticket prices.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_tickets_currency_position()
	{
		echo '
		<div id="em_tickets_currency_position">
			<fieldset>';

		foreach($this->currencies['positions'] as $key => $position)
		{
			echo '
				<input id="em-ticket-currency-position-'.$key.'" type="radio" name="events_maker_general[currencies][position]" value="'.esc_attr($key).'" '.checked($key, $this->options['general']['currencies']['position'], FALSE).' /><label for="em-ticket-currency-position-'.$key.'">'.$position.'</label>';
		}

		echo '
				<br />
				<span class="description">'.__('Choose the location of the currency sign.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_tickets_currency_symbol()
	{
		echo '
		<div id="em_tickets_currency_symbol">
			<fieldset>
				<input type="text" size="4" name="events_maker_general[currencies][symbol]" value="'.esc_attr($this->options['general']['currencies']['symbol']).'" />
				<br />
				<span class="description">'.__('This will appear next to all the currency figures on the website. Ex. $, USD, €...', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_tickets_currency_format()
	{
		echo '
		<div id="em_tickets_currency_format">
			<fieldset>
				<select id="em-tickets-currency-format" name="events_maker_general[currencies][format]">';

		foreach($this->currencies['formats'] as $code => $format)
		{
			echo '
					<option value="'.esc_attr($code).'" '.selected($code, $this->options['general']['currencies']['format'], FALSE).'>'.$format.'</option>';
		}

		echo '
				</select>
				<br />
				<span class="description">'.__('This determines how your currency is displayed. Ex. 1,234.56 or 1,200 or 1200.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_order_by()
	{
		echo '
		<div id="em_order_by">
			<fieldset>';

		foreach($this->sortings as $val => $trans)
		{
			echo '
				<input id="em-order-by-'.$val.'" type="radio" name="events_maker_general[order_by]" value="'.esc_attr($val).'" '.checked($val, $this->options['general']['order_by'], false).' /><label for="em-order-by-'.$val.'">'.$trans.'</label>';
		}

		echo '
				<br />
				<span class="description">'.__('Select how to order your events list (works for both: admin and front-end default query).', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_order()
	{
		echo '
		<div id="em_order">
			<fieldset>';

		foreach($this->orders as $val => $trans)
		{
			echo '
				<input id="em-order-'.$val.'" type="radio" name="events_maker_general[order]" value="'.esc_attr($val).'" '.checked($val, $this->options['general']['order'], false).' /><label for="em-order-'.$val.'">'.$trans.'</label>';
		}

		echo '
				<br />
				<span class="description">'.__('Select events list order (works for both: admin and front-end default query).', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_show_past_events()
	{
		echo '
		<div id="em_show_past_events">
			<fieldset>
				<input id="em-show-ended-events" type="checkbox" name="events_maker_general[show_past_events]" '.checked($this->options['general']['show_past_events'], true, false).' /><label for="em-show-ended-events">'.__('Show past events', 'events-maker').'</label>
				<br />
				<span class="description">'.__('Select whether to include past events in events list (works for front-end default query).', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_expire_current()
	{
		echo '
		<div id="em_expire_current">
			<fieldset>
				<input id="em-expire-current" type="checkbox" name="events_maker_general[expire_current]" '.checked($this->options['general']['expire_current'], true, false).' /><label for="em-expire-current">'.__('Expire current events', 'events-maker').'</label>
				<br />
				<span class="description">'.__('Select how to handle already started events (works for front-end default query).', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_show_occurrences()
	{
		echo '
		<div id="em_show_occurrences">
			<fieldset>
				<input id="em-show-occurrences" type="checkbox" name="events_maker_general[show_occurrences]" '.checked($this->options['general']['show_occurrences'], true, false).' /><label for="em-show-occurrences">'.__('Show occurrences', 'events-maker').'</label>
				<br />
				<span class="description">'.__('Select whether to include event occurrences in events list (works for front-end default query).', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_event_nav_menu()
	{
		$menus = get_terms('nav_menu');

		echo '
		<div id="em_event_nav_menu">
			<fieldset>
				<input id="em-event-nav-menu-checkbox" type="checkbox" name="events_maker_general[event_nav_menu][show]" '.checked($this->options['general']['event_nav_menu']['show'], true, false).' /><label for="em-event-nav-menu-checkbox">'.__('Show link in menu', 'events-maker').'</label>
				<div id="em_event_nav_menu_opt"'.($this->options['general']['event_nav_menu']['show'] === FALSE ? ' style="display: none;"' : '').'>';

		if(!empty($menus))
		{
			echo '
					<label for="em-event-nav-menu">'.__('Menu', 'events-maker').':</label> <select id="em-event-nav-menu" name="events_maker_general[event_nav_menu][menu_id]">';

			foreach($menus as $menu)
			{
				echo '
						<option value="'.esc_attr($menu->term_id).'" '.selected($menu->term_id, $this->options['general']['event_nav_menu']['menu_id'], FALSE).'>'.$menu->name.'</option>';
			}

			echo '
					</select>
					<br />
					<label for="em-event-nav-menu-title">'.__('Title', 'events-maker').':</label> <input id="em-event-nav-menu-title" type="text" name="events_maker_general[event_nav_menu][menu_name]" value="'.esc_attr($this->options['general']['event_nav_menu']['menu_name']).'" />
					<br />
					<span class="description">'.__('Note: if global WordPress permalinks were changed remember to save Events Maker general settings again.', 'events-maker').'</span>';
		}
		else
			echo '
					<span class="description">'.__('Note: there is no menu to which you could add events archive link.', 'events-maker').'</span>';

		echo '
				</div>';

		echo '
				<span class="description">'.__('Select if you want to automatically add events archive link to nav menu.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_date_format()
	{
		echo '
		<div id="em_date_format">
			<fieldset>
				<label for="em-date-format">'.__('Date', 'events-maker').':</label> <input id="em-date-format" type="text" name="events_maker_general[datetime_format][date]" value="'.esc_attr($this->options['general']['datetime_format']['date']).'" /> <code>'.date_i18n($this->options['general']['datetime_format']['date'], current_time('timestamp')).'</code>
				<br />
				<label for="em-time-format">'.__('Time', 'events-maker').':</label> <input id="em-time-format" type="text" name="events_maker_general[datetime_format][time]" value="'.esc_attr($this->options['general']['datetime_format']['time']).'" /> <code>'.date($this->options['general']['datetime_format']['time'], current_time('timestamp')).'</code>
				<br />
				<span class="description">'.__('Enter your preffered date and time formatting.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_first_weekday()
	{
		global $wp_locale;

		echo '
		<div id="em_first_weekday">
			<fieldset>
				<select name="events_maker_general[first_weekday]">
					<option value="1" '.selected(1, $this->options['general']['first_weekday'], false).'>'.$wp_locale->get_weekday(1).'</option>
					<option value="7" '.selected(7, $this->options['general']['first_weekday'], false).'>'.$wp_locale->get_weekday(0).'</option>
				</select>
				<br />
				<span class="description">'.__('Select preffered first day of the week for the calendar display.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_default_templates()
	{
		echo '
		<div id="em_default_templates">
			<fieldset>
				<input id="em-default-templates" type="checkbox" name="events_maker_templates[default_templates]" '.checked($this->options['templates']['default_templates'], true, false).' /><label for="em-default-templates">'.__('Enable to use default templates', 'events-maker').'</label>
				<br />
				<span class="description">'.__('For each of the events pages, the corresponding template is used. To use your own template simply give it the same name and store in your theme folder. By default, if Events Maker can\'t find a template in your theme directory, it will use its own default template. To prevent this, uncheck this option. WordPress will then decide which template from your theme\'s folder to use.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_template_archive()
	{
		echo '
		<div id="em_template_archive">
			<p class="description">
				<code>templates/<strong>archive-event.php</strong></code>
			</p>
		</div>';
	}


	/**
	 * 
	*/
	public function em_template_content_archive_event()
	{
		echo '
		<div id="em_template_content_archive_event">
			<p class="description">
				<code>templates/<strong>content-event.php</strong></code>
			</p>
		</div>';
	}


	/**
	 * 
	*/
	public function em_template_single()
	{
		echo '
		<div id="em_template_single">
			<p class="description">
				<code>templates/<strong>single-event.php</strong></code>
			</p>
		</div>';
	}


	/**
	 * 
	*/
	public function em_template_content_single_event()
	{
		echo '
		<div id="em_template_content_single_event">
			<p class="description">
				<code>templates/<strong>content-single-event.php</strong></code>
			</p>
		</div>';
	}


	/**
	 * 
	*/
	public function em_template_tax_locations()
	{
		echo '
		<div id="em_template_tax_locations">
			<p class="description">
				<code>templates/<strong>taxonomy-event-location.php</strong></code>
			</p>
		</div>';
	}


	/**
	 * 
	*/
	public function em_template_tax_categories()
	{
		echo '
		<div id="em_template_tax_categories">
			<p class="description">
				<code>templates/<strong>taxonomy-event-category.php</strong></code>
			</p>
		</div>';
	}


	/**
	 * 
	*/
	public function em_template_tax_organizers()
	{
		echo '
		<div id="em_template_tax_organizers">
			<p class="description">
				<code>templates/<strong>taxonomy-event-organizer.php</strong></code>
			</p>
		</div>';
	}


	/**
	 * 
	*/
	public function em_template_tax_tags()
	{
		echo '
		<div id="em_template_tax_tags">
			<p class="description">
				<code>templates/<strong>taxonomy-event-tag.php</strong></code>
			</p>
		</div>';
	}


	/**
	 * 
	*/
	public function em_permalinks_desc()
	{
		echo '
		<span class="description">'.__('These settings will work only if permalinks are enabled.', 'events-maker').'</span>';
	}


	/**
	 * 
	*/
	public function em_archive_event()
	{
		echo '
		<div id="em_archive_event">
			<fieldset>
				<input type="text" name="events_maker_permalinks[event_rewrite_base]" value="'.esc_attr($this->options['permalinks']['event_rewrite_base']).'" />
				<br />
				<span class="description"><code>'.site_url().'/<strong>'.$this->options['permalinks']['event_rewrite_base'].'</strong>/</code></span>
				<span class="description">'.__('General Events root slug to prefix all your events pages with.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_single_event()
	{
		echo '
		<div id="em_single_event">
			<fieldset>
				<input type="text" name="events_maker_permalinks[event_rewrite_slug]" value="'.esc_attr($this->options['permalinks']['event_rewrite_slug']).'" />
				<br />
				<span class="description"><code>'.site_url().'/<strong>'.$this->options['permalinks']['event_rewrite_base'].'</strong>/<strong>'.$this->options['permalinks']['event_rewrite_slug'].'</strong>/</code></span>
				<span class="description">'.__('Single event page slug.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_location_event()
	{
		echo '
		<div id="em_location_event">
			<fieldset>
				<input type="text" name="events_maker_permalinks[event_locations_rewrite_slug]" value="'.esc_attr($this->options['permalinks']['event_locations_rewrite_slug']).'" />
				<br />
				<span class="description"><code>'.site_url().'/<strong>'.$this->options['permalinks']['event_rewrite_base'].'</strong>/<strong>'.$this->options['permalinks']['event_locations_rewrite_slug'].'</strong>/</code></span>
				<span class="description">'.__('Event Locations page slug.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_category_event()
	{
		echo '
		<div id="em_category_event">
			<fieldset>
				<input type="text" name="events_maker_permalinks[event_categories_rewrite_slug]" value="'.esc_attr($this->options['permalinks']['event_categories_rewrite_slug']).'" />
				<br />
				<span class="description"><code>'.site_url().'/<strong>'.$this->options['permalinks']['event_rewrite_base'].'</strong>/<strong>'.$this->options['permalinks']['event_categories_rewrite_slug'].'</strong>/</code></span>
				<span class="description">'.__('Event Categories page slug.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_tag_event()
	{
		echo '
		<div id="em_tag_event">
			<fieldset>
				<input type="text" name="events_maker_permalinks[event_tags_rewrite_slug]" value="'.esc_attr($this->options['permalinks']['event_tags_rewrite_slug']).'" />
				<br />
				<span class="description"><code>'.site_url().'/<strong>'.$this->options['permalinks']['event_rewrite_base'].'</strong>/<strong>'.$this->options['permalinks']['event_tags_rewrite_slug'].'</strong>/</code></span>
				<span class="description">'.__('Event Tags page slug.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_organizer_event()
	{
		echo '
		<div id="em_organizer_event">
			<fieldset>
				<input type="text" name="events_maker_permalinks[event_organizers_rewrite_slug]" value="'.esc_attr($this->options['permalinks']['event_organizers_rewrite_slug']).'" />
				<br />
				<span class="description"><code>'.site_url().'/<strong>'.$this->options['permalinks']['event_rewrite_base'].'</strong>/<strong>'.$this->options['permalinks']['event_organizers_rewrite_slug'].'</strong>/</code></span>
				<span class="description">'.__('Event Organizers page slug.', 'events-maker').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function em_capabilities_table()
	{
		global $wp_roles;

		$built_in_roles = array('administrator', 'author', 'contributor', 'editor', 'subscriber');

		$html = '
		<table class="widefat fixed posts">
			<thead>
				<tr>
					<th>'.__('Role', 'events-maker').'</th>';

		foreach($built_in_roles as $role_name)
		{
			$html .= '<th>'.esc_html((isset($wp_roles->role_names[$role_name]) ? translate_user_role($wp_roles->role_names[$role_name]) : __('None', 'events-maker'))).'</th>';
		}

		$html .= '
				</tr>
			</thead>
			<tbody id="the-list">';

		$i = 0;

		foreach($this->capabilities as $em_role => $role_display)
		{
			$html .= '
				<tr'.(($i++ % 2 === 0) ? ' class="alternate"' : '').'>
					<td>'.esc_html(__($role_display, 'events-maker')).'</td>';

			foreach($built_in_roles as $role_name)
			{
				$role = $wp_roles->get_role($role_name);
				$html .= '
					<td>
						<input type="checkbox" name="events_maker_capabilities['.esc_attr($role->name).']['.esc_attr($em_role).']" value="1" '.checked('1', $role->has_cap($em_role), FALSE).' '.disabled($role->name, 'administrator', FALSE).' />
					</td>';
			}

			$html .= '
				</tr>';
		}

		$html .= '
			</tbody>
		</table>';

		echo $html;
	}


	/**
	 * Validates capabilities settings
	*/
	function validate_capabilities($input)
	{
		global $wp_roles;

		if(isset($_POST['save_em_capabilities']))
		{
			foreach($wp_roles->roles as $role_name => $role_text)
			{
				$role = $wp_roles->get_role($role_name);

				if(!$role->has_cap('manage_options'))
				{
					foreach($this->defaults['capabilities'] as $capability)
					{
						if(isset($input[$role_name][$capability]) && $input[$role_name][$capability] === '1')
							$role->add_cap($capability);
						else
							$role->remove_cap($capability);
					}
				}
			}

			set_transient($this->transient_id, maybe_serialize(array('status' => 'updated', 'text' => $this->errors['settings_caps_saved'])), 60);
		}
		elseif(isset($_POST['reset_em_capabilities']))
		{
			foreach($wp_roles->roles as $role_name => $display_name)
			{
				$role = $wp_roles->get_role($role_name);

				foreach($this->defaults['capabilities'] as $capability)
				{
					if($role->has_cap('manage_options'))
						$role->add_cap($capability);
					else
						$role->remove_cap($capability);
				}
			}

			set_transient($this->transient_id, maybe_serialize(array('status' => 'updated', 'text' => $this->errors['settings_caps_reseted'])), 60);
		}

		return '';
	}


	/**
	 * Validates or resets general settings
	*/
	public function validate_general($input)
	{
		if(isset($_POST['save_em_general']))
		{
			//rewrite rules
			$input['rewrite_rules'] = FALSE;

			//supports
			$supports = array();
			$input['supports'] = (isset($input['supports']) ? array_flip($input['supports']) : NULL);

			foreach($this->supports as $function => $trans)
			{
				$supports[$function] = (isset($input['supports'][$function]) ? TRUE : FALSE);
			}

			$input['supports'] = $supports;

			//currencies
			$input['currencies']['symbol'] = sanitize_text_field($input['currencies']['symbol']);
			$input['currencies']['code'] = (isset($input['currencies']['code']) && in_array($input['currencies']['code'], array_keys($this->currencies['codes'])) ? $input['currencies']['code'] : $this->defaults['currencies']['code']);
			$input['currencies']['format'] = (isset($input['currencies']['format']) && in_array($input['currencies']['format'], array_keys($this->currencies['formats'])) ? $input['currencies']['format'] : $this->defaults['currencies']['format']);
			$input['currencies']['position'] = (isset($input['currencies']['position']) && in_array($input['currencies']['position'], array_keys($this->currencies['positions'])) ? $input['currencies']['position'] : $this->defaults['currencies']['position']);

			//default order
			$input['order_by'] = (isset($input['order_by']) && in_array($input['order_by'], array_keys($this->sortings)) ? $input['order_by'] : $this->defaults['general']['order_by']);
			$input['order'] = (isset($input['order']) && in_array($input['order'], array_keys($this->orders)) ? $input['order'] : $this->defaults['general']['order']);

			//date, time, weekday
			$input['datetime_format']['date'] = sanitize_text_field($input['datetime_format']['date']);
			$input['datetime_format']['time'] = sanitize_text_field($input['datetime_format']['time']);
			$input['first_weekday'] = (in_array($input['first_weekday'], array(1, 7)) ? (int)$input['first_weekday']: $this->defaults['general']['first_weekday']);

			if($input['datetime_format']['date'] === '')
				$input['datetime_format']['date'] = get_option('date_format');

			if($input['datetime_format']['time'] === '')
				$input['datetime_format']['time'] = get_option('time_format');

			// treat current event as expired
			$input['expire_current'] = (isset($input['expire_current']) ? true : false);

			// show past events
			$input['show_past_events'] = (isset($input['show_past_events']) ? true : false);

			// show occurrences
			$input['show_occurrences'] = (isset($input['show_occurrences']) ? true : false);

			// use organizers
			$input['use_organizers'] = (isset($input['use_organizers']) ? true : false);

			// use tags
			$input['use_tags'] = (isset($input['use_tags']) ? true : false);

			// use tickets
			$input['use_event_tickets'] = (isset($input['use_event_tickets']) ? true : false);

			// full calendar display
			$input['full_calendar_display']['type'] = (isset($input['full_calendar_display']['type'], $this->calendar_displays[$input['full_calendar_display']['type']]) ? $input['full_calendar_display']['type'] : $this->defaults['general']['full_calendar_display']['type']);

			if($input['full_calendar_display']['type'] === 'page')
			{
				// page id
				$input['full_calendar_display']['page'] = (int)(isset($input['full_calendar_display']['page']) ? $input['full_calendar_display']['page'] : $this->defaults['general']['full_calendar_display']['post']);

				// wpml and polylang compatibility
				if($input['full_calendar_display']['page'] !== 0 && function_exists('icl_object_id'))
					$input['full_calendar_display']['page'] = icl_object_id($input['full_calendar_display']['page'], 'page', true);

				// content display position
				$input['full_calendar_display']['content'] = (isset($input['full_calendar_display']['content'], $this->calendar_contents[$input['full_calendar_display']['content']]) ? $input['full_calendar_display']['content'] : $this->defaults['general']['full_calendar_display']['content']);

				$input['display_page_notice'] = false;
			}
			else
			{
				// page id
				$input['full_calendar_display']['page'] = $this->defaults['general']['full_calendar_display']['post'];

				// content display position
				$input['full_calendar_display']['content'] = $this->defaults['general']['full_calendar_display']['content'];

				if(!$this->options['general']['display_page_notice'])
					$input['display_page_notice'] = false;
			}

			// deactivation
			$input['deactivation_delete'] = (isset($input['deactivation_delete']) ? true : false);

			//menu
			$input['event_nav_menu']['show'] = (isset($input['event_nav_menu']['show']) ? true : false);

			$menu_failed = false;
			$menus = get_terms('nav_menu');

			if($input['event_nav_menu']['show'] && !empty($menus))
			{
				$input['event_nav_menu']['menu_id'] = (int)$input['event_nav_menu']['menu_id'];

				if(($input['event_nav_menu']['menu_name'] = sanitize_text_field($input['event_nav_menu']['menu_name'])) === '')
				{
					$menu_failed = true;

					$input['event_nav_menu']['menu_id'] = 0;
					$input['event_nav_menu']['item_id'] = 0;

					set_transient($this->transient_id, maybe_serialize(array('status' => 'error', 'text' => $this->errors['empty_menu_name'])), 60);
				}
				else
				{
					if(!($menu_item = $this->update_menu($input['event_nav_menu']['menu_id'], $input['event_nav_menu']['menu_name'])))
					{
						$menu_failed = true;

						$input['event_nav_menu']['menu_id'] = 0;
						$input['event_nav_menu']['item_id'] = 0;
						$input['event_nav_menu']['menu_name'] = '';

						set_transient($this->transient_id, maybe_serialize(array('status' => 'error', 'text' => $this->errors['no_such_menu'])), 60);
					}
					else
						$input['event_nav_menu']['item_id'] = $menu_item;
				}
			}
			else
			{
				$input['event_nav_menu']['show'] = false;
				$input['event_nav_menu']['menu_id'] = $this->defaults['general']['event_nav_menu']['menu_id'];
				$input['event_nav_menu']['menu_name'] = $this->defaults['general']['event_nav_menu']['menu_name'];
				$input['event_nav_menu']['item_id'] = $this->update_menu();
			}

			if(!$menu_failed)
				set_transient($this->transient_id, maybe_serialize(array('status' => 'updated', 'text' => $this->errors['settings_gene_saved'])), 60);
		}
		elseif(isset($_POST['reset_em_general']))
		{
			$input = $this->defaults['general'];

			if(!$this->options['general']['display_page_notice'])
				$input['display_page_notice'] = false;

			//menu
			$input['event_nav_menu']['show'] = false;
			$input['event_nav_menu']['menu_id'] = $this->defaults['general']['event_nav_menu']['menu_id'];
			$input['event_nav_menu']['menu_name'] = $this->defaults['general']['event_nav_menu']['menu_name'];
			$input['event_nav_menu']['item_id'] = $this->update_menu();

			//datetime format
			$input['datetime_format'] = array(
				'date' => get_option('date_format'),
				'time' => get_option('time_format')
			);

			set_transient($this->transient_id, maybe_serialize(array('status' => 'updated', 'text' => $this->errors['settings_gene_reseted'])), 60);
		}

		return $input;
	}


	/**
	 * Validates permalinks settings
	*/
	public function validate_permalinks($input)
	{
		if(isset($_POST['save_em_permalinks']))
		{
			//slugs
			$input['event_rewrite_base'] = sanitize_title($input['event_rewrite_base']);
			$input['event_rewrite_slug'] = sanitize_title($input['event_rewrite_slug']);
			$input['event_categories_rewrite_slug'] = sanitize_title($input['event_categories_rewrite_slug']);
			$input['event_locations_rewrite_slug'] = sanitize_title($input['event_locations_rewrite_slug']);

			if($this->options['general']['use_tags'] === TRUE)
				$input['event_tags_rewrite_slug'] = sanitize_title($input['event_tags_rewrite_slug']);

			if($this->options['general']['use_organizers'] === TRUE)
				$input['event_organizers_rewrite_slug'] = sanitize_title($input['event_organizers_rewrite_slug']);

			set_transient($this->transient_id, maybe_serialize(array('status' => 'updated', 'text' => $this->errors['settings_perm_saved'])), 60);
		}
		elseif(isset($_POST['reset_em_permalinks']))
		{
			$input = $this->defaults['permalinks'];

			set_transient($this->transient_id, maybe_serialize(array('status' => 'updated', 'text' => $this->errors['settings_perm_reseted'])), 60);
		}

		$general_opts = get_option('events_maker_general');
		$general_opts['rewrite_rules'] = TRUE;

		update_option('events_maker_general', $general_opts);

		return $input;
	}


	/**
	 * Validates or resets templates settings
	*/
	public function validate_templates($input)
	{
		if(isset($_POST['save_em_templates']))
		{
			$input['default_templates'] = (isset($input['default_templates']) ? true : false);

			set_transient($this->transient_id, maybe_serialize(array('status' => 'updated', 'text' => $this->errors['settings_temp_saved'])), 60);
		}
		elseif(isset($_POST['reset_em_templates']))
		{
			$input = $this->defaults['templates'];

			set_transient($this->transient_id, maybe_serialize(array('status' => 'updated', 'text' => $this->errors['settings_temp_reseted'])), 60);
		}

		return $input;
	}


	/**
	 * Adds new menu item to specified menu or removes it
	*/
	function update_menu($menu_id = NULL, $menu_item_title = '')
	{
		$menu_item_id = $this->options['general']['event_nav_menu']['item_id'];

		if(is_nav_menu_item($menu_item_id))
		{
			$nav_menu_item = TRUE;

			if($menu_id === NULL)
			{
				wp_delete_post($menu_item_id, TRUE);
				$menu_item_id = 0;
			}
		}
		else
		{
			$nav_menu_item = FALSE;
			$menu_item_id = 0;
		}

		if(is_int($menu_id) && !empty($menu_id))
		{
			if(($menu = wp_get_nav_menu_object($menu_id)) === FALSE)
				return FALSE;

			$menu_id = $menu->term_id;
			$menu_item_data = array(
				'menu-item-title' => $menu_item_title,
				'menu-item-url' => em_get_event_date_link(),
				'menu-item-object' => 'event',
				'menu-item-status' => ($menu_id == 0 ? '' : 'publish'),
				'menu-item-type' => 'custom'
			);

			if($nav_menu_item === TRUE)
			{
				$menu_item = wp_setup_nav_menu_item(get_post($menu_item_id));
				$menu_item_data['menu-item-parent-id'] = $menu_item->menu_item_parent;
				$menu_item_data['menu-item-position'] = $menu_item->menu_order;
			}

			$menu_item_id = wp_update_nav_menu_item($menu_id, $menu_item_id, $menu_item_data);
		}

		return $menu_item_id;
	}
}
?>