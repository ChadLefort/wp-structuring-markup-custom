<?php
/**
 * Schema.org Display
 *
 * @author  Kazuya Takami
 * @author  Justin Frydman
 * @version 2.3.3
 * @since   1.0.0
 */
class Structuring_Markup_Display {

	/**
	 * Constructor Define.
	 *
	 * @since 1.0.0
	 */
	public function __construct () {
		$db = new Structuring_Markup_Admin_Db();
		$this->set_schema( $db );
	}

	/**
	 * Setting schema.org
	 *
	 * @since   1.0.0
	 * @version 2.1.0
	 * @param   Structuring_Markup_Admin_Db $db
	 */
	private function set_schema ( Structuring_Markup_Admin_Db $db ) {
		$this->get_schema_data( $db, 'all' );
		if ( is_home() ) {
			$this->get_schema_data( $db, 'home' );
		}
		if ( is_single() && get_post_type() === 'post' ) {
			$this->get_schema_data( $db, 'post' );
		}
		if ( is_singular( 'schema_event_post' ) ) {
			$this->get_schema_data( $db, 'event' );
		}
		if ( is_page() ) {
			$this->get_schema_data( $db, 'page' );
		}
	}

	/**
	 * Setting JSON-LD Template
	 *
	 * @since   1.0.0
	 * @version 2.4.0
	 * @param   Structuring_Markup_Admin_Db $db
	 * @param   string $output
	 */
	private function get_schema_data ( Structuring_Markup_Admin_Db $db, $output ) {
		$results = $db->get_select_options( $output );

		if ( isset( $results ) ) {
			foreach ( $results as $row ) {
				if ( isset( $row->type ) && isset( $row->activate ) && $row->activate === 'on' ) {
					switch ( $row->type ) {
						case 'local_business':
							if ( isset( $row->options ) && $row->options ) {
								$this->set_schema_local_business( unserialize( $row->options ) );
							}
							break;
					}
				}
			}
		}
	}

	/**
	 * Setting JSON-LD Template
	 *
	 * @since 1.0.0
	 * @param array $args
	 */
	private function set_schema_json ( array $args ) {
		echo '<script type="application/ld+json">' , PHP_EOL;
		echo json_encode( $args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) , PHP_EOL;
		echo '</script>' , PHP_EOL;
	}

	/**
	 * Setting schema.org LocalBusiness
	 *
	 * @since   2.3.0
	 * @version 2.4.0
	 * @param   array $options
	 */
	private function set_schema_local_business ( array $options ) {

		/** weekType defined. */
		$week_array = array(
			array("type" => "Mo", "display" => "Monday"),
			array("type" => "Tu", "display" => "Tuesday"),
			array("type" => "We", "display" => "Wednesday"),
			array("type" => "Th", "display" => "Thursday"),
			array("type" => "Fr", "display" => "Friday"),
			array("type" => "Sa", "display" => "Saturday"),
			array("type" => "Su", "display" => "Sunday")
		);

		$args = array(
			"@context"  => "http://schema.org",
			"@type"     => isset( $options['name'] ) ? esc_html( $options['business_type'] ) : "",
			"name"      => isset( $options['name'] ) ? esc_html( $options['name'] ) : "",
			"logo"     => isset( $options['logo'] ) ? esc_url( $options['logo'] ) : "",
			"url"       => isset( $options['name'] ) ? esc_url( $options['url'] ) : "",
			"telephone" => isset( $options['name'] ) ? esc_html( $options['telephone'] ) : ""
		);

		if ( isset( $options['food_active'] ) && $options['food_active'] === 'on' ) {
			if ( isset( $options['menu'] ) && $options['menu'] !== '' ) {
				$args['menu'] = esc_url( $options['menu'] );
			}
			if ( isset( $options['accepts_reservations'] ) && $options['accepts_reservations'] === 'on' ) {
				$args['acceptsReservations'] = "True";
			} else {
				$args['acceptsReservations'] = "False";
			}
		}

		$address_array["address"] = array(
			"@type"           => "PostalAddress",
			"streetAddress"   => isset( $options['name'] ) ? esc_html( $options['street_address'] ) : "",
			"addressLocality" => isset( $options['name'] ) ? esc_html( $options['address_locality'] ) : "",
			"addressRegion"   => isset( $options['name'] ) ? esc_html( $options['address_region'] ) : "",
			"postalCode"      => isset( $options['name'] ) ? esc_html( $options['postal_code'] ) : "",
			"addressCountry"  => isset( $options['name'] ) ? esc_html( $options['address_country'] ) : ""
		);
		$args = array_merge( $args, $address_array );

		if ( isset( $options['geo_active'] ) && $options['geo_active'] === 'on' ) {
			$geo_array["geo"] = array(
				"@type"     => "GeoCoordinates",
				"latitude"  => isset( $options['name'] ) ? esc_html( floatval( $options['latitude'] ) ) : "",
				"longitude" => isset( $options['name'] ) ? esc_html( floatval( $options['longitude'] ) ) : ""
			);
			$args = array_merge( $args, $geo_array );
		}

		/* openingHours */
		$active_days = array();
		foreach ( $week_array as $value ) {
			if ( isset( $options[$value['type']] ) && $options[$value['type']] === 'on' ) {
				$active_days[$value['type']] = $options['week'][$value['type']];
			}
		}

		if( !empty( $active_days ) ) {

			$obj = new Structuring_Markup_Opening_Hours( $active_days );
			$opening_hours = $obj->display();

			$opening_array["openingHours"] = array();

			foreach( $opening_hours as $value ) {
				$opening_array["openingHours"][] = $value;
			}

			$args = array_merge( $args, $opening_array );
		}

		/** Social Profiles */
		if ( isset( $options['social'] ) ) {
			$socials["sameAs"] = array();

			foreach ( $options['social'] as $value ) {
				if ( !empty( $value ) ) {
					$socials["sameAs"][] = esc_html( $value );
				}
			}
			$args = array_merge( $args, $socials );
		}

		$this->set_schema_json( $args );
	}
}
