<div class="container-fluid back-to-dashboard">
	<div class="row">
		<div class="col-md-12">
			<div class="backtodash soft-top soft-bottom">
				<nav class="portal-nav">
					<a href="<?php echo $is_concierge ? site_url() . '/concierge' : site_url() . '/dashboard'; ?>"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
					<span class="ml-auto"></span>
					<button class="portal-nav-toggle js-portal-nav-toggle"><i class="fa fa-bars"></i></button>
					<div class="portal-nav-links">
						<?php echo \FXUP_USER_PORTAL\Controllers\FXUP_Itinerary_Process::instance()->renderPortalNavigation( $Itinerary->getPostID() ); ?>
					</div>
				</nav>
			</div>
		</div>
	</div>
</div>
<style>
	.portal-nav {
		overflow: hidden;
		position: relative;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		width: 100%;
	}

	.portal-nav-links {
		display: none;
		flex-basis: 100%;
		flex-grow: 1;
		padding: 10px;
	}

	.portal-nav-links.open {
		display: block;
	}
	
	.portal-nav-links a {
		display: block;
		padding: 10px;
	}
	
	.portal-nav-toggle {
		display: block;
		position: absolute;
		right: 0;
		top: 0;
		font-size: 20px;
	}

	@media (min-width:992px) {
		.portal-nav-links {
			padding: 0;
			display: block !important;
			flex-basis: auto;
			flex-grow: 0;
		}
		
		.portal-nav-links a {
			display: inline-block;
			padding-top: 0;
			padding-bottom: 0;
		}
		
		.portal-nav-toggle {
			display: none;
		}
	}
</style>
<script>
	( function ( $ ) {
		$( '.js-portal-nav-toggle' ).click( function () {
			$( '.portal-nav-links' ).toggleClass( 'open' );
		} );
	} )( jQuery );
</script>