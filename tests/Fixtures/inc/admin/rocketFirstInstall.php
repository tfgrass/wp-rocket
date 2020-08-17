<?php

$default = [
	'secret_cache_key'            => 'secret_cache_key_uniqid',
	'cache_mobile'                => 1,
	'do_caching_mobile_files'     => 0,
	'cache_webp'                  => 0,
	'cache_logged_user'           => 0,
	'cache_ssl'                   => 1,
	'emoji'                       => 1,
	'embeds'                      => 0,
	'cache_reject_uri'            => [],
	'cache_reject_cookies'        => [],
	'cache_reject_ua'             => [],
	'cache_query_strings'         => [],
	'cache_purge_pages'           => [],
	'purge_cron_interval'         => 10,
	'purge_cron_unit'             => 'HOUR_IN_SECONDS',
	'exclude_css'                 => [],
	'exclude_js'                  => [],
	'exclude_inline_js'           => [],
	'defer_all_js'                => 0,
	'defer_all_js_safe'           => 1,
	'async_css'                   => 0,
	'critical_css'                => '',
	'lazyload'                    => 0,
	'lazyload_iframes'            => 0,
	'lazyload_youtube'            => 0,
	'minify_css'                  => 0,
	'minify_css_key'              => 'minify_css_key_uniqid',
	'minify_concatenate_css'      => 0,
	'minify_js'                   => 0,
	'minify_js_key'               => 'minify_js_key_uniqid',
	'minify_concatenate_js'       => 0,
	'minify_google_fonts'         => 1,
	'manual_preload'              => 1,
	'sitemap_preload'             => 0,
	'sitemap_preload_url_crawl'   => '500000',
	'sitemaps'                    => [],
	'dns_prefetch'                => 0,
	'preload_fonts'               => [],
	'database_revisions'          => 0,
	'database_auto_drafts'        => 0,
	'database_trashed_posts'      => 0,
	'database_spam_comments'      => 0,
	'database_trashed_comments'   => 0,
	'database_expired_transients' => 0,
	'database_all_transients'     => 0,
	'database_optimize_tables'    => 0,
	'schedule_automatic_cleanup'  => 0,
	'automatic_cleanup_frequency' => 'daily',
	'cdn'                         => 0,
	'cdn_cnames'                  => [],
	'cdn_zone'                    => [],
	'cdn_reject_files'            => [],
	'do_cloudflare'               => 0,
	'cloudflare_email'            => '',
	'cloudflare_api_key'          => '',
	'cloudflare_zone_id'          => '',
	'cloudflare_devmode'          => 0,
	'cloudflare_protocol_rewrite' => 0,
	'cloudflare_auto_settings'    => 0,
	'cloudflare_old_settings'     => '',
	'control_heartbeat'           => 0,
	'heartbeat_site_behavior'     => 'reduce_periodicity',
	'heartbeat_admin_behavior'    => 'reduce_periodicity',
	'heartbeat_editor_behavior'   => 'reduce_periodicity',
	'varnish_auto_purge'          => 0,
	'do_beta'                     => 0,
	'analytics_enabled'           => 0,
	'google_analytics_cache'      => 0,
	'facebook_pixel_cache'        => 0,
	'sucury_waf_cache_sync'       => 0,
	'sucury_waf_api_key'          => '',
];

$integration = $default;
$integration[ 'async_css_mobile' ] = 1;
$integration[ 'delay_js' ]         = 1;
$integration[ 'delay_js_scripts' ] = [
	'getbutton.io',
	'//a.omappapi.com/app/js/api.min.js',
	'feedbackcompany.com/includes/widgets/feedback-company-widget.min.js',
	'snap.licdn.com/li.lms-analytics/insight.min.js',
	'static.ads-twitter.com/uwt.js',
	'platform.twitter.com/widgets.js',
	'connect.facebook.net/en_GB/sdk.js',
	'connect.facebook.net/en_US/sdk.js',
	'static.leadpages.net/leadbars/current/embed.js',
	'translate.google.com/translate_a/element.js',
	'widget.manychat.com',
	'google.com/recaptcha/api.js',
	'xfbml.customerchat.js',
	'static.hotjar.com/c/hotjar-',
	'smartsuppchat.com/loader.js',
	'grecaptcha.execute',
	'Tawk_API',
	'shareaholic',
	'sharethis',
	'simple-share-buttons-adder',
	'addtoany',
	'font-awesome',
	'wpdiscuz',
	'cookie-law-info',
	'cookie-notice',
	'pinit.js',
	'gtag',
	'gtm',
	'fbevents.js',
	'fbq(',
];

return [
	'test_data' => [
		'defaultOptionsArray' => [
			'unit'        => $default,
			'integration' => $integration,
		],
	],
];
