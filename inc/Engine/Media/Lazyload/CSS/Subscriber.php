<?php
namespace WP_Rocket\Engine\Media\Lazyload\CSS;

use WP_Filesystem_Direct;
use WP_Post;
use WP_Rocket\Admin\Options_Data;
use WP_Rocket\Engine\Common\Context\ContextInterface;
use WP_Rocket\Engine\Media\Lazyload\CSS\Front\ContentFetcher;
use WP_Rocket\Engine\Media\Lazyload\CSS\Front\Extractor;
use WP_Rocket\Engine\Media\Lazyload\CSS\Front\FileResolver;
use WP_Rocket\Engine\Media\Lazyload\CSS\Front\MappingFormatter;
use WP_Rocket\Engine\Media\Lazyload\CSS\Front\RuleFormatter;
use WP_Rocket\Engine\Media\Lazyload\CSS\Front\TagGenerator;
use WP_Rocket\Engine\Common\Cache\CacheInterface;
use WP_Rocket\Engine\Optimization\RegexTrait;
use WP_Rocket\Event_Management\Subscriber_Interface;
use WP_Rocket\Logger\LoggerAware;
use WP_Rocket\Logger\LoggerAwareInterface;

class Subscriber implements Subscriber_Interface, LoggerAwareInterface {
	use LoggerAware, RegexTrait;
	/**
	 * Extract background images from CSS.
	 *
	 * @var Extractor
	 */
	protected $extractor;

	/**
	 * Cache instance.
	 *
	 * @var CacheInterface
	 */
	protected $cache;

	/**
	 * Format the CSS rule inside the CSS content.
	 *
	 * @var RuleFormatter
	 */
	protected $rule_formatter;

	/**
	 * Resolves the name from the file from its URL.
	 *
	 * @var FileResolver
	 */
	protected $file_resolver;

	/**
	 * Format data for the Mapping file.
	 *
	 * @var MappingFormatter
	 */
	protected $mapping_formatter;

	/**
	 * Generate tags from the mapping of lazyloaded images.
	 *
	 * @var TagGenerator
	 */
	protected $tag_generator;

	/**
	 * Fetch content.
	 *
	 * @var ContentFetcher
	 */
	protected $fetcher;

	/**
	 * Context.
	 *
	 * @var ContextInterface
	 */
	protected $context;

	/**
	 * WPR Options.
	 *
	 * @var Options_Data
	 */
	protected $options;

	/**
	 * Instantiate class.
	 *
	 * @param Extractor        $extractor Extract background images from CSS.
	 * @param RuleFormatter    $rule_formatter Format the CSS rule inside the CSS content.
	 * @param FileResolver     $file_resolver Resolves the name from the file from its URL.
	 * @param CacheInterface   $cache Cache instance.
	 * @param MappingFormatter $mapping_formatter Format data for the Mapping file.
	 * @param TagGenerator     $tag_generator Generate tags from the mapping of lazy loaded images.
	 * @param ContentFetcher   $fetcher Fetch content.
	 * @param ContextInterface $context Context.
	 * @param Options_Data     $options WPR Options.
	 */
	public function __construct( Extractor $extractor, RuleFormatter $rule_formatter, FileResolver $file_resolver, CacheInterface $cache, MappingFormatter $mapping_formatter, TagGenerator $tag_generator, ContentFetcher $fetcher, ContextInterface $context, Options_Data $options ) {
		$this->extractor         = $extractor;
		$this->cache             = $cache;
		$this->rule_formatter    = $rule_formatter;
		$this->file_resolver     = $file_resolver;
		$this->mapping_formatter = $mapping_formatter;
		$this->tag_generator     = $tag_generator;
		$this->context           = $context;
		$this->options           = $options;
		$this->fetcher           = $fetcher;
	}

