<?php
/**
 * Events Maker public functions
 *
 * Functions available for users and developers. May not be replaced
 *
 * @author 	Digital Factory
 * @package Events Maker/Functions
 * @version 1.1.0
 */

if(!defined('ABSPATH')) exit;


/**
 * Get events
 * @param 	mixed
 * @return 	array
 */
function em_get_events($args = array())
{
	$defaults = array(
		'post_type' => 'event',
		'suppress_filters' => false,
		'posts_per_page' => -1
	);
	$args = wp_parse_args($args, $defaults);
	
	return apply_filters('em_get_events', get_posts($args));
}

/**
 * Get single event
 * @param 	int 	$post_id 	(default: 0 or current post ID)
 * @return 	object
 */
function em_get_event($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	return apply_filters('em_get_event', (($post = get_post((int)$post_id, 'OBJECT', 'raw')) !== NULL ? $post : NULL), $post_id);
}

/**
 * Get single event occurrences
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @param 	string 	$period		'all'. 'future' or 'past' (default: 'all')
 * @param 	string 	$orderby	(default: 'asc')
 * @return 	array
 */

function em_get_occurrences($post_id = 0, $period = 'all', $orderby = 'asc', $limit = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is this a reccuring event?
	if(!em_is_recurring($post_id))
		return false;
	
	$defaults = array(
		'period'   	=> $period,
		'orderby' 	=> $orderby,
		'limit'   	=> absint($limit)
	);
	
	$args = array();
	$args = apply_filters('em_get_occurrences_args', wp_parse_args($args, $defaults));
	
	$all_occurrences = get_post_meta($post_id, '_event_occurrence_date', false);

	if($args['orderby'] === 'asc')
		sort($all_occurrences, SORT_STRING);
	else
		rsort($all_occurrences, SORT_STRING);

	$occurrences = array();
	$now = current_time('timestamp');

	if($args['period'] === 'all')
	{
		foreach($all_occurrences as $id => $occurrence)
		{
			$dates = explode('|', $occurrence);
			$occurrences[] = array('start' => $dates[0], 'end' => $dates[1]);
		}
	}
	elseif($args['period'] === 'future')
	{
		foreach($all_occurrences as $id => $occurrence)
		{
			$dates = explode('|', $occurrence);

			if($now < strtotime($dates[0]) && $now < strtotime($dates[1]))
				$occurrences[] = array('start' => $dates[0], 'end' => $dates[1]);
		}
	}
	else
	{
		foreach($occurrences_start as $id => $occurrence)
		{
			$dates = explode('|', $occurrence);

			if($now > strtotime($dates[0]) && $now > strtotime($dates[1]))
				$occurrences[] = array('start' => $dates[0], 'end' => $dates[1]);
		}
	}

	if($limit > 0)
		return $occurrences = array_slice($occurrences, 0, $args['limit']);

	return apply_filters('em_get_occurrences', $occurrences, $post_id);
}

/**
 * Get event first occurrence
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @return 	array
 */
function em_get_first_occurrence($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is this a reccuring event?
	if(!em_is_recurring($post_id))
		return false;
	
	return apply_filters('em_get_first_occurrence', array('start' => get_post_meta($post_id, '_event_start_date', true), 'end' => get_post_meta($post_id, '_event_end_date', true)), $post_id);
}

/**
 * Get event last occurrence
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @return 	array
 */
function em_get_last_occurrence($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is this a reccuring event?
	if(!em_is_recurring($post_id))
		return false;

	$dates = explode('|', get_post_meta($post_id, '_event_occurrence_last_date', true));

	return apply_filters('em_get_next_occurrence', array('start' => $dates[0], 'end' => $dates[1]), $post_id);
}

/**
 * Get event next occurrence
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @return 	array
 */
function em_get_next_occurrence($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is this a reccuring event?
	if(!em_is_recurring($post_id))
		return false;
	
	$occurence = em_get_occurrences($post_id, 'future');
	
	if (empty($occurence[0]))
		return false;
	
	return apply_filters('em_get_next_occurrence', $occurence[0], $post_id);
}

