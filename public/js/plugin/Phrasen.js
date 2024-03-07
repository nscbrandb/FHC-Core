import FhcApi from './FhcApi.js';

const categories = Vue.reactive({});
const loadingModules = {};

function extractCategory(obj, category) {
	return obj.filter(e => e.category == category).reduce((res, elem) => {
		if (!res[elem.phrase])
			res[elem.phrase] = elem.text;
		return res;
	}, {});
}
function getValueForLoadedPhrase(category, phrase, params) {
	let result = categories[category][phrase];
	if (!result)
		return '<< PHRASE ' + phrase + '>>';
	if (params)
		result = result.replace(/\{([^}]*)\}/g, (match, p1) => params[p1] === undefined ? match : params[p1]);
	return result;
}


const phrasen = {
	loadCategory(category) {
		if (Array.isArray(category))
			return Promise.all(category.map(cat => this.loadCategory(cat)));
		if (!loadingModules[category])
			loadingModules[category] = this.config.globalProperties
				.$fhcApi.factory.phrasen.loadCategory(category)
				.then(res => res?.data ? extractCategory(res.data, category) : {})
				.then(res => {
					categories[category] = res;
				});
		return loadingModules[category];
	},
	t_ref(category, phrase, params) {
		console.warn('deprecated');
		return Vue.computed(() => this.t(category, phrase, params));
	},
	t(category, phrase, params) {
		if (params === undefined && (
			(Array.isArray(category) && category.length == 2) ||
			(category.split && category.split('/').length == 2))
			) {
			params = phrase;
			[category, phrase] = category.split ? category.split('/') : category;
		}
		if (phrase === undefined) {
			console.error('invalid input', category, phrase, params);
			return '';
		}
		let val = Vue.computed(() => {
			if (!categories[category])
				return '';
			return getValueForLoadedPhrase(category, phrase, params);
		});
		if (!categories[category])
			this.loadCategory(category);
		return val.value;
	}
};

export default {
	install(app, options) {
		app.use(FhcApi, options?.fhcApi || undefined);
		app.config.globalProperties.$p = {
			t: phrasen.t,
			loadCategory: cat => phrasen.loadCategory.call(app, cat),
			t_ref: phrasen.t_ref
		};
	}
}
