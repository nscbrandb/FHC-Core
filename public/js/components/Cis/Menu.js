import CisMenuEntry from "./Menu/Entry.js";
import FhcSearchbar from "../searchbar/searchbar.js";

export default {
    components: {
        CisMenuEntry,
        FhcSearchbar
    },
    props: {
		menu: Array,
		rootUrl: String,
        logoUrl: String,
        avatarUrl: String,
        logoutUrl: String,
		selectedtypes: Array,
        searchbaroptions: Object,
        searchfunction: Function
    },
    data: () => {
        return {
            entries: [],
			activeEntry:null,
			url:null,
			urlMatchRankings:[],
        };
    },
	computed:{
		highestMatchingUrlCount(){
			// gets the hightest ranking inside the array
			let highestMatch = Math.max(...this.urlMatchRankings);

			if(this.urlMatchRankings.length > 0){
				// if more than one entry has the same ranking, none should be active
				return this.urlMatchRankings.filter((value)=>value == highestMatch).length > 1 ? null : highestMatch;
			}

			return null;
		},
	},
	methods:{
		addUrlCount(count){
			this.urlMatchRankings.push(count);
		},

		setActiveEntry(content_id){
			this.activeEntry = content_id;
		}
	},
	mounted(){
		this.entries = this.menu;
	},
    template: /*html*/`
	<!--<p>CISVUE HEADER</p>
	<p>active entry content_id : {{activeEntry}}</p>
	<p>highest count : {{highestMatchingUrlCount}}</p>
	-->
	<button id="nav-main-btn" class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#nav-main" aria-controls="nav-main" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
	<fhc-searchbar id="nav-search" class="fhc-searchbar w-100" :searchoptions="searchbaroptions" :searchfunction="searchfunction" :selectedtypes="selectedtypes"></fhc-searchbar>
    <a id="nav-logo" :href="rootUrl">
        <img :src="logoUrl" alt="Logo">
    </a>
	<button id="nav-user-btn" class="btn btn-link rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav-user-menu" aria-expanded="false" aria-controls="nav-user-menu">
		<img :src="avatarUrl" class="avatar rounded-circle"/>
	</button>
	<ul id="nav-user-menu" class="collapse list-unstyled" aria-labelledby="nav-user-btn">
		<li><a class="btn btn-level-2 rounded-0 d-block" href="#" id="menu-profil">Profil</a></li>
		<li><a class="btn btn-level-2 rounded-0 d-block" href="#">Ampeln</a></li>
		<li><hr class="dropdown-divider"></li>
		<li><a class="btn btn-level-2 rounded-0 d-block" :href="logoutUrl">Logout</a></li>
	</ul>
    <nav id="nav-main" class="offcanvas offcanvas-start bg-dark" tabindex="-1" aria-labelledby="nav-main-btn" data-bs-backdrop="false">
		<div id="nav-main-sticky">
			<div id="nav-main-toggle" class="position-static d-none d-lg-block bg-dark">
				<button type="button" class="btn bg-dark text-light rounded-0 p-1 d-flex align-items-center" data-bs-toggle="collapse" data-bs-target="#nav-main-menu" aria-expanded="true" aria-controls="nav-main-menu">
					<i class="fa fa-arrow-circle-left"></i>
				</button>
			</div>
			<div class="offcanvas-body p-0">
				<div id="nav-main-menu" class="collapse collapse-horizontal show">
					<div>
						<cis-menu-entry @UrlCount="addUrlCount" @activeEntry="setActiveEntry" :highestMatchingUrlCount="highestMatchingUrlCount" :activeContent="activeEntry" v-for="entry in entries" :key="entry.content_id" :entry="entry" />
					</div>
				</div>
			</div>
		</div>
    </nav>`
};