	/**
	 * Returns an array of events that this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			'rocket_generate_lazyloaded_css'        => [
				[ 'create_lazy_css_files', 18 ],
				[ 'create_lazy_inline_css', 21 ],
				[ 'add_lazy_tag', 24 ],
			],
			'rocket_buffer'                         => [ 'maybe_replace_css_images', 1002 ],
			'after_rocket_clean_domain'             => 'clear_generated_css',
			'after_rocket_clean_post'               => 'clear_generate_css_post',
			'wp_enqueue_scripts'                    => 'insert_lazyload_script',
			'rocket_exclude_js'                     => 'add_lazyload_script_exclude_js',
			'rocket_exclude_defer_js'               => 'add_lazyload_script_rocket_exclude_defer_js',
			'rocket_delay_js_exclusions'            => 'add_lazyload_script_rocket_delay_js_exclusions',
			'rocket_css_image_lazyload_images_load' => [ 'exclude_rocket_lazyload_excluded_src', 10, 2 ],
		];
	}

	/**
	 * Replace CSS images by the lazyloaded version.
	 *
	 * @param string $html page HTML.
	 * @return string
	 */
	public function maybe_replace_css_images( string $html ): string {

		if ( ! $this->context->is_allowed() ) {
			return $html;
		}

		$this->logger::debug(
			'Starting lazyload',
			[
				'type' => 'lazyload_css_bg_images',
				'data' => $html,
			]
			);

		$output = apply_filters(
			'rocket_generate_lazyloaded_css',
			[
				'html' => $html,
			]
			);

		if ( ! is_array( $output ) || ! key_exists( 'html', $output ) ) {
			$this->logger::debug(
				'Lazyload bailed out',
				[
					'type' => 'lazyload_css_bg_images',
					'data' => $html,
				]
				);
			return $html;
		}

		$this->logger::debug(
			'Ending lazyload',
			[
				'type' => 'lazyload_css_bg_images',
				'data' => $html,
			]
			);

		return $output['html'];
	}

	/**
	 * Clear the lazyload CSS files.
	 *
	 * @return void
	 */
	public function clear_generated_css() {
		$this->logger::debug(
			'Clear lazy CSS',
			[
				'type' => 'lazyload_css_bg_images',
			]
			);
		$this->cache->clear();
	}

	/**
	 * Clear the lazyload CSS linked with a post.
	 *
	 * @param WP_Post $post post cleared.
	 * @return void
	 */
	public function clear_generate_css_post( WP_Post $post ) {
		$url = get_post_permalink( $post->ID );
		$this->logger::debug(
			"Clear lazy CSS for $url",
			[
				'type' => 'lazyload_css_bg_images',
			]
			);
		if ( ! $url ) {
			$this->logger::debug(
				"Clear lazy CSS for $url",
				[
					'type' => 'lazyload_css_bg_images',
				]
				);
			return;
		}
			$this->cache->delete( $this->format_url( $url ) );
	}

	/**
	 * Insert the lazyload script.
	 *
	 * @return void
	 */
	public function insert_lazyload_script() {
		if ( ! $this->context->is_allowed() ) {
			return;
		}

		/**
		 * Filters the threshold at which lazyload is triggered
		 *
		 * @since 1.2
		 *
		 * @param int $threshold Threshold value.
		 */
		$threshold = (int) apply_filters( 'rocket_lazyload_threshold', 300 );

		wp_enqueue_script( 'rocket_lazyload_css', rocket_get_constant( 'WP_ROCKET_ASSETS_JS_URL' ) . 'lazyload-css.min.js', [], rocket_get_constant( 'WP_ROCKET_VERSION' ), true );

		wp_localize_script(
			'rocket_lazyload_css',
			'rocket_lazyload_css_data',
			[
				'threshold' => $threshold,
			]
			);
	}

	/**
	 * Create the lazyload file for CSS files.
	 *
	 * @param array $data Data sent.
	 * @return array
	 */
	public function create_lazy_css_files( array $data ): array {

		if ( ! key_exists( 'html', $data ) || ! key_exists( 'css_files', $data ) ) {
			$this->logger::debug(
				'Create lazy css files bailed out',
				[
					'type' => 'lazyload_css_bg_images',
					'data' => $data,
				]
				);
			return $data;
		}

		$html    = $data['html'];
		$html    = $this->replace_html_comments( $html );
		$mapping = [];

		$css_files = array_unique( $data['css_files'] );

		usort(
			$css_files,
			function ( $url1, $url2 ) {
				return strlen( $url1 ) < strlen( $url2 ) ? 1 : -1;
			}
			);

		$css_files_mapping = [];

		$time = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		foreach ( $css_files as $url ) {
			$placeholder = uniqid( 'url_bg_css_' );

			$html = str_replace( $url, $placeholder, $html );

			$css_files_mapping[ $url ] = $placeholder;
		}

		foreach ( $css_files as $url ) {

			if ( $this->is_excluded( $url ) ) {
				$this->logger::debug(
					"Excluded lazy css files $url",
					[
						'type' => 'lazyload_css_bg_images',
					]
				);
				continue;
			}

			$url_key = $this->format_url( $url );
			if ( ! $this->cache->has( $url_key ) ) {
				$this->logger::debug(
					"Generate lazy css files $url",
					[
						'type' => 'lazyload_css_bg_images',
					]
					);

				$file_mapping = $this->generate_css_file( $url );

				if ( empty( $file_mapping ) ) {
					$this->logger::debug(
						"Create lazy css files $url bailed out",
						[
							'type' => 'lazyload_css_bg_images',
						]
						);
					continue;
				}

				$mapping = array_merge( $mapping, $file_mapping );

			} else {
				$this->logger::debug(
					"Load lazy css files $url",
					[
						'type' => 'lazyload_css_bg_images',
					]
					);
				$mapping = array_merge( $mapping, $this->load_existing_mapping( $url ) );
			}

			$cached_url = $this->cache->generate_url( $url_key );

			$this->logger::debug(
				"Generated url lazy css files $url",
				[
					'type' => 'lazyload_css_bg_images',
					'data' => $cached_url,
				]
			);

			$parsed_query = wp_parse_url( $url, PHP_URL_QUERY );
			$queries      = [];

			if ( $parsed_query ) {
				parse_str( $parsed_query, $queries );
			}

			$queries['wpr_t'] = $time;
			$cached_url       = add_query_arg( $queries, $cached_url );

			$html = str_replace( $css_files_mapping[ $url ], $cached_url, $html );
		}

		foreach ( $css_files_mapping as $url => $placeholder ) {
			$html = str_replace( $placeholder, $url, $html );
		}

		$html = $this->restore_html_comments( $html );

		$data['html'] = $html;

		if ( ! key_exists( 'lazyloaded_images', $data ) ) {
			$data['lazyloaded_images'] = [];
		}

		$data['lazyloaded_images'] = array_merge( $data['lazyloaded_images'], $mapping );
		$data['lazyloaded_images'] = array_unique( $data['lazyloaded_images'], SORT_REGULAR );

		return $data;
	}