/**
 * Get event active occurrence
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @return 	array
 */
function em_get_active_occurrence($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is this a reccuring event?
	if(!em_is_recurring($post_id))
		return false;

	$occurrences = get_post_meta($post_id, '_event_occurrence_date', false);
	sort($occurrences, SORT_STRING);

	$now = current_time('timestamp');

	foreach($occurrences as $id => $occurrence)
	{
		$dates = explode('|', $occurrence);

		if($now > strtotime($dates[0]) && $now < strtotime($dates[1]))
			return array('start' => $dates[0], 'end' => $dates[1]);
	}

	return false;
}

/**
 * Get event occurrence date when in loop
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @return 	array
 */
function em_get_current_occurrence($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is it reccuring event?
	if(!em_is_recurring($post_id))
		return false;
	
	global $post;
	
	if(empty($post->event_occurrence_start_date))
		return false;
	
	return apply_filters('em_get_current_occurrence', array(
		'start' => $post->event_occurrence_start_date,
		'end' => $post->event_occurrence_end_date
	), $post_id);
}

/**
 * Get the event date
 * @param 	int		$post_id	(default: 0 or current post ID)
 * @param 	array	$args		(default: '')
 * @return 	string or array
 */
function em_get_the_date($post_id = 0, $args = array())
{

	$post_id 	= (int)(empty($post_id) ? get_the_ID() : $post_id);
	$date 		= array();
	
	if(empty($post_id))
		return false;
	
	$defaults = array(
		'range'   	=> '',		// start, end
		'output' 	=> '',		// datetime, date, time
		'format' 	=> '',		// date or time format
	);
	$args = apply_filters('em_get_the_date_args', wp_parse_args($args, $defaults));
 
	$occurrence = em_get_current_occurrence($post_id);
	
	// if current event is event occurrence?
	if(!empty($occurrence))
	{
		$start_date = $occurrence['start'];
		$end_date = $occurrence['end'];
	}
	else
	{
		$start_date = get_post_meta($post_id, '_event_start_date', true);
		$end_date = get_post_meta($post_id, '_event_end_date', true);
	}

	if(empty($start_date))
		return false;
	
	// date format options
	$options = get_option('events_maker_general');
	$date_format = $options['datetime_format']['date'];
	$time_format = $options['datetime_format']['time'];
	
	if(!empty($args['format']) && is_array($args['format']))
	{
		$date_format = (!empty($args['format']['date']) ? $args['format']['date'] : $date_format);
		$time_format = (!empty($args['format']['time']) ? $args['format']['time'] : $time_format);
	}
	
	// what is there to display?
	if(!empty($args['range']))
	{
		if($args['range'] === 'start' && !empty($start_date))
			$date['start'] = $start_date;
		elseif ($args['range'] === 'end' && !empty($end_date))
			$date['end'] = $end_date;
	}
	else
		$date = array('start' => $start_date, 'end' => $end_date);

	// what part of the date to display and how to format it?
	if(!empty($date))
	{
		foreach ($date as $key => $value)
		{
			if ($args['output'] === 'date') // output date only
			{
				$date[$key] = !empty($args['format']) ? em_format_date($value, 'date', $args['format']) : em_format_date($value, 'date', $date_format);
			} 
			elseif ($args['output'] === 'time') // output time only
			{
				$date[$key] = !empty($args['format']) ? em_format_date($value, 'time', $args['format']) : em_format_date($value, 'time', $time_format);
			}
			else // output date & time
			{
				$date[$key] = !empty($args['format']) ? em_format_date($value, 'datetime', $args['format']) : em_format_date($value, 'datetime', $date_format.' '.$time_format);
			}
		}
	}

	return apply_filters('em_get_the_date', $date, $post_id, $args);
}

/**
 * Get event start date
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @param 	string 	$type		(default: 'datetime')
 * @return 	string
 */
function em_get_the_start($post_id = 0, $type = 'datetime')
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	$date = get_post_meta((int)$post_id, '_event_start_date', true);

	return apply_filters('em_get_the_start', (!empty($date) ? em_format_date($date, $type) : false), $post_id);
}

