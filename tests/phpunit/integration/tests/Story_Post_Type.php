<?php

declare(strict_types = 1);

/**
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Web_Stories\Tests\Integration;

use Google\Web_Stories\Experiments;
use Google\Web_Stories\Settings;
use WP_UnitTest_Factory;

/**
 * @coversDefaultClass \Google\Web_Stories\Story_Post_Type
 */
class Story_Post_Type extends DependencyInjectedTestCase {
	/**
	 * Admin user for test.
	 */
	protected static int $admin_id;

	/**
	 * Story id.
	 */
	protected static int $story_id;

	/**
	 * Archive page ID.
	 */
	protected static int $archive_page_id;

	/**
	 * Test instance.
	 */
	protected \Google\Web_Stories\Story_Post_Type $instance;

	private Settings $settings;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$admin_id = $factory->user->create(
			[ 'role' => 'administrator' ]
		);

		self::$story_id = $factory->post->create(
			[
				'post_type'    => \Google\Web_Stories\Story_Post_Type::POST_TYPE_SLUG,
				'post_title'   => 'Story_Post_Type Test Story',
				'post_status'  => 'publish',
				'post_content' => 'Example content',
				'post_author'  => self::$admin_id,
			]
		);

		/**
		 * @var int $poster_attachment_id
		 */
		$poster_attachment_id = $factory->attachment->create_object(
			[
				'file'           => DIR_TESTDATA . '/images/canola.jpg',
				'post_parent'    => 0,
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Test Image',
			]
		);

		set_post_thumbnail( self::$story_id, $poster_attachment_id );

