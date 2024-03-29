<?php
if(!defined('ABSPATH')) exit;

new Events_Maker_Metaboxes($events_maker);

class Events_Maker_Metaboxes
{
	private $options = array();
	private $metaboxes = array();
	private $recurrences = array();
	private $tickets_fields = array();
	private $events_maker;


	public function __construct($events_maker)
	{
		$this->events_maker = $events_maker;

		//settings
		$this->options = $events_maker->get_options();

		//actions
		add_action('add_meta_boxes', array(&$this, 'add_events_meta_boxes'), 10, 2);
		add_action('admin_enqueue_scripts', array(&$this, 'admin_scripts_styles'));
		add_action('after_setup_theme', array(&$this, 'set_recurrences'));
		add_action('after_setup_theme', array(&$this, 'load_defaults'));
		add_action('save_post', array(&$this, 'save_event'), 10, 2);
	}


	/**
	 * 
	*/
	public function set_recurrences()
	{
		$this->recurrences = $this->events_maker->get_recurrences();
	}


	/**
	 * 
	*/
	public function load_defaults()
	{
		$this->tickets_fields = apply_filters(
			'em_event_tickets_fields',
			array(
				'name' => __('Ticket Name', 'events-maker'),
				'price' => __('Price', 'events-maker')
			)
		);

		$post_types = apply_filters('em_event_post_type', array('event'));

		foreach ($post_types as $post_type)
		{
			$this->metaboxes[] = apply_filters(
				'em_'.$post_type.'_metaboxes',
				array(
					'event-options-box' => array(
						'title' => __('Event Display Options', 'events-maker'),
						'callback' => array(&$this, 'event_options_cb'),
						'post_type' => $post_type,
						'context' => 'side',
						'priority' => 'core'
					),
					'event-date-time-box' => array(
						'title' => __('Event Date and Time', 'events-maker'),
						'callback' => array(&$this, 'event_date_time_cb'),
						'post_type' => $post_type,
						'context' => 'normal',
						'priority' => 'high'
					),
					'event-cost-tickets-box' => array(
						'title' => __('Event Tickets', 'events-maker'),
						'callback' => array(&$this, 'event_tickets_cb'),
						'post_type' => $post_type,
						'context' => 'normal',
						'priority' => 'high'
					)
				)
			);
		}
	}


	/**
	 * 
	*/
	public function admin_scripts_styles($page)
	{
		$screen = get_current_screen();

		if(($page === 'post-new.php' || $page === 'post.php') && in_array($screen->post_type, apply_filters('em_event_post_type', array('event'))))
		{
			global $wp_locale;

			wp_register_script(
				'events-maker-datetimepicker',
				EVENTS_MAKER_URL.'/assets/jquery-timepicker-addon/jquery-ui-timepicker-addon.min.js',
				array('jquery')
			);
			
			$lang = str_replace('_', '-', get_locale());
			$lang_exp = explode('-', $lang);

			if(file_exists(EVENTS_MAKER_PATH.'assets/jquery-timepicker-addon/i18n/jquery-ui-timepicker-'.$lang.'.js'))
				$lang_path = EVENTS_MAKER_URL.'/assets/jquery-timepicker-addon/i18n/jquery-ui-timepicker-'.$lang.'.js';
			elseif(file_exists(EVENTS_MAKER_PATH.'assets/jquery-timepicker-addon/i18n/jquery-ui-timepicker-'.$lang_exp[0].'.js'))
				$lang_path = EVENTS_MAKER_URL.'/assets/jquery-timepicker-addon/i18n/jquery-ui-timepicker-'.$lang_exp[0].'.js';
			
			if(isset($lang_path))
			{
				wp_register_script(
					'events-maker-datetimepicker-localization',
					$lang_path,
					array('jquery', 'events-maker-datetimepicker')
				);

				wp_enqueue_script('events-maker-datetimepicker-localization');
			}

			wp_register_script(
				'events-maker-admin-post',
				EVENTS_MAKER_URL.'/js/admin-post.js',
				array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-slider', 'events-maker-datetimepicker')
			);

			wp_enqueue_script('events-maker-admin-post');

			wp_localize_script(
				'events-maker-admin-post',
				'emPostArgs',
				array(
					'ticketsFields' => $this->tickets_fields,
					'ticketDelete' => __('Delete', 'events-maker'),
					'currencySymbol' => em_get_currency_symbol(),
					'startDateTime' => __('Start date/time', 'events-maker'),
					'endDateTime' => __('End date/time', 'events-maker'),
					'dateDelete' => __('Delete', 'events-maker'),
					'deleteTicket' => __('Are you sure you want to delete this ticket?', 'events-maker'),
					'deleteCustomOccurrence' => __('Are you sure you want to delete this occurrence?', 'events-maker'),
					'firstWeekDay' => $this->options['general']['first_weekday'],
					'monthNames' => array_values($wp_locale->month),
					'monthNamesShort' => array_values($wp_locale->month_abbrev),
					'dayNames' => array_values($wp_locale->weekday),
					'dayNamesShort' => array_values($wp_locale->weekday_abbrev),
					'dayNamesMin' => array_values($wp_locale->weekday_initial),
					'isRTL' => $wp_locale->is_rtl(),
					'day' => __('day', 'events-maker'),
					'days' => __('days', 'events-maker'),
					'week' => __('week', 'events-maker'),
					'weeks' => __('weeks', 'events-maker'),
					'month' => __('month', 'events-maker'),
					'months' => __('months', 'events-maker'),
					'year' => __('year', 'events-maker'),
					'years' => __('years', 'events-maker')
				)
			);

			wp_register_style(
				'events-maker-admin',
				EVENTS_MAKER_URL.'/css/admin.css'
			);

			wp_register_style(
				'events-maker-datetimepicker',
				EVENTS_MAKER_URL.'/assets/jquery-timepicker-addon/jquery-ui-timepicker-addon.min.css'
			);

			wp_enqueue_style('events-maker-admin');
			wp_enqueue_style('events-maker-wplike');
			wp_enqueue_style('events-maker-datetimepicker');
		}
	}


