<?php

namespace FXUP_User_Portal\Helpers;

if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * FXUP Itinerary Change Logger
 * - Logs only client (non-concierge) saves
 * - Builds Added / Removed / Updated diffs from ACF itinerary payloads
 * - Returns both plain text and HTML for admin + digest
 */
final class Itinerary_Change_Logger
{

	/** Determine if current user is a client (not concierge). */
	private function is_client_user(): bool
	{
		$user_id = get_current_user_id();
		if ( ! $user_id )
			return false;
		$user = get_userdata( $user_id );
		// Adjust this role/capability check to your site’s concierge logic as needed.
		return ! ($user && (in_array( 'um_concierge', (array) $user->roles, true ) || user_can( $user_id, 'manage_options' )));
	}

	/** Clean the raw itinerary payload before storing JSON. */
	private function clean_itinerary_payload_array( array $payload ): array
	{
		$cleaned = [];

		foreach ( $payload as $day ) {
			if ( empty( $day['trip_day_activities'] ) || ! is_array( $day['trip_day_activities'] ) ) {
				continue;
			}

			$cleaned[] = [
				'trip_day' => $day['trip_day'] ?? '',
				'trip_day_activities' => array_values( $day['trip_day_activities'] ),
			];
		}
		
		return $cleaned;
	}

