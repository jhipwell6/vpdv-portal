<?php

namespace FXUP_User_Portal\Core;

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	class Child_Guest_Migration_Command
	{
		/**
		 * Run migration from legacy child storage to child guest entities.
		 *
		 * ## EXAMPLES
		 *
		 *     wp vpdv migrate-children
		 */
		public function migrate_children()
		{
			$Migration = new \FXUP_User_Portal\Migrations\ChildGuestMigration();
			$stats = $Migration->runMigration();

			\WP_CLI::success( 'Child guest migration complete.' );
			\WP_CLI::line( 'itineraries scanned: ' . (int) $stats['itineraries_scanned'] );
			\WP_CLI::line( 'child guests created: ' . (int) $stats['child_guests_created'] );
			\WP_CLI::line( 'room placeholders converted: ' . (int) $stats['room_placeholders_converted'] );
			\WP_CLI::line( 'legacy fields cleared: ' . (int) $stats['legacy_fields_cleared'] );
		}
	}

	\WP_CLI::add_command( 'vpdv', __NAMESPACE__ . '\\Child_Guest_Migration_Command' );
}