	/**
	 * 
	*/
	public function add_events_meta_boxes($post_type, $post)
	{
		if (isset($post_type) && in_array($post_type, apply_filters('em_event_post_type', array('event'))))
		{
		
			global $wp_meta_boxes;
	
			foreach($this->metaboxes as $key)
			{
				foreach($key as $id => $metabox)
				{
					if($id === 'event-cost-tickets-box' && !$this->options['general']['use_event_tickets'])
						continue;
					else
						add_meta_box($id, $metabox['title'], $metabox['callback'], $metabox['post_type'], $metabox['context'], $metabox['priority']);
				}
			}
	
			$found_priority = false;
	
			foreach(array('low', 'core', 'high') as $priority)
			{
				if(isset($wp_meta_boxes[$post->post_type]['side'][$priority]['postimagediv']))
				{
					$found_priority = $priority;
					$post_image_box = $wp_meta_boxes[$post->post_type]['side'][$priority]['postimagediv'];
					break;
				}
			}
	
			$sideboxes = array();
			$event_options_box = $wp_meta_boxes[$post->post_type]['side']['core']['event-options-box'];
	
			unset($wp_meta_boxes[$post->post_type]['side']['core']['event-options-box']);
	
			if($found_priority !== false)
				unset($wp_meta_boxes[$post->post_type]['side'][$found_priority]['postimagediv']);
	
			foreach($wp_meta_boxes[$post->post_type]['side']['core'] as $id => $sidebox)
			{
				$sideboxes[$id] = $sidebox;
	
				if($id === 'submitdiv')
				{
					$sideboxes['event-options-box'] = $event_options_box;
	
					if($found_priority !== false)
						$sideboxes['postimagediv'] = $post_image_box;
				}
			}
	
			$wp_meta_boxes[$post->post_type]['side']['core'] = $sideboxes;
		}
	}