	/**
	 * Add the lazy tag to the current HTML.
	 *
	 * @param array $data Data sent.
	 * @return array
	 */
	public function add_lazy_tag( array $data ): array {

		if ( ! key_exists( 'html', $data ) || ! key_exists( 'lazyloaded_images', $data ) ) {
			$this->logger::debug(
				'Add lazy tag bailed out',
				[
					'type' => 'lazyload_css_bg_images',
					'data' => $data,
				]
				);
			return $data;
		}

		$lazyload_images = $data['lazyloaded_images'];

		/**
		 * Lazyload background CSS excluded urls.
		 *
		 * @param array $excluded Excluded URLs.
		 * @param array $urls List of Urls processed.
		 */
		$loaded = apply_filters( 'rocket_css_image_lazyload_images_load', [], $lazyload_images );

		$tags = $this->tag_generator->generate( $lazyload_images, $loaded );
		$this->logger::debug(
			'Add lazy tag generated',
			[
				'type' => 'lazyload_css_bg_images',
				'data' => $tags,
			]
			);
		$data['html'] = str_replace( '</head>', "$tags</head>", $data['html'] );

		return $data;
	}

	/**
	 * Generate lazy CSS for a file.
	 *
	 * @param string $url Url from the CSS.
	 * @return array
	 */
	protected function generate_css_file( string $url ) {
		$path = $this->file_resolver->resolve( $url );

		if ( ! $path ) {
			$path = $url;
		}

		$content = $this->fetcher->fetch( $path, $this->cache->generate_path( $url ) );

		if ( ! $content ) {
			return [];
		}

		$url_key = $this->format_url( $url );

		$output = $this->generate_content( $content );

		if ( ! key_exists( 'urls', $output ) || count( $output['urls'] ) === 0 ) {
			return [];
		}

		if ( ! $this->cache->set( $url_key, $output['content'] ) ) {
			return [];
		}

		$this->cache->set( $url_key . '.json', json_encode( $output['urls'] ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode

		return $output['urls'];
	}

	/**
	 * Generate lazy content for a certain content.
	 *
	 * @param string $content Content to generate lazy for.
	 * @return array
	 */
	protected function generate_content( string $content ): array {
		$urls           = $this->extractor->extract( $content );
		$formatted_urls = [];
		foreach ( $urls as $url_tags ) {
			$url_tags       = array_map(
				function ( $url_tag ) {
					/**
					 * Lazyload CSS hash.
					 *
					 * @param string $hash Lazyload CSS hash.
					 */
					$url_tag['hash'] = apply_filters( 'rocket_lazyload_css_hash',  wp_generate_uuid4(), $url_tag );
					return $url_tag;
				},
				$url_tags
				);
			$content        = $this->rule_formatter->format( $content, $url_tags );
			$formatted_urls = array_merge( $formatted_urls, $this->mapping_formatter->format( $url_tags ) );
		}

		return [
			'urls'    => $formatted_urls,
			'content' => $content,
		];
	}

	/**
	 * Load existing mapping for a URL.
	 *
	 * @param string $url Url we load mapping for.
	 * @return array
	 */
	protected function load_existing_mapping( string $url ) {
		$content = $this->cache->get( $this->format_url( $url ) . '.json' );
		$urls    = json_decode( $content, true );
		if ( ! $urls ) {
			return [];
		}
		return $urls;
	}

	/**
	 * Create the lazyload file for inline CSS.
	 *
	 * @param array $data Data sent.
	 * @return array
	 */
	public function create_lazy_inline_css( array $data ): array {

		if ( ! key_exists( 'html', $data ) || ! key_exists( 'css_inline', $data ) ) {
			$this->logger::debug(
				'Create lazy css inline bailed out',
				[
					'type' => 'lazyload_css_bg_images',
					'data' => $data,
				]
				);
			return $data;
		}

		$html = $data['html'];

		if ( ! key_exists( 'lazyloaded_images', $data ) ) {
			$data['lazyloaded_images'] = [];
		}

		foreach ( $data['css_inline'] as $content ) {

			$output = $this->generate_content( $content );

			if ( empty( $output ) ) {
				$this->logger::debug(
					"Create lazy css inline $content bailed out",
					[
						'type' => 'lazyload_css_bg_images',
						'data' => [
							'content' => $content,
							'output'  => $output,
						],
					]
					);
				continue;
			}

			$html = str_replace( $content, $output['content'], $html );

			$data['lazyloaded_images'] = array_merge( $data['lazyloaded_images'], $output['urls'] );
		}

		$data['html'] = $html;

		return $data;
	}

	/**
	 * Format a URL.
	 *
	 * @param string $url URL to format.
	 * @return string
	 */
	protected function format_url( string $url ): string {
		return strtok( $url, '?' );
	}

	/**
	 * Check of the string is excluded.
	 *
	 * @param string $string String to check.
	 * @return bool
	 */
	protected function is_excluded( string $string ) {

		/**
		 * Filters the src used to prevent lazy load from being applied.
		 *
		 * @param array $excluded_src An array of excluded src.
		 */
		$excluded_values = apply_filters( 'rocket_lazyload_excluded_src', [] );

		if ( ! is_array( $excluded_values ) ) {
			$excluded_values = (array) $excluded_values;
		}

		if ( empty( $excluded_values ) ) {
			return false;
		}

		foreach ( $excluded_values as $excluded_value ) {
			if ( strpos( $string, $excluded_value ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add the lazyload script to exclude js exclusions.
	 *
	 * @param array $js_files Exclusions.
	 * @return array
	 */
	public function add_lazyload_script_exclude_js( array $js_files ) {
		if ( ! $this->is_activated() ) {
			return $js_files;
		}

		$js_files [] = 'wp-rocket/assets/js/lazyload-css.min.js';
		return $js_files;
	}

	/**
	 * Add the lazyload script to defer js exclusions.
	 *
	 * @param array $exclude_defer_js Exclusions.
	 * @return array
	 */
	public function add_lazyload_script_rocket_exclude_defer_js( array $exclude_defer_js ) {
		if ( ! $this->is_activated() ) {
			return $exclude_defer_js;
		}

		$exclude_defer_js [] = 'wp-rocket/assets/js/lazyload-css.min.js';
		return $exclude_defer_js;
	}

	/**
	 * Add the lazyload script to delay js exclusions.
	 *
	 * @param array $js_files Exclusions.
	 * @return array
	 */
	public function add_lazyload_script_rocket_delay_js_exclusions( array $js_files ) {
		if ( ! $this->is_activated() ) {
			return $js_files;
		}

		$js_files [] = 'wp-rocket/assets/js/lazyload-css.min.js';
		return $js_files;
	}

	/**
	 * Is the feature activated.
	 *
	 * @return bool
	 */
	protected function is_activated(): bool {
		return (bool) $this->options->get( 'lazyload_css_bg_img', false );
	}

	/**
	 * Add lazyload_excluded_src to excluded filters.
	 *
	 * @param array $excluded Excluded URLs.
	 * @param array $urls List of Urls processed.
	 * @return mixed
	 */
	public function exclude_rocket_lazyload_excluded_src( $excluded, $urls ) {

		/**
		 * Filters the src used to prevent lazy load from being applied.
		 *
		 * @param array $excluded_src An array of excluded src.
		 */
		$excluded_values = apply_filters( 'rocket_lazyload_excluded_src', [] );

		if ( ! is_array( $excluded_values ) ) {
			$excluded_values = (array) $excluded_values;
		}

		if ( empty( $excluded_values ) ) {
			return $excluded;
		}

		foreach ( $urls as $url ) {
			foreach ( $excluded_values as $excluded_value ) {
				if ( strpos( $url['selector'], $excluded_value ) !== false || strpos( $url['style'], $excluded_value ) !== false ) {
					$excluded[] = $url;
					break;
				}
			}
		}

		return $excluded;
	}
}