/**
 * Get event end date
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @param 	string 	$type		(default: 'datetime')
 * @return 	string
 */
function em_get_the_end($post_id = 0, $type = 'datetime')
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	$date = get_post_meta((int)$post_id, '_event_end_date', true);

	return apply_filters('em_get_the_end', (!empty($date) ? em_format_date($date, $type) : false), $post_id);
}

/**
 * Format given date
 * @param 	string 	$date		(default: NULL)
 * @param 	string 	$type		(default: 'datetime')
 * @param 	string 	$format		(default: false)
 * @return 	string
 */
function em_format_date($date = NULL, $type = 'datetime', $format = false)
{
	if($date === NULL)
		$date = current_time('timestamp', false);

	$options = get_option('events_maker_general');
	$date_format = $options['datetime_format']['date'];
	$time_format = $options['datetime_format']['time'];

	if(is_array($format))
	{
		$date_format = (!empty($format['date']) ? $format['date'] : $date_format);
		$time_format = (!empty($format['time']) ? $format['time'] : $time_format);
	} 
	elseif(!empty($format))
	{
		if($type === 'date')
			$date_format = $format;
		if($type === 'time')
			$time_format = $format;
	}

	if($type === 'date')
		return date_i18n($date_format, strtotime($date));
	elseif($type === 'time')
		return date($time_format, strtotime($date));
	else
		return date_i18n($date_format.' '.$time_format, strtotime($date));
}

/**
 * Check if given event is an all day event
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @return 	bool
 */
function em_is_all_day($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);
	
	if(empty($post_id))
		return false;

	return apply_filters('em_is_all_day', (get_post_meta((int)$post_id, '_event_all_day', true) === '1' ? true : false), $post_id);
}

/**
 * Check if given event is a reccurring event
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @return 	bool
 */
function em_is_recurring($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	$recurrence = get_post_meta($post_id, '_event_recurrence', true);

	return apply_filters(
		'em_is_recurring',
		$recurrence['type'] === 'once' ? false : true,
		$post_id
	);
}

/**
 * Check if given event is a free event
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @return 	bool
 */
function em_is_free($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);
	
	if(empty($post_id))
		return false;

	return apply_filters('em_is_free', (get_post_meta((int)$post_id, '_event_free', true) === '1' ? true : false), $post_id);
}

/**
 * Get the ticket data for a given event
 * @param 	int 	$post_id	(default: 0 or current post ID)
 * @return 	void?
 */
function em_get_tickets($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);
	
	if(empty($post_id))
		return false;
	
	if(em_is_free($post_id) === false)
	{
		$tickets = get_post_meta((int)$post_id, '_event_tickets', true);

		if(isset($tickets) && is_array($tickets))
			return $tickets;
		else
			return NULL;
	}
	else
		return false;
}

/**
 * Get the currency symbol and append it to the price
 * @param 	string 	$price		(default: '')
 * @return 	string
 */
function em_get_currency_symbol($price = '')
{
	$options = get_option('events_maker_general');

	$symbol = ($options['currencies']['symbol'] === '' ? strtoupper($options['currencies']['code']) : $options['currencies']['symbol']);

	if(is_numeric($price))
	{
		switch($options['currencies']['format'])
		{
			case 1:
				$price = number_format($price, 2, '.', ',');
				break;

			case 2:
				$price = number_format($price, 0, '', ',');
				break;

			case 3:
				$price = number_format($price, 0, '', '');
				break;

			case 4:
				$price = number_format($price, 2, '.', '');
				break;

			case 5:
				$price = number_format($price, 2, ',', ' ');
				break;

			case 6:
				$price = number_format($price, 2, '.', ' ');
				break;
		}

		return apply_filters('em_get_currency_symbol', ($options['currencies']['position'] === 'after' ? $price.' '.$symbol : $symbol.' '.$price), $price);
	}
	else
		return apply_filters('em_get_currency_symbol', $symbol, $price);
}

