const FXUP_TourConfig = {
	useModalOverlay: true,
	defaultStepOptions: {
		scrollTo: { behavior: 'smooth', block: 'center' },
		modalOverlayOpeningPadding: 10,
		buttons: [
			{
				action() {
					return this.back();
				},
				classes: 'shepherd-button-secondary btn btn-sm',
				text: 'Back'
			},
			{
				action() {
					return this.next();
				},
				classes: 'btn btn-sm btn-primary',
				text: 'Next'
			}
		],
		cancelIcon: {
			enabled: true
		}
	},
	steps: [
		{
			attachTo: {
				element: ".dashboard-page-header",
				on: 'bottom'
			},
			title: "Welcome",
			text: "Welcome to your Villa Punto de Vista Travel Dashboard. Let us show you around.",
			buttons: [
				{
					action() {
						return this.next();
					},
					classes: 'btn btn-sm btn-primary',
					text: 'Next'
				}
			]
		},
		{
			attachTo: {
				element: ".dashboard-video-link a",
				on: 'bottom'
			},
			title: "Video",
			text: "Learn more about the dashboard by watching this short video."
		},
		{
			attachTo: {
				element: ".itinerary-section",
				on: 'right'
			},
			title: "Itinerary",
			text: "Plan for your trip by modifying your itinerary."
		},
		{
			attachTo: {
				element: ".dashboard-sidebar > .selectric-wrapper:first-child",
				on: 'bottom'
			},
			title: "Pages",
			text: "Navigate by selecting a page.",
			buttons: [
				{
					action() {
						return this.back();
					},
					classes: 'shepherd-button-secondary btn btn-sm',
					text: 'Back'
				},
				{
					action() {
						return this.complete();
					},
					classes: 'btn btn-sm btn-success',
					text: 'Finish'
				}
			]
		}
	]
};