	/**
	 * 
	*/
	public function event_date_time_cb($post)
	{
		wp_nonce_field('events_maker_save_event_datetime', 'event_nonce_datetime');

		// defaults
		$options = array('weekly' => '', 'monthly' => '');
		$selected = '';

		// datetimes
		$current_date = date('Y-m-d', current_time('timestamp'));
		$current_time = date('H:i', current_time('timestamp'));
		$today = (int)date('N', current_time('timestamp'));

		// metas
		$event_all_day = get_post_meta($post->ID, '_event_all_day', true);
		$event_start_date = explode(' ', get_post_meta($post->ID, '_event_start_date', true));
		$event_end_date = explode(' ', get_post_meta($post->ID, '_event_end_date', true));
		$event_recurrence = get_post_meta($post->ID, '_event_recurrence', true);

		// edit event?
		if($event_all_day !== '')
		{
			$event_all_day = (bool)(int)$event_all_day;

			if($event_all_day)
				$event_start_date[1] = $event_end_date[1] = '';
		}
		else
		{
			$event_all_day = false;

			$event_start_date[0] = $event_end_date[0] = $current_date;
			$event_start_date[1] = $event_end_date[1] = $current_time;
		}

		// creating new event?
		if(!is_array($event_recurrence))
		{
			$event_recurrence = array(
				'type' => 'once',
				'repeat' => 1,
				'until' => $current_date,
				'weekly_days' => array($today),
				'monthly_day_type' => 1,
				'separate_end_date' => array()
			);
		}

		$html = '
		<div class="date-time-row">
			<label for="event_start_date">'.__('Start date/time', 'events-maker').':</label> <input id="event_start_date" type="text" name="event_start_date" value="'.esc_attr($event_start_date[0]).'"/> <input id="event_start_time" type="text" name="event_start_time" value="'.esc_attr(isset($event_start_date[1]) ? substr($event_start_date[1], 0, 5) : '').'" '.($event_all_day ? 'style="display: none;"' : '').'/>
		</div>
		<div class="date-time-row">
			<label for="event_end_date">'.__('End date/time', 'events-maker').':</label> <input id="event_end_date" type="text" name="event_end_date" value="'.esc_attr($event_end_date[0]).'"/> <input id="event_end_time" type="text" name="event_end_time" value="'.esc_attr(isset($event_end_date[1]) ? substr($event_end_date[1], 0, 5) : '').'" '.($event_all_day ? 'style="display: none;"' : '').'/>
		</div>
		<div>
			<label for="event_all_day">'.__('All-day event?', 'events-maker').'</label> <input id="event_all_day" type="checkbox" name="event_all_day" '.checked($event_all_day, true, false).'/>
		</div>
		<div class="date-time-row">
			<label for="event_recurrence">'.__('Recurrence', 'events-maker').'</label> <select id="event_recurrence" name="event_recurrence[type]" class="">';

		foreach($this->recurrences as $id => $recurrence)
		{
			if($id === 'weekly')
			{
				if($event_recurrence['type'] !== 'weekly')
					$check_options = array($today);
				else
					$check_options = $event_recurrence['weekly_days'];

				global $weekday;

				$weekdays = $weekday;

				if($this->options['general']['first_weekday'] === 1)
				{
					$weekdays[7] = $weekday[0];
					unset($weekdays[0]);
				}
				else
					$weekdays = array_combine(range(1, 7), array_values($weekdays));
				
				$options['weekly'] .= '<fieldset>';
				foreach($weekdays as $day_id => $day)
				{
					$options['weekly'] .= '<input id="event_recurrence_weekday_'.$day_id.'" type="checkbox" name="event_recurrence[weekly][weekly_days]['.$day_id.']" value="'.$day_id.'" '.checked(in_array($day_id, $check_options), true, false).'/><label for="event_recurrence_weekday_'.$day_id.'">'.esc_html($day).'</label>';
				}
				$options['weekly'] .= '</fieldset>';
			}
			elseif($id === 'monthly')
			{
				if($event_recurrence['type'] !== 'monthly')
					$day_type = 1;
				else
					$day_type = (int)$event_recurrence['monthly_day_type'];

				$options['monthly'] = '<input id="event_recurrence_day_month" type="radio" name="event_recurrence[monthly][monthly_day_type]" value="1"/ '.checked($day_type, 1, false).'><label for="event_recurrence_day_month">'.__('day of the month', 'events-maker').'</label><br /><input id="event_recurrence_day_week" type="radio" name="event_recurrence[monthly][monthly_day_type]" value="2" '.checked($day_type, 2, false).'/><label for="event_recurrence_day_week">'.__('day of the week', 'events-maker').'</label>';
			}

			$html .= '
				<option value="'.esc_attr($id).'" '.selected($id, $event_recurrence['type'], false).'>'.esc_html($recurrence).'</option>';
		}

		$template = '
		'.__('Repeat every', 'events-maker').' <input type="text" size="2" maxlength="4" name="event_recurrence[repeat]" value="%1$d"/> <span class="occurrence">%2$s</span> '.__('until', 'events-maker').' <input class="event_recurrence_until" type="text" name="event_recurrence[until]" size="10" maxlength="10" value="%3$s"/>';

		if($event_recurrence['type'] === 'once' || $event_recurrence['type'] === 'daily' || $event_recurrence['type'] === 'custom')
			$repeat = _n('day', 'days', $event_recurrence['repeat'], 'events-maker');
		elseif($event_recurrence['type'] === 'weekly')
			$repeat = _n('week', 'weeks', $event_recurrence['repeat'], 'events-maker');
		elseif($event_recurrence['type'] === 'monthly')
			$repeat = _n('month', 'months', $event_recurrence['repeat'], 'events-maker');
		else
			$repeat = _n('year', 'years', $event_recurrence['repeat'], 'events-maker');

		$html .= '
			</select>
			<div id="event_recurrence_types" class="date-time-row"'.(in_array($event_recurrence['type'], array('custom', 'once'), true) ? ' style="display: none;"' : '').'>
				<div class="start">
					'.sprintf(
						$template,
						$event_recurrence['repeat'],
						$repeat,
						$event_recurrence['until']
					).'
				</div>
				<div class="weekly"'.($event_recurrence['type'] === 'weekly' ? '' : ' style="display: none;"').'>
					'.$options['weekly'].'
				</div>
				<div class="monthly"'.($event_recurrence['type'] === 'monthly' ? '' : ' style="display: none;"').'>
					'.$options['monthly'].'
				</div>
			</div>
			<div id="event_custom_occurrences" class="date-time-row"'.($event_recurrence['type'] === 'custom' ? '' : ' style="display: none;"').'>
				<div id="event-custom-template" style="display: none;">
					<div class="event-custom" style="display: none;">
						<label for="event_separate_end_date____ID___">'.__('Separate end date', 'events-maker').'</label><input class="event_separate" id="event_separate_end_date____ID___" type="checkbox" name="___EVENT_CUSTOM_DATE___[separate_end_date][___ID___]"/>
						<div class="start">
							<label for="event_start_date____ID___">'.__('Start date/time', 'events-maker').':</label> <input id="event_start_date____ID___" type="text" name="___EVENT_CUSTOM_DATE___[dates][___ID___][start][date]" value="" class="event_custom_date"/> <input id="event_start_time____ID___" type="text" name="___EVENT_CUSTOM_DATE___[dates][___ID___][start][time]" value="" class="event_custom_time"/>
							<a class="delete-custom-event button button-secondary" href="#">'.__('Delete', 'events-maker').'</a>
						</div>
						<div class="end" style="display: none">
							<label for="event_end_date____ID___">'.__('End date/time', 'events-maker').':</label> <input id="event_end_date____ID___" type="text" name="___EVENT_CUSTOM_DATE___[dates][___ID___][end][date]" value="" class="event_custom_date"/> <input id="event_end_time____ID___" type="text" name="___EVENT_CUSTOM_DATE___[dates][___ID___][end][time]" value="" class="event_custom_time"/>
						</div>
					</div>
				</div>
				<div>
					<a href="#" id="add-custom-event" class="button button-primary">'.__('Add new occurrence', 'events-maker').'</a>
				</div>';