/**
 * Get all event locations
 * @param 	array 	$args		(default: array('fields' => 'all'))
 * @return 	string
 */
function em_get_locations($args = array('fields' => 'all'))
{
	if(taxonomy_exists('event-location'))
	{
		$locations = get_terms('event-location', $args);

		if(isset($args['fields']) && $args['fields'] === 'all')
		{
			foreach($locations as $id => $location)
			{
				$locations[$id]->location_meta = get_option('event_location_'.$location->term_id);
			}
		}

		return $locations;
	}
	else
		return false;
}

/**
 * Get single event location data
 * @param 	int 	$term_id	(default: NULL)
 * @return 	string
 */
function em_get_location($term_id = NULL)
{
	if(taxonomy_exists('event-location'))
	{
		if($term_id === NULL)
		{
			$term = get_queried_object();

			if(is_tax() && is_object($term) && isset($term->term_id))
				$term_id = $term->term_id;
			else
				return NULL;
		}

		if(($location = get_term((int)$term_id, 'event-location', 'OBJECT', 'raw')) !== NULL)
		{
			$location->location_meta = get_option('event_location_'.$location->term_id);

			return $location;
		}
		else
			return NULL;
	}
	else
		return false;
}

/**
 * Get all event locations for a given event
 * @param 	int 	$post_id	(default: 0)
 * @return 	string
 */
function em_get_locations_for($post_id = 0)
{
	if(taxonomy_exists('event-location'))
	{
		if(is_array($locations = wp_get_post_terms((int)$post_id, 'event-location')))
		{
			if(!empty($locations))
			{
				foreach($locations as $id => $location)
				{
					$locations[$id]->location_meta = get_option('event_location_'.$location->term_id);
				}

				return $locations;
			}
			else
				return array();
		}
		else
			return NULL;
	}
	else
		return false;
}

/**
 * Get all event organizers
 * @param 	array 	$args		(default: array('fields' => 'all'))
 * @return 	string
 */
function em_get_organizers($args = array('fields' => 'all'))
{
	if(taxonomy_exists('event-organizer'))
	{
		$organizers = get_terms('event-organizer', $args);

		if(isset($args['fields']) && $args['fields'] === 'all')
		{
			foreach($organizers as $id => $organizer)
			{
				$organizers[$id]->organizer_meta = get_option('event_organizer_'.$organizer->term_id);
			}
		}

		return $organizers;
	}
	else
		return false;
}

/**
 * Get single event organizer data
 * @param 	int 	$term_id	(default: NULL)
 * @return 	string
 */
function em_get_organizer($term_id = NULL)
{
	if(taxonomy_exists('event-organizer'))
	{
		if($term_id === NULL)
		{
			$term = get_queried_object();

			if(is_tax() && is_object($term) && isset($term->term_id))
				$term_id = $term->term_id;
			else
				return NULL;
		}

		if(($organizer = get_term((int)$term_id, 'event-organizer', 'OBJECT', 'raw')) !== NULL)
		{
			$organizer->organizer_meta = get_option('event_organizer_'.$organizer->term_id);

			return $organizer;
		}
		else
			return NULL;
	}
	else
		return false;
}

/**
 * Get all event organizers for a given event
 * @param 	int 	$post_id	(default: 0)
 * @return 	string
 */
function em_get_organizers_for($post_id = 0)
{
	if(taxonomy_exists('event-organizer'))
	{
		if(is_array($organizers = wp_get_post_terms((int)$post_id, 'event-organizer')))
		{
			if(!empty($organizers))
			{
				foreach($organizers as $id => $organizer)
				{
					$organizers[$id]->organizer_meta = get_option('event_organizer_'.$organizer->term_id);
				}

				return $organizers;
			}
			else
				return array();
		}
		else
			return NULL;
	}
	else
		return false;
}

/**
 * Get all event categories
 * @param 	array 	$args		(default: array())
 * @return 	string
 */
function em_get_categories($args = array())
{
	if(taxonomy_exists('event-category'))
		return get_terms('event-category', $args);
	else
		return false;
}