		self::$archive_page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
	}

	public function set_up(): void {
		parent::set_up();

		$experiments = $this->createMock( Experiments::class );
		$experiments->method( 'is_experiment_enabled' )
					->willReturn( true );

		$this->settings = $this->injector->make( Settings::class );
		$this->instance = new \Google\Web_Stories\Story_Post_Type( $this->settings );
	}

	public function tear_down(): void {
		delete_option( $this->settings::SETTING_NAME_ARCHIVE );
		delete_option( $this->settings::SETTING_NAME_ARCHIVE_PAGE_ID );

		parent::tear_down();
	}

	/**
	 * @covers ::register
	 */
	public function test_register(): void {
		$this->instance->register();

		$this->assertSame( 10, has_filter( 'wp_insert_post_data', [ $this->instance, 'change_default_title' ] ) );
		$this->assertSame( 10, has_filter( 'wp_insert_post_empty_content', [ $this->instance, 'filter_empty_content' ] ) );
		$this->assertSame(
			10,
			has_filter(
				'bulk_post_updated_messages',
				[
					$this->instance,
					'bulk_post_updated_messages',
				]
			)
		);
	}

	/**
	 * @covers ::get_post_type_icon
	 */
	public function test_get_post_type_icon(): void {
		$valid = $this->call_private_method( [ $this->instance, 'get_post_type_icon' ] );
		$this->assertStringContainsString( 'data:image/svg+xml;base64', $valid );
	}

	/**
	 * @covers ::register_post_type
	 */
	public function test_register_post_type(): void {

		$post_type = $this->instance->register_post_type();
		$this->assertNotWPError( $post_type );
		$this->assertTrue( $post_type->has_archive );
	}

	/**
	 * @covers ::register_post_type
	 */
	public function test_register_post_type_disabled(): void {
		update_option( $this->settings::SETTING_NAME_ARCHIVE, 'disabled' );
		$post_type = $this->instance->register_post_type();
		$this->assertNotWPError( $post_type );
		$this->assertFalse( $post_type->has_archive );
	}

	/**
	 * @covers ::register_post_type
	 */
	public function test_register_post_type_default(): void {
		update_option( $this->settings::SETTING_NAME_ARCHIVE, 'default' );
		$post_type = $this->instance->register_post_type();
		$this->assertNotWPError( $post_type );
		$this->assertTrue( $post_type->has_archive );
	}

	/**
	 * @covers ::register_meta
	 */
	public function test_register_meta(): void {
		$this->instance->register_meta();

		$this->assertTrue( registered_meta_key_exists( 'post', $this->instance::PUBLISHER_LOGO_META_KEY, $this->instance->get_slug() ) );
		$this->assertTrue( registered_meta_key_exists( 'post', $this->instance::POSTER_META_KEY, $this->instance->get_slug() ) );
	}

	/**
	 * @covers ::change_default_title
	 */
	public function test_change_default_title(): void {
		$post = self::factory()->post->create_and_get(
			[
				'post_type'    => $this->instance->get_slug(),
				'post_content' => '<html><head></head><body><amp-story></amp-story></body></html>',
				'post_status'  => 'auto-draft',
				'post_title'   => 'Auto draft',
			]
		);

		$this->assertSame( '', $post->post_title );
	}

	/**
	 * @covers ::filter_empty_content
	 */
	public function test_filter_empty_content(): void {
		$postarr = [
			'post_type'             => $this->instance->get_slug(),
			'post_content_filtered' => 'Not empty',
		];

		$empty_postarr = [
			'post_type'             => $this->instance->get_slug(),
			'post_content_filtered' => '',
		];

		$this->assertFalse( $this->instance->filter_empty_content( false, $postarr ) );
		$this->assertFalse( $this->instance->filter_empty_content( false, $empty_postarr ) );
		$this->assertFalse( $this->instance->filter_empty_content( true, $postarr ) );
		$this->assertTrue( $this->instance->filter_empty_content( true, $empty_postarr ) );
	}

	/**
	 * @covers ::get_has_archive
	 */
	public function test_get_has_archive_default(): void {
		$actual = $this->instance->get_has_archive();
		$this->assertTrue( $actual );
	}

	/**
	 * @covers ::get_has_archive
	 */
	public function test_get_has_archive_disabled_experiments(): void {
		$this->instance = new \Google\Web_Stories\Story_Post_Type( $this->settings );

		$actual = $this->instance->get_has_archive();
		$this->assertTrue( $actual );
	}

	/**
	 * @covers ::get_has_archive
	 */
	public function test_get_has_archive_disabled(): void {
		update_option( $this->settings::SETTING_NAME_ARCHIVE, 'disabled' );

		$actual = $this->instance->get_has_archive();

		delete_option( $this->settings::SETTING_NAME_ARCHIVE );

		$this->assertFalse( $actual );
	}

	/**
	 * @covers ::get_has_archive
	 */
	public function test_get_has_archive_custom_but_no_page(): void {
		update_option( $this->settings::SETTING_NAME_ARCHIVE, 'custom' );

		$actual = $this->instance->get_has_archive();

		delete_option( $this->settings::SETTING_NAME_ARCHIVE );

		$this->assertTrue( $actual );
	}

	/**
	 * @covers ::get_has_archive
	 */
	public function test_get_has_archive_custom_but_invalid_page(): void {
		update_option( $this->settings::SETTING_NAME_ARCHIVE, 'custom' );
		update_option( $this->settings::SETTING_NAME_ARCHIVE_PAGE_ID, PHP_INT_MAX );

		$actual = $this->instance->get_has_archive();

		delete_option( $this->settings::SETTING_NAME_ARCHIVE );
		delete_option( $this->settings::SETTING_NAME_ARCHIVE_PAGE_ID );

		$this->assertTrue( $actual );
	}

	/**
	 * @covers ::get_has_archive
	 */
	public function test_get_has_archive_custom(): void {
		update_option( $this->settings::SETTING_NAME_ARCHIVE, 'custom' );
		update_option( $this->settings::SETTING_NAME_ARCHIVE_PAGE_ID, self::$archive_page_id );

		$actual = $this->instance->get_has_archive();

		delete_option( $this->settings::SETTING_NAME_ARCHIVE );
		delete_option( $this->settings::SETTING_NAME_ARCHIVE_PAGE_ID );

		$this->assertIsString( $actual );
		$this->assertSame( urldecode( (string) get_page_uri( self::$archive_page_id ) ), $actual );
	}

	/**
	 * @covers ::get_has_archive
	 */
	public function test_get_has_archive_custom_not_published(): void {
		update_option( $this->settings::SETTING_NAME_ARCHIVE, 'custom' );
		update_option( $this->settings::SETTING_NAME_ARCHIVE_PAGE_ID, self::$archive_page_id );

		wp_update_post(
			[
				'ID'          => self::$archive_page_id,
				'post_status' => 'draft',
			]
		);

		$actual = $this->instance->get_has_archive();

		delete_option( $this->settings::SETTING_NAME_ARCHIVE );
		delete_option( $this->settings::SETTING_NAME_ARCHIVE_PAGE_ID );

		wp_update_post(
			[
				'ID'          => self::$archive_page_id,
				'post_status' => 'publish',
			]
		);

		$this->assertTrue( $actual );
	}

	/**
	 * @covers ::on_plugin_uninstall
	 */
	public function test_on_plugin_uninstall(): void {
		$presets = [
			'fillColors' => [
				[
					'type'     => 'conic',
					'stops'    =>
						[
							[
								'color'    => [],
								'position' => 0,
							],
							[
								'color'    => [],
								'position' => 0.7,
							],
						],
					'rotation' => 0.5,
				],
			],
			'textColors' => [
				[
					'color' => [],
				],
			],
			'textStyles' => [
				[
					'color'              => [],
					'backgroundColor'    =>
						[
							'type'     => 'conic',
							'stops'    => [],
							'rotation' => 0.5,
						],
					'backgroundTextMode' => 'FILL',
					'font'               => [],
				],
			],
		];
		add_option( \Google\Web_Stories\Story_Post_Type::STYLE_PRESETS_OPTION, $presets );

		$post = self::factory()->post->create_and_get(
			[
				'post_title'   => 'test title',
				'post_type'    => \Google\Web_Stories\Story_Post_Type::POST_TYPE_SLUG,
				'post_content' => '<html><head></head><body><amp-story></amp-story></body></html>',
			]
		);

		add_post_meta( $post->ID, \Google\Web_Stories\Story_Post_Type::POSTER_META_KEY, [] );
		add_post_meta( $post->ID, \Google\Web_Stories\Story_Post_Type::PUBLISHER_LOGO_META_KEY, 123 );

		$this->instance->on_plugin_uninstall();

		$this->assertSame( '', get_post_meta( $post->ID, $this->instance::POSTER_META_KEY, true ) );

		$this->assertSame( '', get_post_meta( $post->ID, $this->instance::PUBLISHER_LOGO_META_KEY, true ) );
		$this->assertFalse( get_option( \Google\Web_Stories\Story_Post_Type::STYLE_PRESETS_OPTION ) );
	}
}