		if($event_recurrence['type'] === 'custom')
		{
			$occurrences = get_post_meta($post->ID, '_event_occurrence_date', false);

			if(!empty($occurrences))
			{
				foreach($occurrences as $id => $occurrence)
				{
					if($id === 0)
						continue;

					$dates = explode('|', $occurrence);

					if($event_all_day)
					{
						$start['date'] = date('Y-m-d', strtotime($dates[0]));
						$start['time'] = '';
						$end['date'] = date('Y-m-d', strtotime($dates[1]));
						$end['time'] = '';
					}
					else
					{
						$start['date'] = date('Y-m-d', strtotime($dates[0]));
						$start['time'] = date('H:i', strtotime($dates[0]));
						$end['date'] = date('Y-m-d', strtotime($dates[1]));
						$end['time'] = date('H:i', strtotime($dates[1]));
					}

					$html .= '
					<div class="event-custom">
						<label for="event_separate_end_date_'.$id.'">'.__('Separate end date', 'events-maker').'</label><input class="event_separate" id="event_separate_end_date_'.$id.'" type="checkbox" name="event_recurrence[custom][separate_end_date]['.$id.']" '.checked($event_recurrence['separate_end_date'][$id - 1], true, false).'/>
						<div class="start">
							<label for="event_start_date_'.$id.'">'.__('Start date/time', 'events-maker').':</label> <input id="event_start_date_'.$id.'" type="text" name="event_recurrence[custom][dates]['.$id.'][start][date]" value="'.$start['date'].'" class="event_custom_date"/> <input id="event_start_time_'.$id.'" type="text" name="event_recurrence[custom][dates]['.$id.'][start][time]" value="'.$start['time'].'" class="event_custom_time"/>
							<a class="delete-custom-event button button-secondary" href="#">'.__('Delete', 'events-maker').'</a>
						</div>
						<div class="end"'.($event_recurrence['separate_end_date'][$id - 1] ? '' : ' style="display: none"').'>
							<label for="event_end_date_'.$id.'">'.__('End date/time', 'events-maker').':</label> <input id="event_end_date_'.$id.'" type="text" name="event_recurrence[custom][dates]['.$id.'][end][date]" value="'.$end['date'].'" class="event_custom_date"/> <input id="event_end_time_'.$id.'" type="text" name="event_recurrence[custom][dates]['.$id.'][end][time]" value="'.$end['time'].'" class="event_custom_time"/>
						</div>
					</div>';
				}
			}
		}
		else
		{
			$html .= '
				<div class="event-custom">
					<label for="event_separate_end_date_1">'.__('Separate end date', 'events-maker').'</label><input class="event_separate" id="event_separate_end_date_1" type="checkbox" name="event_recurrence[custom][separate_end_date][1]"/>
					<div class="start">
						<label for="event_start_date_1">'.__('Start date/time', 'events-maker').':</label> <input id="event_start_date_1" type="text" name="event_recurrence[custom][dates][1][start][date]" value="" class="event_custom_date"/> <input id="event_start_time_0" type="text" name="event_recurrence[custom][dates][1][start][time]" value="" class="event_custom_time"/>
						<a class="delete-custom-event button button-secondary" href="#">'.__('Delete', 'events-maker').'</a>
					</div>
					<div class="end" style="display: none">
						<label for="event_end_date_1">'.__('End date/time', 'events-maker').':</label> <input id="event_end_date_1" type="text" name="event_recurrence[custom][dates][1][end][date]" value="" class="event_custom_date"/> <input id="event_end_time_0" type="text" name="event_recurrence[custom][dates][1][end][time]" value="" class="event_custom_time"/>
					</div>
				</div>';
		}

		$html .= '
			</div>
		</div>';

		echo $html;

