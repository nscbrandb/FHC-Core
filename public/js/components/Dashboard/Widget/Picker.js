import BsModal from "../../Bootstrap/Modal.js";

export default {
	components: {
		BsModal
	},
	props: [
		"widgets"
	],
	data: () => ({
		callbacks: {}
	}),
	methods: {
		getWidget() {
			return new Promise((resolve,reject) => {
				this.callbacks = {resolve,reject};
				this.$refs.modal.show();
			});
		},
		close() {
			if (this.callbacks.reject)
				this.callbacks.reject();
			this.callbacks = {};
		},
		pick(widget_id) {
			if (this.callbacks.resolve)
				this.callbacks.resolve(widget_id);
			this.callbacks = {};
			this.$refs.modal.hide();
		}
	},
	template: `<div class="dashboard-widget-picker">
		<bs-modal ref="modal" class="fade" :dialog-class="{'modal-fullscreen-sm-down': 1, 'modal-dialog modal-xl': widgets && widgets.length > 5}" @hiddenBsModal="close">
			<template v-slot:title>Create new widget</template>
			<template v-slot:default>
				<div v-if="widgets" class="row">
					<div v-if="!widgets.length">
						No Widgets available
					</div>
					<div v-for="widget in widgets" :key="widget.widget_id" class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
						<div class="card h-100" @click="pick(widget.widget_id)">
							<img class="card-img-top" :src="widget.setup.icon" :alt="'pictogram for ' + (widget.setup.name || widget.widget_kurzbz)">
							<div class="card-body">
								<h5 class="card-title">{{ widget.setup.name || widget.widget_kurzbz }}</h5>
								<p class="card-text">{{ widget.beschreibung }}</p>
							</div>
						</div>
					</div>
				</div>
				<div v-else class="text-center"><i class="fa-solid fa-spinner fa-pulse fa-3x"></i></div>
			</template>
		</bs-modal>
	</div>`
}