/**
 * Get single event category data
 * @param 	int 	$term_id	(default: NULL)
 * @return 	string
 */
function em_get_category($term_id = NULL)
{
	if(!taxonomy_exists('event-category'))
		return false;

	if($term_id === NULL)
	{
		$term = get_queried_object();

		if(is_tax() && is_object($term) && isset($term->term_id))
			$term_id = $term->term_id;
		else
			return NULL;
	}

	return (($category = get_term((int)$term_id, 'event-category', 'OBJECT', 'raw')) !== NULL ? $category : NULL);
}

/**
 * Get all event categories for a given event
 * @param 	int 	$post_id	(default: 0)
 * @return 	string
 */
function em_get_categories_for($post_id = 0)
{
	if(!taxonomy_exists('event-category'))
		return false;

	if(is_array($categories = wp_get_post_terms((int)$post_id, 'event-category')))
		return (!empty($categories) ? $categories : array());
	else
		return NULL;
}


function em_is_event_archive($datetype = '')
{
	global $wp_query;

	if(!is_post_type_archive('event'))
		return false;

	if($datetype === '')
		return true;

	if(!empty($wp_query->query_vars['event_ondate']))
	{
		$date = explode('/', $wp_query->query_vars['event_ondate']);

		if((($a = count($date)) === 1 && $datetype === 'year') || ($a === 2 && $datetype === 'month') || ($a === 3 && $datetype === 'day'))
			return true;
	}

	return false;
}


function em_get_event_date_link($year = 0, $month = 0, $day = 0)
{
	global $wp_rewrite;

	$archive = get_post_type_archive_link('event');

	$year = (int)$year;
	$month = (int)$month;
	$day = (int)$day;

	if($year === 0 && $month === 0 && $day === 0)
		return $archive;

	$em_year = $year;
	$em_month = str_pad($month, 2, '0', STR_PAD_LEFT);
	$em_day = str_pad($day, 2, '0', STR_PAD_LEFT);

	if($day !== 0)
		$link_date = compact('em_year', 'em_month', 'em_day');
	elseif($month !== 0)
		$link_date = compact('em_year', 'em_month');
	else
		$link_date = compact('em_year');

	if(!empty($archive) && $wp_rewrite->using_mod_rewrite_permalinks() && ($permastruct = $wp_rewrite->get_extra_permastruct('event_ondate')))
	{
		$archive = apply_filters('post_type_archive_link', home_url(str_replace('%event_ondate%', implode('/', $link_date), $permastruct)), 'event');
	}
	else
		$archive = add_query_arg('event_ondate', implode('-', $link_date), $archive);

	return $archive;
}


function em_display_event_taxonomy($taxonomy = '', $args = array())
{
	if(!taxonomy_exists($taxonomy))
		return false;

	return apply_filters('em_display_event_taxonomy', em_get_event_taxonomy($taxonomy, $args));
}


function em_get_event_taxonomy($taxonomy = '', $args = array())
{
	$defaults = array(
		'display_as_dropdown' => false,
		'show_hierarchy' => true,
		'order_by' => 'name',
		'order' => 'desc'
	);

	$args = apply_filters('em_get_event_taxonomy_args', wp_parse_args($args, $defaults));

	if($args['display_as_dropdown'] === false)
	{
		return wp_list_categories(
			array(
				'orderby' => $args['order_by'],
				'order' => $args['order'],
				'hide_empty' => false,
				'hierarchical' => (bool)$args['show_hierarchy'],
				'taxonomy' => $taxonomy,
				'echo' => false,
				'style' => 'list',
				'title_li' => ''
			)
		);
	}
	else
	{
		return wp_dropdown_categories(
			array(
				'orderby' => $args['order_by'],
				'order' => $args['order'],
				'hide_empty' => false, 
				'hierarchical' => (bool)$args['show_hierarchy'],
				'taxonomy' => $taxonomy,
				'hide_if_empty' => false,
				'echo' => false
			)
		);
	}
}