		do_action('em_after_metabox_event_datetime');
	}


	/**
	 * 
	*/
	public function event_tickets_cb($post)
	{
		wp_nonce_field('events_maker_save_event_tickets', 'event_nonce_tickets');

		$tickets = get_post_meta($post->ID, '_event_tickets', true);
		$free_event = (($free = get_post_meta($post->ID, '_event_free', true)) === '' ? '1' : $free);
		$html_t = '';
		$symbol = em_get_currency_symbol();

		$html = '
		<p>
			<label for="event_free">'.__('Is this a free event?', 'events-maker').'</label>
			<input id="event_free" type="checkbox" name="event_free" '.checked($free_event, '1', false).' /> 
		</p>
		<div id="event_cost_and_tickets"'.($free_event === '1' ? ' style="display: none;"' : '').'>
			<div>
				<a href="#" id="event_add_ticket" class="button button-primary">'.__('Add new ticket', 'events-maker').'</a>
			</div>';

		if(!empty($tickets) && is_array($tickets))
		{
			foreach($tickets as $id => $ticket)
			{
				$html_t .= '
				<p rel="'.$id.'">';

				foreach($this->tickets_fields as $key => $field)
				{
					$html_t .= '
					<label for="event_tickets['.$id.']['.$key.']">'.$field.':</label> <input type="text" id="event_tickets['.$id.']['.$key.']" name="event_tickets['.$id.']['.$key.']" value="'.esc_attr(isset($ticket[$key]) ? $ticket[$key] : '').'" />'.($key === 'price' ? $symbol : '');
				}

				$html_t .= '
					<a href="#" class="event_ticket_delete button button-secondary">'.__('Delete', 'events-maker').'</a>
				</p>';
			}
		}
		else
		{
			$html_t .= '
				<p rel="0">';

			foreach($this->tickets_fields as $key => $field)
			{
				$html_t .= '
					<label for="event_tickets[0]['.$key.']">'.$field.':</label> <input type="text" id="event_tickets[0]['.$key.']" name="event_tickets[0]['.$key.']" value="" />'.($key === 'price' ? $symbol : '');
			}

			$html_t .= '
					<a href="#" class="event_ticket_delete button button-secondary">'.__('Delete', 'events-maker').'</a>
				</p>';
		}

		$html .= '
			<div id="event_tickets">
			'.$html_t.'
			</div>
			<div>
				<label for="event_tickets_url">'.__('Buy tickets URL', 'events-maker').':</label> <input id="event_tickets_url" class="regular-text" type="text" name="event_tickets_url" value="'.esc_url(get_post_meta($post->ID, '_event_tickets_url', true)).'" />
			</div>
		</div>';

		echo $html;

		do_action('em_after_metabox_event_tickets');
		
	}


	/**
	 * 
	*/
	public function event_options_cb($post)
	{
		wp_nonce_field('events_maker_save_event_options', 'event_nonce_options');

		$opts = get_post_meta($post->ID, '_event_display_options', true);
		$html_arr = array();

		$html_arr['display-google-map'] = '
		<div>
			<input id="event_google_map" type="checkbox" name="event_display_options[google_map]" '.checked((isset($opts['google_map']) && $opts['google_map'] !== '' ? $opts['google_map'] : '1'), '1', false).' /> <label for="event_google_map">'.__('Display Google Map', 'events-maker').'</label>
		</div>';

		if($this->options['general']['use_event_tickets'])
		{
			$html_arr['display-tickets-info'] = '
		<div>
			<input id="event_price_tickets_info" type="checkbox" name="event_display_options[price_tickets_info]" '.checked((isset($opts['price_tickets_info']) && $opts['price_tickets_info'] !== '' ? $opts['price_tickets_info'] : '1'), '1', false).' /> <label for="event_price_tickets_info">'.__('Display Tickets Info', 'events-maker').'</label>
		</div>';
		}

		if($this->options['general']['use_organizers'])
		{
			$html_arr['display-organizer-details'] = '
		<div>
			<input id="event_display_organizer_details" type="checkbox" name="event_display_options[display_organizer_details]" '.checked((isset($opts['display_organizer_details']) && $opts['display_organizer_details'] !== '' ? $opts['display_organizer_details'] : '1'), '1', false).' /> <label for="event_display_organizer_details">'.__('Display Organizer Details', 'events-maker').'</label>
		</div>';
		}

		$html_arr['display-location-details'] = '
		<div>
			<input id="event_display_location_details" type="checkbox" name="event_display_options[display_location_details]" '.checked((isset($opts['display_location_details']) && $opts['display_location_details'] !== '' ? $opts['display_location_details'] : '1'), '1', false).' /> <label for="event_display_location_details">'.__('Display Location Details', 'events-maker').'</label>
		</div>';

		foreach(apply_filters('em_metabox_event_options', $html_arr, $opts) as $option)
		{
			echo $option;
		}

		do_action('em_after_metabox_event_options', $opts);
	}


	/**
	 * Saves event with new metaboxes
	*/
	public function save_event($post_ID)
	{
		// break if doing autosave
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return $post_ID;

		// verify if event_nonce_datetime nonce is set and valid
		if(!isset($_POST['event_nonce_datetime']) || !wp_verify_nonce($_POST['event_nonce_datetime'], 'events_maker_save_event_datetime'))
        	return $post_ID;

		// if tickets are not used do not validate them
		if($this->options['general']['use_event_tickets'])
		{
			// verify if event_nonce_tickets nonce is set and valid
			if(!isset($_POST['event_nonce_tickets']) || !wp_verify_nonce($_POST['event_nonce_tickets'], 'events_maker_save_event_tickets'))
				return $post_ID;
		}

		// verify if event_nonce_options nonce is set and valid
		if(isset($_POST['event_nonce_options']) && !wp_verify_nonce($_POST['event_nonce_options'], 'events_maker_save_event_options'))
        	return $post_ID;

		// break if current user can't edit events
		if(!current_user_can('edit_event', $post_ID))
			return $post_ID;

		// event date and time section
		$em_helper = new Events_Maker_Helper();
		$event_all_day = isset($_POST['event_all_day']) ? 1 : 0;
		$start_date_ok = false;

		update_post_meta($post_ID, '_event_all_day', $event_all_day);
		$event_start_date = $event_end_date = $current_datetime = current_time('mysql', false);
		$current_date = date('Y-m-d', current_time('timestamp', false));

		// is it all day long event?
		if($event_all_day === 1)
		{
			if($em_helper->is_valid_date($_POST['event_start_date']))
			{
				$start_date_ok = true;
				update_post_meta($post_ID, '_event_start_date', $_POST['event_start_date'].' 00:00:00');
				$event_start_date = $_POST['event_start_date'].' 00:00:00';
			}
			else
			{
				update_post_meta($post_ID, '_event_start_date', $current_datetime);
				$event_start_date = $current_datetime;
			}

			if($em_helper->is_valid_date($_POST['event_end_date']))
			{
				if($start_date_ok)
				{
					if($em_helper->is_after_date($_POST['event_end_date'], $_POST['event_start_date']))
					{
						update_post_meta($post_ID, '_event_end_date', $_POST['event_end_date'].' 00:00:00');
						$event_end_date = $_POST['event_end_date'].' 00:00:00';
					}
					else
					{
						$event_end_date = $event_start_date;
						update_post_meta($post_ID, '_event_end_date', $event_end_date);
					}
				}
				else
				{
					update_post_meta($post_ID, '_event_end_date', $_POST['event_end_date'].' 00:00:00');
					$event_end_date = $_POST['event_end_date'].' 00:00:00';
				}
			}
			else
			{
				$event_end_date = $event_start_date;
				update_post_meta($post_ID, '_event_end_date', $event_end_date);
			}
		}
		else
		{
			if($em_helper->is_valid_date($_POST['event_start_date']) && $em_helper->is_valid_time($_POST['event_start_time']))
			{
				$start_date_ok = true;
				$event_start_date = date('Y-m-d H:i:s', strtotime($_POST['event_start_date'].' '.$_POST['event_start_time']));
				update_post_meta($post_ID, '_event_start_date', $event_start_date);
			}
			else
			{
				update_post_meta($post_ID, '_event_start_date', $current_datetime);
				$event_start_date = $current_datetime;
			}

			if($em_helper->is_valid_date($_POST['event_end_date']) && $em_helper->is_valid_time($_POST['event_end_time']))
			{
				if($start_date_ok)
				{
					if($em_helper->is_after_date($_POST['event_end_date'].' '.$_POST['event_end_time'], $_POST['event_start_date'].' '.$_POST['event_start_time']))
					{
						$event_end_date = date('Y-m-d H:i:s', strtotime($_POST['event_end_date'].' '.$_POST['event_end_time']));
						update_post_meta($post_ID, '_event_end_date', $event_end_date);
					}
					else
					{
						$event_end_date = $event_start_date;
						update_post_meta($post_ID, '_event_end_date', $event_end_date);
					}
				}
				else
				{
					$event_end_date = date('Y-m-d H:i:s', strtotime($_POST['event_end_date'].' '.$_POST['event_end_time']));
					update_post_meta($post_ID, '_event_end_date', $event_end_date);
				}
			}
			else
			{
				$event_end_date = $event_start_date;
				update_post_meta($post_ID, '_event_end_date', $event_end_date);
			}
		}

		// if tickets are not used do not save them
		if($this->options['general']['use_event_tickets'])
		{
			update_post_meta($post_ID, '_event_free', (isset($_POST['event_free']) ? 1 : 0));

			$tickets = array();

			if(!isset($_POST['event_free']))
			{
				$ticket_url = (isset($_POST['event_tickets_url']) ? $_POST['event_tickets_url'] : '');

				if(isset($_POST['event_tickets']) && is_array($_POST['event_tickets']) && !empty($_POST['event_tickets']))
				{
					foreach($_POST['event_tickets'] as $id => $ticket)
					{
						$tickets_fields = array();
						$empty = 0;

						foreach($this->tickets_fields as $key => $trans)
						{
							$tickets_fields[$key] = sanitize_text_field(isset($ticket[$key]) ? $ticket[$key] : '');
							$empty += (($tickets_fields[$key] !== '') ? 1 : 0);
						}

						if($empty > 0)
							$tickets[$id] = $tickets_fields;
					}

					if(empty($tickets))
					{
						$ticket_url = '';

						update_post_meta($post_ID, '_event_free', 1);
					}

					update_post_meta($post_ID, '_event_tickets', $tickets);
				}
				else
				{
					$ticket_url = '';

					update_post_meta($post_ID, '_event_tickets', array());
					update_post_meta($post_ID, '_event_free', 1);
				}

				update_post_meta($post_ID, '_event_tickets_url', esc_url($ticket_url));
			}
			else
			{
				update_post_meta($post_ID, '_event_tickets', $tickets);
				update_post_meta($post_ID, '_event_tickets_url', '');
			}
		}

		// removes all previous occurrences
		delete_post_meta($post_ID, '_event_occurrence_date');
		delete_post_meta($post_ID, '_event_occurrence_last_date');

		// adds first occurrence even for one time events
		update_post_meta($post_ID, '_event_occurrence_date', $event_start_date.'|'.$event_end_date);

		$recurrence = $_POST['event_recurrence'];

		if(isset($this->recurrences[$recurrence['type']]))
		{
			$recurrence['until'] = date('Y-m-d', strtotime($recurrence['until']));
			$today = (int)date('N', strtotime($event_start_date));

			if($recurrence['type'] === 'once')
			{
				$event_recurrence = array(
					'type' => 'once',
					'repeat' => 1,
					'until' => $current_date,
					'weekly_days' => array($today),
					'monthly_day_type' => 1,
					'separate_end_date' => array()
				);

				// adds last occurrence (same as first)
				update_post_meta($post_ID, '_event_occurrence_last_date', $event_start_date.'|'.$event_end_date);
			}
			elseif($recurrence['type'] === 'custom')
			{
				$event_recurrence = array(
					'type' => 'custom',
					'repeat' => 1,
					'until' => $current_date,
					'weekly_days' => array($today),
					'monthly_day_type' => 1
				);

				if(!empty($recurrence['custom']['separate_end_date']))
					$separates = $recurrence['custom']['separate_end_date'];
				else
					$separates = array();

				// adds custom dates
				$event_recurrence['separate_end_date'] = $this->add_custom_dates($post_ID, $recurrence['custom']['dates'], $event_all_day, $separates, $event_start_date, $event_end_date);
			}
			else
			{
				$weekly_days = array();
				$monthly_day_type = 1;

				if($recurrence['type'] === 'weekly')
				{
					if(isset($recurrence['weekly']['weekly_days']))
					{
						foreach($recurrence['weekly']['weekly_days'] as $week_id => $weekday)
						{
							$id = (int)$week_id;

							if($id >= 1 && $id <= 7)
								$weekly_days[] = $id;
						}

						if(empty($weekly_days))
							$weekly_days = array($today);
					}
					else
						$weekly_days = array($today);
				}
				elseif($recurrence['type'] === 'monthly')
				{
					$weekly_days = array($today);

					if(isset($recurrence['monthly']['monthly_day_type']))
					{
						$id = (int)$recurrence['monthly']['monthly_day_type'];

						$monthly_day_type = ($id === 2 ? 2 : 1);
					}
				}

				$event_recurrence = array(
					'type' => $recurrence['type'],
					'repeat' => (($repeat = (int)$recurrence['repeat']) > 0 ? $repeat : 1),
					'until' => $recurrence['until'],
					'weekly_days' => $weekly_days,
					'monthly_day_type' => $monthly_day_type,
					'separate_end_date' => array()
				);

				// creates occurrences
				$this->create_recurrences($post_ID, $event_start_date, $event_end_date, $recurrence['type'], $recurrence['repeat'], $recurrence['until'], $weekly_days, $monthly_day_type);
			}
		}

		update_post_meta($post_ID, '_event_recurrence', $event_recurrence);

		$event_display_options = apply_filters('em_event_display_options', array('google_map', 'price_tickets_info', 'display_organizer_details', 'display_location_details'));

		if(is_array($event_display_options) && !empty($event_display_options))
		{
			$event_display_options_arr = array();

			foreach($event_display_options as $event_option)
			{
				$event_display_options_arr[$event_option] = isset($_POST['event_display_options'][$event_option]) ? 1 : 0;
			}

			update_post_meta($post_ID, '_event_display_options', $event_display_options_arr);
		}
		else
			update_post_meta($post_ID, '_event_display_options', array());
	}


	private function add_custom_dates($post_id, $dates, $all_day, $separate_end_date, $start, $end)
	{
		$custom_dates = $separates = array();
		$diff = strtotime($end) - strtotime($start);
		$em_helper = new Events_Maker_Helper();

		// is it all day long event?
		if($all_day === 1)
		{
			foreach($dates as $id => $date)
			{
				if(isset($separate_end_date[$id]))
				{
					if($em_helper->is_valid_date($date['start']['date']) && $em_helper->is_valid_date($date['end']['date']) && $em_helper->is_after_date($date['end']['date'], $date['start']['date'], false))
					{
						$separates[] = true;
						$custom_dates[] = array(
							'start' => $date['start']['date'].' 00:00:00',
							'end' => $date['end']['date'].' 00:00:00'
						);
					}
				}
				else
				{
					if($em_helper->is_valid_date($date['start']['date']))
					{
						$separates[] = false;
						$custom_dates[] = array(
							'start' => $date['start']['date'].' 00:00:00',
							'end' => date('Y-m-d', strtotime($date['start']['date']) + $diff).' 00:00:00'
						);
					}
				}
			}
		}
		else
		{
			foreach($dates as $id => $date)
			{
				if(isset($separate_end_date[$id]))
				{
					if($em_helper->is_valid_date($date['start']['date']) && $em_helper->is_valid_date($date['end']['date']) && $em_helper->is_valid_time($date['start']['time']) && $em_helper->is_valid_time($date['end']['time']) && $em_helper->is_after_date($date['end']['date'].' '.$date['end']['time'], $date['start']['date'].' '.$date['start']['time'], false))
					{
						$separates[] = true;
						$custom_dates[] = array(
							'start' => date('Y-m-d H:i:s', strtotime($date['start']['date'].' '.$date['start']['time'])),
							'end' => date('Y-m-d H:i:s', strtotime($date['end']['date'].' '.$date['end']['time']))
						);
					}
				}
				else
				{
					if($em_helper->is_valid_date($date['start']['date']) && $em_helper->is_valid_time($date['start']['time']))
					{
						$time = strtotime($date['start']['date'].' '.$date['start']['time']);
						$separates[] = false;
						$custom_dates[] = array(
							'start' => date('Y-m-d H:i:s', $time),
							'end' => date('Y-m-d H:i:s', $time + $diff)
						);
					}
				}
			}
		}

		if(!empty($custom_dates))
		{
			global $wpdb;

			$query = array();

			foreach($custom_dates as $date)
			{
				$query[] = "(".$post_id.", '_event_occurrence_date', '".$date['start']."|".$date['end']."')";
			}

			if(!empty($query))
				$wpdb->query('INSERT INTO '.$wpdb->postmeta.' (post_id, meta_key, meta_value) VALUES '.implode(', ', $query));

			array_multisort($custom_dates);

			// gets last occurrence
			$last = end($custom_dates);

			// adds last occurrence
			add_post_meta($post_id, '_event_occurrence_last_date', $last['start'].'|'.$last['end']);
		}
		else
		{
			// adds last occurrence (same as first)
			add_post_meta($post_id, '_event_occurrence_last_date', $start.'|'.$end);
		}

		return $separates;
	}


	private function create_recurrences($post_id, $start, $end, $type, $repeat, $until, $weekly_days, $monthly_day_type)
	{
		$em_helper = new Events_Maker_Helper();

		if($em_helper->is_after_date($start, $end) || $em_helper->is_after_date($start, $until))
			return;

		$format = 'Y-m-d H:i:s';
		$occurrences = array();
		$diff = strtotime($end) - strtotime($start);
		$finish = strtotime($until);

		if($type === 'daily')
		{
			$repeat *= 86400;
			$current = strtotime($start);

			while($current <= $finish)
			{
				$occurrences[] = array('start' => date($format, $current), 'end' => date($format, $current + $diff));

				// creates new current date
				$current += $repeat;
			}
		}
		elseif($type === 'weekly')
		{
			$current = $start_date = strtotime($start);
			$weekdays = array();
			$repeat *= 7;
			$i = $counter = 0;
			$day = date('N', $current);

			foreach($weekly_days as $weekday)
			{
				$weekdays[] = $weekday - $day;
			}

			$number_of_days = count($weekdays);

			while($current <= $finish)
			{
				if(($more_days = ($weekdays[$i++] + $repeat * $counter)) >= 0)
				{
					// creates new current date
					$current = strtotime('+'.$more_days.' days', $start_date);

					if($current <= $finish)
						$occurrences[] = array('start' => date($format, $current), 'end' => date($format, $current + $diff));
				}

				if($i === $number_of_days)
				{
					$counter++;
					$i = 0;
				}
			}
		}
		elseif($type === 'monthly')
		{
			$current = strtotime($start);
			$start_date = date_parse($start);

			// is it day of week?
			if($monthly_day_type === 2)
			{
				// 1-7
				$day_of_week = date('N', $current);

				// 1-31 / 7 rounded down
				$which = (int)floor(date('j', $current) / 7);

				// time
				$diff_time = $start_date['second'] + $start_date['minute'] * 60 + $start_date['hour'] * 3600;
			}
			else
				$diff_time = 0;

			while($current <= $finish)
			{
				$occurrences[] = array('start' => date($format, $current + $diff_time), 'end' => date($format, $current + $diff + $diff_time));

				// current date
				$date = date_parse(date('Y-m-d', $current));

				// creates new current date
				if($start_date['day'] > 28)
				{
					$values = date('Y-m-t', strtotime('+'.$repeat.' months', strtotime($date['year'].'-'.$date['month'].'-01')));
					$values = explode('-', $values);

					if($values[2] < $date['day'])
						$current = strtotime($values[0].'-'.$values[1].'-'.$values[2]);
					else
						$current = strtotime($values[0].'-'.$values[1].'-'.$start_date['day']);
				}
				else
					$current = strtotime('+'.$repeat.' months', $current);

				if($monthly_day_type === 2)
				{
					// due to PHP 5.2 bugs lets do some craziness
					$year = date('Y', $current);
					$month = date('m', $current);
					$day_of_month = date('N', strtotime($year.'-'.$month.'-01'));

					if($day_of_month <= $day_of_week)
						$number = $day_of_week - $day_of_month + 1;
					else
						$number = $day_of_week - $day_of_month + 8;

					$number += (7 * $which);

					// is it valid date?
					while(!checkdate((int)$month, $number, $year))
					{
						$number -= 7;
					}

					$current = strtotime($year.'-'.$month.'-'.str_pad($number, 2, '0', STR_PAD_LEFT));
				}
			}
		}
		elseif($type === 'yearly')
		{
			$current = strtotime($start);

			while($current <= $finish)
			{
				$occurrences[] = array('start' => date($format, $current), 'end' => date($format, $current + $diff));

				// creates new current date
				$current = strtotime('+1 year', $current);
			}
		}

		if(!empty($occurrences))
		{
			global $wpdb;

			$query = array();

			foreach($occurrences as $id => $occurrence)
			{
				if($id > 0)
					$query[] = "(".$post_id.", '_event_occurrence_date', '".$occurrence['start']."|".$occurrence['end']."')";
			}

			if(!empty($query))
				$wpdb->query('INSERT INTO '.$wpdb->postmeta.' (post_id, meta_key, meta_value) VALUES '.implode(', ', $query));
		}

		// gets last occurrence
		$last = end($occurrences);

		// adds last occurrence
		add_post_meta($post_id, '_event_occurrence_last_date', $last['start'].'|'.$last['end']);
	}
}
?>