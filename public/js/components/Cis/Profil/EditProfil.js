import BsModal from "../../Bootstrap/Modal.js";
import Alert from "../../Bootstrap/Alert.js";
import EditProfilSelect from "./EditProfilSelect.js";

export default {
  components: {
    BsModal,
    Alert,
    EditProfilSelect,
  },
  mixins: [BsModal],
  props: {
    value: Object,
    title: String,
    /*
     * NOTE(chris):
     * Hack to expose in "emits" declared events to $props which we use
     * in the v-bind directive to forward all events.
     * @see: https://github.com/vuejs/core/issues/3432
     */
    onHideBsModal: Function,
    onHiddenBsModal: Function,
    onHidePreventedBsModal: Function,
    onShowBsModal: Function,
    onShownBsModal: Function,
  },
  data() {
    return {
      topic: null,
      profilUpdate: null,
      editData: this.value,
      breadcrumb: null,

      result: false,
      info: null,
    };
  },

  methods: {
    async submitProfilChange() {
      //? when the update contains a file upload

      //TODO: check if the updated value is different from the original value before submitting the request
      if (this.topic && this.profilUpdate) {
        if (this.profilUpdate.files) {
          const fileIDs = await this.uploadFiles(this.profilUpdate.files);
         
          if (fileIDs) {
            this.profilUpdate.files = fileIDs;
            console.log("here is the update", this.profilUpdate);
          }
        }
        //? inserts new row in public.tbl_cis_profil_update
        //* calls the update api call if an update field is present in the data that was passed to the modal
        const handleApiResponse = (res) => {
          if (res.data.error == 0) {
            this.result = true;
            this.hide();
            Alert.popup(
              "Ihre Anfrage wurde erfolgreich gesendet. Bitte warten Sie, während sich das Team um Ihre Anfrage kümmert."
            );
          } else {
            this.result = false;
            this.hide();
            Alert.popup(
              "Ein Fehler ist aufgetreten: " + JSON.stringify(res.data.retval)
            );
          }
        };

        this.editData.updateID
          ? Vue.$fhcapi.UserData.updateProfilRequest(
              this.topic,
              this.profilUpdate,
              this.editData.updateID
            ).then((res) => {
              handleApiResponse(res);
            })
          : Vue.$fhcapi.UserData.insertProfilRequest(
              this.topic,
              this.profilUpdate
            ).then((res) => {
              handleApiResponse(res);
            });
      }
    },

    uploadFiles: async function (files) {
      let updatedFiles = [];

      if (this.editData.updateID) {
        //? if we are updating an already existing profilRequest
        const existingFiles = await Vue.$fhcapi.UserData.getProfilRequestFiles(
          this.editData.updateID
        ).then((res) => {
          return res.data;
        });

        let filesToKeep = [];
        let filesToDelete = [];
        console.log(existingFiles);
        console.log(files);
        existingFiles.forEach((file) => {
          Array.from(files).some((f) => f.name === file.name)
            ? filesToKeep.push(file)
            : filesToDelete.push(file.dms_id);
        });
        
        //? only keeps the newest version of the documents and deletes the old versions in the database
        Vue.$fhcapi.UserData.deleteOldVersionFiles(
          filesToDelete
        ).then((res) => {
          console.log(res);
        });  
       

        updatedFiles = [...filesToKeep];
      }

      let formData = new FormData();
      for (let i = 0; i < files.length; i++) {
        if (files[i].type !== "application/x.fhc-dms+json")
          formData.append("files[]", files[i]);
      }

      await Vue.$fhcapi.UserData.insertFile(formData)
        .then((res) => {
          /* returns file information as 
        [{"name":"example.png", "dms_id":282531}] */

          updatedFiles = updatedFiles.concat(
            res.data?.map((file) => {
              console.log("here are the files:",file);
              return { dms_id: file.dms_id, name: file.client_name};
            })
          );
        })
        .catch((err) => {
          console.log(err);
        });

      return updatedFiles;
    },
  },
  computed: {},
  created() {
    if (this.editData.topic) {
      //? if the topic was passed through the prop add it to the component
      this.topic = this.editData.topic;
    }
  },
  mounted() {
    this.modal = this.$refs.modalContainer.modal;
  },
  popup(options) {
    return BsModal.popup.bind(this)(null, options);
  },
  template: `
  <bs-modal ref="modalContainer" v-bind="$props" body-class="" dialog-class="modal-lg" class="bootstrap-alert" backdrop="false" >
    
  <template v-if="title" v-slot:title>
      {{title }}  
    </template>
    <template v-slot:default>

    <nav aria-label="breadcrumb" class="ps-2  ">
      <ol class="breadcrumb ">
        <li class="breadcrumb-item"  v-for="element in breadcrumb">{{element}}</li>
      
      </ol>
    </nav>

   
    <edit-profil-select @submit="submitProfilChange" v-model:breadcrumb="breadcrumb" v-model:topic="topic" v-model:profilUpdate="profilUpdate" ariaLabel="test" :list="editData"></edit-profil-select>
   

    </template>
    <!-- optional footer -->
    <template   v-slot:footer>
      
    <button class="btn btn-outline-danger " @click="hide">Abbrechen</button>    
      <button v-if="profilUpdate"  @click="submitProfilChange" role="button" class="btn btn-primary">Senden</button>
    </template>
    <!-- end of optional footer --> 
  </bs-modal>`,
};