function em_display_event_archives($args = array())
{
	global $wp_locale;

	$defaults = array(
		'display_as_dropdown' => false,
		'show_post_count' => true,
		'type' => 'monthly',
		'order' => 'desc',
		'limit' => 0
	);
	$args = wp_parse_args($args, $defaults);
	
	$archives = $counts = array();
	$cut = ($args['type'] === 'yearly' ? 4 : 7);

	$events = get_posts(
		array(
			'post_type' => 'event',
			'suppress_filters' => false,
			'posts_per_page' => -1
		)
	);

	foreach($events as $event)
	{
		$startdatetime = get_post_meta($event->ID, '_event_start_date', true);
		$enddatetime = get_post_meta($event->ID, '_event_end_date', true);

		if(!empty($startdatetime))
		{
			$start_ym = substr($startdatetime, 0, $cut);
			$archives[] = $start_ym;

			if(isset($counts[$start_ym]))
				$counts[$start_ym]++;
			else
				$counts[$start_ym] = 1;
		}

		if(!empty($enddatetime))
		{
			$end_ym = substr($enddatetime, 0, $cut);
			$archives[] = $end_ym;

			if($start_ym !== $end_ym)
			{
				if(isset($counts[$end_ym]))
					$counts[$end_ym]++;
				else
					$counts[$end_ym] = 1;
			}
		}
	}

	$archives = array_unique($archives, SORT_STRING);
	natsort($archives);

	$elem_m = ($args['display_as_dropdown'] === true ? 'select' : 'ul');
	$elem_i = ($args['display_as_dropdown'] === true ? '<option value="%s">%s%s</option>' : '<li><a href="%s">%s</a>%s</li>');
	$html = sprintf('<%s>', $elem_m);

	foreach(array_slice(($args['order'] === 'desc' ? array_reverse($archives) : $archives), 0, ($args['limit'] === 0 ? NULL : $args['limit'])) as $archive)
	{
		if($args['type'] === 'yearly')
		{
			$link = em_get_event_date_link($archive);
			$display = $archive;
		}
		else
		{
			$date = explode('-', $archive);
			$link = em_get_event_date_link($date[0], $date[1]);
			$display = $wp_locale->get_month($date[1]).' '.$date[0];
		}

		$html .= sprintf(
			$elem_i,
			$link,
			$display,
			($args['show_post_count'] === true ? ' ('.$counts[$archive].')' : '')
		);
	}

	$html .= sprintf('</%s>', $elem_m);

	return $html;
}


function em_display_google_map($args = array(), $locations = 0)
{
	$defaults = array(
		'width' => '100%',
		'height' => '200px',
		'zoom' => 15,
		'maptype' => 'roadmap',
		'locations' => ''
	);

	$defaults_bool = array(
		'maptypecontrol' => true,
		'zoomcontrol' => true,
		'streetviewcontrol' => true,
		'overviewmapcontrol' => false,
		'pancontrol' => false,
		'rotatecontrol' => false,
		'scalecontrol' => false,
		'draggable' => true,
		'keyboardshortcuts' => true,
		'scrollzoom' => true
	);

	$defaults = array_merge($defaults, $defaults_bool, $args);

	$tmp = array();

	foreach($args as $arg => $value)
	{
		if(in_array($arg, array_keys($defaults_bool), true))
		{
			$tmp[$arg] = ($value === true ? 'on' : 'off');
		}
	}

	extract(array_merge($defaults, $tmp), EXTR_PREFIX_ALL, 'em');

	if(is_array($locations) && !empty($locations))
	{
		$locations_tmp = array();

		foreach($locations as $location)
		{
			$locations_tmp[] = (int)$location;
		}

		$locations_tmp = array_unique($locations_tmp);
		$em_locations = implode(',', $locations_tmp);
	}
	elseif(is_numeric($locations))
		$em_locations = ((int)$locations !== 0 ? (int)$locations : '');

	echo do_shortcode('[em-google-map locations="'.$em_locations.'" width="'.$em_width.'" height="'.$em_height.'" zoom="'.$em_zoom.'" maptype="'.$em_maptype.'" maptypecontrol="'.$em_maptypecontrol.'" zoomcontrol="'.$em_zoomcontrol.'" streetviewcontrol="'.$em_streetviewcontrol.'" overviewmapcontrol="'.$em_overviewmapcontrol.'" pancontrol="'.$em_pancontrol.'" rotatecontrol="'.$em_rotatecontrol.'" scalecontrol="'.$em_scalecontrol.'" draggable="'.$em_draggable.'" keyboardshortcuts="'.$em_keyboardshortcuts.'" scrollzoom="'.$em_scrollzoom.'"]');
}


