<?php 

/* 
 * The message body is retrieved from an option in the dashboard.
 * We just need to replace the merge tags
 * option name = email_digest_concierge_guest_removed_message
 * The email service passes these variables
 * $Email
 */

if ( $Email->get_data() && ! empty( $Email->get_data() ) ) :
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
		<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
			<table border="0" cellpadding="20" cellspacing="0" width="800" style="max-width:100%">
				<tbody>
					<tr>
						<td>
							<p>Hello!</p>
							<p>These guests have been removed.</p>
							<p>Here is the guests' information for reference:</p>
							<table style="padding-top:20px;padding-bottom:20px;">
								<thead>
									<tr>
										<th align="left">Itinerary</th>
										<th align="left">Name</th>
										<th align="left">Email</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $Email->get_data() as $data ) : ?>
									<tr>
										<td># <?php echo $data['itinerary_id']; ?> - <?php echo $data['itinerary_title']; ?></td>
										<td><?php echo $data['guest_first_name']; ?> <?php echo $data['guest_last_name']; ?></td>
										<td><?php echo $data['guest_email']; ?></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<p>These guests' information has already been removed from the site.</p>
							<p>Thanks!</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</body>
</html>
<?php endif; ?>