export default {
	props: [
		'dateien',
		'multiupload'
	],
	computed: {
		intDateien:{
			get() {
				return this.dateien;
			},
			set(value) {
				this.$emit('update:dateien', value);
			}
		},
	},
	methods: {
		handleFileChange(event) {
			this.intDateien = event.target.files;
		},
		deleteFile(id) {
			const dt = new DataTransfer();
			const files = this.$refs.upload.files;

			for (let i = 0; i < files.length; i++) {
				const file = files[i];
				if (id !== i)
					dt.items.add(file);
			}

			this.$refs.upload.files = dt.files;
			this.$emit('update:dateien', dt.files);
		}
	},
	template: `		
		<span v-if="multiupload">
		  	<input type="file" multiple ref="upload" @change="handleFileChange" v-model="dateien"/>								
		</span>
		<span v-else>
			<input type="file" ref="upload" @change="handleFileChange" v-model="dateien"/>
		</span>
		
		<span>					
			<ul>							
				<li v-for="(datei,index) in dateien">
					<button>{{datei.name}}</button><button class="text-danger" @click="deleteFile(index)">X</button>
				</li>
			</ul>
		</span>
</div>
</template>`
}