function em_display_calendar($args = array())
{
	// get settings
	$options = get_option('events_maker_general');

	$defaults = array(
		'start_after' => '',
		'start_before' => '',
		'end_after' => '',
		'end_before' => '',
		'ondate' => '',
		'date_range' => 'between',
		'date_type' => 'all',
		'ticket_type' => 'all',
		'show_past_events' => (int)$options['show_past_events'],
		'show_occurrences' => 1,
		'post_type' => 'event',
		'author' => array()
	);

	// parse arguments
	$args = wp_parse_args($args, $defaults);

	// create strings
	$args['start_after'] = (string)$args['start_after'];
	$args['start_before'] = (string)$args['start_before'];
	$args['end_after'] = (string)$args['end_after'];
	$args['end_before'] = (string)$args['end_before'];
	$args['ondate'] = (string)$args['ondate'];

	// valid date range?
	if(!in_array($args['date_range'], array('between', 'outside'), true))
		$args['date_range'] = $defaults['date_range'];

	// valid date type?
	if(!in_array($args['date_type'], array('all', 'all_day', 'not_all_day'), true))
		$args['date_type'] = $defaults['date_type'];

	// valid ticket type?
	if(!in_array($args['ticket_type'], array('all', 'free', 'paid'), true))
		$args['ticket_type'] = $defaults['ticket_type'];

	// make bitwise integers
	$args['show_past_events'] = (int)(bool)$args['show_past_events'];
	$args['show_occurrences'] = (int)(bool)$args['show_occurrences'];

	$authors = array();

	if(!is_array($args['author']))
		$args['author'] = array((int)$args['author']);

	if(!(empty($args['author']) || $args['author'][0] === 0))
	{
		// some magic to handle both string and array
		$users = explode(',', implode(',', $args['author']));

		foreach($users as $author)
		{
			$authors[] = (int)$author;
		}
	}

	if(!empty($authors))
		// removes possible duplicates and makes string from it
		$args['author'] = implode(',', array_unique($authors));
	else
		$args['author'] = '';

	// display calendar
	echo do_shortcode('[em-full-calendar start_after="'.$args['start_after'].'" start_before="'.$args['start_before'].'" end_after="'.$args['end_after'].'" end_before="'.$args['end_before'].'" date_range="'.$args['date_range'].'" date_type="'.$args['date_type'].'" ticket_type="'.$args['ticket_type'].'" ondate="'.$args['ondate'].'" show_past_events="'.$args['show_past_events'].'" show_occurrences="'.$args['show_occurrences'].'" post_type="'.$args['post_type'].'" author="'.$args['author'].'"]');
}


// Get template part (for templates like the content-event.php)
function em_get_template_part($slug, $name = '') 
{
	$template = '';

	// look in yourtheme/slug-name.php and yourtheme/events-maker/slug-name.php
	if($name)
		$template = locate_template(array("{$slug}-{$name}.php", 'events-maker/'."{$slug}-{$name}.php"));

	// get default slug-name.php
	if(!$template && $name && file_exists(EVENTS_MAKER_PATH."/templates/{$slug}-{$name}.php"))
		$template = EVENTS_MAKER_PATH."/templates/{$slug}-{$name}.php";

	// if template file doesn't exist, look in yourtheme/slug.php and yourtheme/events-maker/slug.php
	if(!$template)
		$template = locate_template( array( "{$slug}.php", 'events-maker/'."{$slug}.php"));

	$template = apply_filters('em_get_template_part', $template, $slug, $name);

	if($template)
		load_template($template, false);
}