	/** Create a row with before_json when capture starts. */
	public function capture_before( int $itinerary_id, array $before ): void
	{
		if ( ! $this->is_client_user() )
			return;

		global $wpdb;
		$table = $wpdb->prefix . 'fxup_itinerary_log';

		$exists = (int) $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE itinerary_id = %d", $itinerary_id
				) );

		if ( ! $exists ) {
			$before_json = wp_json_encode( $this->clean_itinerary_payload_array( $before ) );
			$wpdb->insert(
				$table,
				[
					'itinerary_id' => $itinerary_id,
					'user_id' => get_current_user_id(),
					'before_json' => $before_json,
					'after_json' => null,
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' ),
				],
				[ '%d', '%d', '%s', '%s', '%s', '%s' ]
			);
		}
	}

	/** Update after_json on every client save (kept from your version). */
	public function update_after( int $itinerary_id, array $after ): void
	{
		if ( ! $this->is_client_user() )
			return;

		global $wpdb;
		$table = $wpdb->prefix . 'fxup_itinerary_log';

		$after_clean = $this->clean_itinerary_payload_array( $after );
		$after_json = wp_json_encode( $after_clean );
		$now = current_time( 'mysql' );

		$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM {$table} WHERE itinerary_id = %d LIMIT 1", $itinerary_id
				) );

		if ( $existing_id ) {
			$wpdb->update(
				$table,
				[ 'after_json' => $after_json, 'updated_at' => $now ],
				[ 'id' => $existing_id ],
				[ '%s', '%s' ],
				[ '%d' ]
			);
		}
	}

	/** Retrieve logs for digest window. */
	public function fetch_digest( \DateTimeInterface $from, \DateTimeInterface $to ): array
	{
		global $wpdb;
		$table = $wpdb->prefix . 'fxup_itinerary_log';
		$sql = "SELECT * FROM {$table} WHERE updated_at >= %s AND updated_at < %s ORDER BY updated_at ASC";
		return $wpdb->get_results( $wpdb->prepare( $sql, $from->format( 'Y-m-d H:i:s' ), $to->format( 'Y-m-d H:i:s' ) ), ARRAY_A ) ?: [];
	}

	/** Clear logs after digest. */
	public function clear_all(): int
	{
		global $wpdb;
		$table = $wpdb->prefix . 'fxup_itinerary_log';
		$wpdb->query( "TRUNCATE TABLE {$table}" );
		return (int) $wpdb->rows_affected;
	}

	/**
	 * PUBLIC: Build a simple diff summary (plain text + HTML).
	 * Use this from admin UI and from the controller (digest).
	 */
	public function build_diff_summary( array $before, array $after ): array
	{
		$byDayBefore = $this->normalize_days( $before );
		$byDayAfter = $this->normalize_days( $after );

		$allDays = array_values( array_unique( array_merge( array_keys( $byDayBefore ), array_keys( $byDayAfter ) ) ) );
		sort( $allDays );

		$textLines = [];
		$htmlRows = [];

		foreach ( $allDays as $day ) {
			$bActs = $byDayBefore[$day] ?? [];
			$aActs = $byDayAfter[$day] ?? [];

			// Multiset diff on labels (counts) for add/remove
			$bLabels = array_column( $bActs, 'label' );
			$aLabels = array_column( $aActs, 'label' );
			$ms = $this->multiset_diff( $bLabels, $aLabels );

			// Field-level updates for same activity instance (key = "{label}-{index}")
			$updates = $this->detect_activity_updates( $bActs, $aActs );

			if ( empty( $ms['added'] ) && empty( $ms['removed'] ) && empty( $updates ) ) {
				continue;
			}

			// ---- Plain text ----
			$tline = "{$day}:";
			if ( $ms['added'] )
				$tline .= " Added: " . $this->format_changes_text( $ms['added'] ) . ".";
			if ( $ms['removed'] )
				$tline .= " Removed: " . $this->format_changes_text( $ms['removed'] ) . ".";
			if ( $updates ) {
				$parts = [];
				foreach ( $updates as $label => $changes ) {
					$changeStr = implode( ', ', array_map(
							static fn( $f, $v ) => "{$f}: " . (string) ($v[0] === '' ? '(empty)' : $v[0]) . " → " . (string) ($v[1] === '' ? '(empty)' : $v[1]),
							array_keys( $changes ), $changes
						) );
					$parts[] = "{$label} ({$changeStr})";
				}
				$tline .= " Updated: " . implode( '; ', $parts ) . ".";
			}
			$textLines[] = $tline;

			// ---- HTML row (digest: one table per itinerary; each day -> one row) ----
			$addedHtml = $ms['added'] ? $this->format_changes_html( $ms['added'], '#155724' ) : '';
			$removedHtml = $ms['removed'] ? $this->format_changes_html( $ms['removed'], '#721c24' ) : '';
			$updatedHtml = '';
			if ( $updates ) {
				$uLis = [];
				foreach ( $updates as $label => $changes ) {
					$li = '<li style="margin:0 0 2px 0;"><strong>' . esc_html( $label ) . '</strong><ul style="margin:2px 0 0 16px;padding:0;">';
					foreach ( $changes as $field => [$old, $new] ) {
						$old = $old === '' ? '(empty)' : esc_html( (string) $old );
						$new = $new === '' ? '(empty)' : esc_html( (string) $new );
						$li .= '<li style="margin:0;">' . esc_html( $field ) . ': ' . $old . ' → <strong>' . $new . '</strong></li>';
					}
					$li .= '</ul></li>';
					$uLis[] = $li;
				}
				$updatedHtml = '<div><span style="color:#0c5460;font-weight:600;">Updated:</span><ul style="margin:4px 0 0 18px;padding:0;">' . implode( '', $uLis ) . '</ul></div>';
			}

			$cell = '';
			if ( $addedHtml )
				$cell .= '<div>' . $addedHtml . '</div>';
			if ( $removedHtml )
				$cell .= '<div>' . $removedHtml . '</div>';
			if ( $updatedHtml )
				$cell .= $updatedHtml;

			$htmlRows[] = '<tr>' .
				'<td style="padding:8px;border:1px solid #ddd;vertical-align:top;"><strong>' . esc_html( $day ) . '</strong></td>' .
				'<td style="padding:8px;border:1px solid #ddd;vertical-align:top;">' . ($cell ?: '<em>No changes</em>') . '</td>' .
				'</tr>';
		}

		$htmlTable = $htmlRows ? ('<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;border:1px solid #ddd;">' .
			'<thead><tr><th style="text-align:left;padding:8px;border:1px solid #ddd;background:#f7f7f7;">Day</th><th style="text-align:left;padding:8px;border:1px solid #ddd;background:#f7f7f7;">Changes</th></tr></thead>' .
			'<tbody>' . implode( '', $htmlRows ) . '</tbody></table>') : '<p><em>No changes detected.</em></p>';

		return [
			'text' => $textLines ? implode( "\n", $textLines ) : 'No changes detected.',
			'html' => $htmlTable,
		];
	}

	/** Use new diff in digest builder (controller can call this). */
	public function build_digest_html( array $rows ): string
	{
		if ( ! $rows )
			return '<p>No client itinerary updates today.</p>';

		ob_start();
		echo '<h2 style="margin:0 0 12px 0;">Client Itinerary Updates</h2>';
		echo '<p>Below is a list of all itineraries updated or created within the last 24 hours:</p>';

		foreach ( $rows as $r ) {
			$before = json_decode( $r['before_json'] ?: '[]', true );
			$after = json_decode( $r['after_json'] ?: '[]', true );

			$diff = $this->build_diff_summary( $before, $after );
			$Itinerary = new \FXUP_User_Portal\Models\Itinerary( $r['itinerary_id'] );
			include FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/partials/email-concierge-digest-report-single-itinerary.php';
		}
		return ob_get_clean();
	}

	/* =========================
	 * Internals / helpers
	 * ========================= */

	/** Build day->activity map; each activity has a stable instance key and a human label. */
	private function normalize_days( array $payload ): array
	{
		$out = [];
		foreach ( $payload as $day ) {
			$label = $day['trip_day'] ?? null;
			if ( ! $label )
				continue;

			$list = [];
			foreach ( ($day['trip_day_activities'] ?? []) as $i => $act ) {
				$labelText = $this->resolve_activity_label( $act );
				if ( $labelText === '' || $labelText === '0' )
					continue;

				$key = "{$labelText}-{$i}"; // instance key to detect field-level updates
				$list[$key] = array_merge( $act, [ 'label' => $labelText ] );
			}
			$out[$label] = $list;
		}
		return $out;
	}

	/** Resolve readable label: custom title if present; else activity_title (ID→post title or raw). */
	private function resolve_activity_label( array $act ): string
	{
		$custom = isset( $act['custom_activity_title'] ) ? trim( (string) $act['custom_activity_title'] ) : '';
		if ( $custom !== '' )
			return $custom;

		$title = $act['activity_title'] ?? '';
		if ( is_numeric( $title ) ) {
			$post_title = get_the_title( (int) $title );
			return $post_title ? $post_title : (string) $title;
		}
		return (string) $title;
	}

	/** Detect updates on watched fields for matched instances. */
	private function detect_activity_updates( array $beforeActs, array $afterActs ): array
	{
		$watched = [
			'activity_time_booked',
			'activity_no_conflict',
			'message_private',
			'specific_guests',
			'child_guests',
			'activity_comments',
		];

		$updated = [];
		foreach ( $afterActs as $key => $a ) {
			if ( empty( $beforeActs[$key] ) )
				continue;
			$b = $beforeActs[$key];

			$changes = [];
			foreach ( $watched as $field ) {
				$beforeVal = $b[$field] ?? '';
				$afterVal = $a[$field] ?? '';
				if ( $beforeVal != $afterVal ) {
					$changes[$field] = [ $beforeVal, $afterVal ];
				}
			}
			if ( $changes )
				$updated[$a['label']] = $changes;
		}
		return $updated;
	}

	private function multiset_counts( array $vals ): array
	{
		$vals = array_values( array_filter( $vals, static fn( $v ) => $v !== '' && $v !== null ) );
		return array_count_values( $vals );
	}

	/** Flat-label multiset diff → ['added'=>['Label'=>count], 'removed'=>['Label'=>count]] */
	private function multiset_diff( array $before, array $after ): array
	{
		$b = $this->multiset_counts( $before );
		$a = $this->multiset_counts( $after );
		$keys = array_unique( array_merge( array_keys( $b ), array_keys( $a ) ) );

		$added = $removed = [];
		foreach ( $keys as $k ) {
			$bc = $b[$k] ?? 0;
			$ac = $a[$k] ?? 0;
			if ( $ac > $bc )
				$added[$k] = $ac - $bc;
			if ( $bc > $ac )
				$removed[$k] = $bc - $ac;
		}
		return [ 'added' => $added, 'removed' => $removed ];
	}

	private function format_changes_text( array $changes ): string
	{
		$parts = [];
		foreach ( $changes as $label => $count ) {
			$parts[] = $count > 1 ? "{$label} (x{$count})" : $label;
		}
		return implode( ', ', $parts );
	}

	private function format_changes_html( array $changes, string $color ): string
	{
		$parts = [];
		foreach ( $changes as $label => $count ) {
			$parts[] = esc_html( $count > 1 ? "{$label} (x{$count})" : $label );
		}
		$prefix = $color === '#721c24' ? 'Removed:' : 'Added:';
		return '<span style="color:' . esc_attr( $color ) . ';font-weight:600;">' . esc_html( $prefix ) . '</span> ' . implode( ', ', $parts );
	}
}
