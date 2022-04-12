<?php

namespace WP_Rocket\Tests\Integration\inc\Engine\Optimization\RUCSS\Admin\Subscriber;

use WP_Rocket\Tests\Integration\TestCase;

/**
 * @covers \WP_Rocket\Engine\Optimization\RUCSS\Admin\Subscriber::maybe_disable_combine_css
 *
 * @group RUCSS
 * @group AdminOnly
 */
class Test_MaybeDisableCombineCss extends TestCase {
	public function set_up() {
		parent::set_up();

		$this->unregisterAllCallbacksExcept( 'pre_update_option_wp_rocket_settings', 'maybe_disable_combine_css', 11 );
	}

	public function tear_down() {
		parent::tear_down();

		$this->restoreWpFilter( 'pre_update_option_wp_rocket_settings' );
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldDoExpected( $config, $expected ) {
		$this->assertSame(
			$expected,
			apply_filters( 'pre_update_option_wp_rocket_settings', $config['value'], $config['old_value'] )
		);
	}
}
