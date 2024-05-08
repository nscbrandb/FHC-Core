import BsConfirm from "../../components/Bootstrap/Confirm.js";
//import Pagination from "../../components/Pagination/Pagination.js";
import Content from "../../components/Cis/Cms/Content.js";
import Fhcapi from "../api/fhcapifactory.js";

Vue.$fhcapi = Fhcapi;

const app = Vue.createApp({
  components: {
    Content,
  },
  mounted() {
    document.querySelectorAll("#cms [data-confirm]").forEach((el) => {
      el.addEventListener("click", (evt) => {
        evt.preventDefault();
        BsConfirm.popup(el.dataset.confirm)
          .then(() => {
            Axios.get(el.href)
              .then((res) => {
                // TODO(chris): check for success then show message and/or reload
                location = location;
              })
              .catch((err) => console.error("ERROR:", err));
          })
          .catch(() => {});
      });
    });
    document.querySelectorAll("#cms [data-href]").forEach((el) => {
      el.href = el.dataset.href.replace(
        /^ROOT\//,
        FHC_JS_DATA_STORAGE_OBJECT.app_root
      );
    });
  },
});
app.use(primevue.config.default, { zIndex: { overlay: 9999 } });
app.mount("#cms");
//#cms [data-confirm], #cms [data-href]
