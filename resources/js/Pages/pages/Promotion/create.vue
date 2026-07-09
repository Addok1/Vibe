<script setup>
  import Layout from "@/Layouts/main.vue";
  import PageHeader from "@/Components/page-header.vue";
  import { useForm, router } from "@inertiajs/vue3";
  import axios from "axios";
  import grapesjs from "grapesjs";
  import html2canvas from "html2canvas";
  import { onMounted, ref } from "vue";
  import presetNewsletter from "grapesjs-preset-newsletter";
  import flatPickr from "vue-flatpickr-component";
  import "flatpickr/dist/flatpickr.css";
  import { useI18n } from 'vue-i18n';
    

    const { t } = useI18n();
    const announcement = ref(false);

    const props = defineProps({ template: Object });
    
    const editor = ref(null);
    
    const form = useForm({
      subject: props.template?.subject || "",
      html: props.template?.html || "",
      time: props.template?.time || "",
      date: [],
    });

    const rangeDateconfig = {
  mode: "range",
  dateFormat: "Y-m-d",
};

const buildFinalHtml = () => {
  const html = editor.value.getHtml();
  const css = editor.value.getCss();

  return `
    <!DOCTYPE html>
    <html>
      <head>
        <meta charset="utf-8" />
        <style>${css}</style>
      </head>
      <body>
        ${html}
      </body>
    </html>
  `;
};


    const initEditor = () => {
  editor.value = grapesjs.init({
    container: "#gjs",
    height: "700px",
    fromElement: false,

    storageManager: {
      autoload: false,
      autosave: false,
    },

    assetManager: {
      upload: false,
      embedAsBase64: true,
      uploadText: "Drop images here or click",
    },

    highlightMode: 0,

    plugins: [presetNewsletter],
    pluginsOpts: {
      presetNewsletter: {
        modalTitleImport: "Import HTML",
        modalLabelImport: "Paste your HTML here",
        importPlaceholder: "<table>...</table>",
        cellStyle: {
          "font-size": "14px",
          "font-family": "Arial, Helvetica, sans-serif",
        },
      },
    },

    canvas: {
      styles: [
        "https://unpkg.com/grapesjs/dist/css/grapes.min.css",
      ],
    },
  });

  // Load existing HTML (edit mode)
  if (props.template?.html) {
    editor.value.setComponents(props.template.html);
  }
};
    
    // const capturePreview = async () => {
    //   const iframe = document.createElement("iframe");
    //   document.body.appendChild(iframe);
    //   iframe.contentDocument.write(form.html);
    //   iframe.contentDocument.close();
    
    //   const canvas = await html2canvas(iframe.contentDocument.body);
    //   document.body.removeChild(iframe);
    
    //   return canvas.toDataURL("image/png");
    // };

    const capturePreview = async (finalHtml) => {
  const iframe = document.createElement("iframe");
  iframe.style.position = "absolute";
  iframe.style.left = "-9999px";
  iframe.style.top = "0";

  document.body.appendChild(iframe);

  iframe.contentDocument.open();
  iframe.contentDocument.write(finalHtml);
  iframe.contentDocument.close();

  await new Promise(r => setTimeout(r, 500));

  const canvas = await html2canvas(iframe.contentDocument.body, {
    backgroundColor: "#ffffff",
    scale: 2,
  });

  document.body.removeChild(iframe);

  return canvas.toDataURL("image/png");
};
    
    // const submit = async () => {
    //   form.html = editor.value.getHtml();
    
    //   const preview = await capturePreview();
    
    //   if (props.template) {
    //     await axios.post(`/promotion/templates/update/${props.template.id}`, {
    //       ...form.data(),
    //       preview_image: preview
    //     });
    //   } else {
    //     await axios.post("/promotion/templates/store", {
    //       ...form.data(),
    //       preview_image: preview
    //     });
    //   }
    
    //   router.get("/promotion/templates");
    // };

    const submit = async () => {

      const finalHtml = buildFinalHtml();
      form.html = finalHtml;
  // form.html = editor.value.getHtml();

  // const preview = await capturePreview();
  const preview = await capturePreview(finalHtml);

  let dateValue = form.date;

  // 🔒 Normalize flatpickr output
  if (Array.isArray(dateValue)) {
    dateValue = dateValue.join(" to ");
  }

  await axios.post("/promotion/templates/store", {
    ...form.data(),
    date: dateValue,
    preview_image: preview,
  });

  router.get("/promotion/templates");
};
    
    // onMounted(initEditor);
    onMounted(() => {
  initEditor();

  if (props.template) {
    form.date = [
      props.template.from?.split(" ")[0],
      props.template.to?.split(" ")[0],
    ];
  }
});
    </script>
    
    <template>
      <Layout>
        <PageHeader
          :title="template ?  $t('edit') : $t('create')"
          :pageTitle="$t('announcement')"
          pageLink="/promotion/templates"
        />
    
        <BCard>
          <BCardHeader class="border-0">
            <BLink @click="announcement = !announcement">
              <h6 class="text-success text-decoration-underline text-decoration-underline-success float-end">{{$t('how_it_works')}}</h6>
            </BLink>
        </BCardHeader>
          <BCardBody>
            <div class="mb-3">
              <label>{{ $t("subject")}}</label>
              <input type="text" class="form-control" v-model="form.subject" />
            </div>
    
            <div id="gjs"></div>

            <div class="mb-3">
              <label>{{ $t("from_to_date") }} <span class="text-danger">*</span></label>

              <flat-pickr
                v-model="form.date"
                :config="rangeDateconfig"
                class="form-control"
                placeholder="Select date range"
              />
            </div>
            <div class="mb-3">
              <label>{{ $t("time(in_sec)") }}</label>
              <input type="number" class="form-control" v-model="form.time" />
            </div>
    
            <div class="text-end mt-3">
              <button class="btn btn-primary" @click="submit">
                {{ template ? 'Update' : 'Save' }}
              </button>
            </div>
          </BCardBody>
        </BCard>
        <BModal hide-footer :title="$t('announcement')" class="v-modal-custom" size="lg" v-model="announcement">
          <p class="text-muted mb-2">How Announcement Works:</p>

          <div class="d-flex mt-3">
            <div class="flex-shrink-0">
              <i class="ri-checkbox-circle-fill text-success"></i>
            </div>
            <div class="flex-grow-1 ms-2">
              <p class="text-muted mb-1 fw-medium">How to Create an Announcement</p>
              <ul class="text-muted mb-0 ps-3">
                <li>Enter the announcement subject at the top of the page.</li>
                <li>Drag blocks from the right panel into the white editor area.</li>
                <li>Click on any block to edit its text, style, or settings.</li>
                <li>Use <strong>Text</strong> blocks to add content and <strong>Button</strong> blocks for actions.</li>
                <li>Arrange sections by dragging them up or down as needed.</li>
                <li>Design the layout for mobile screen size for the best Driver App experience.</li>
                <li>Use the <strong>Preview</strong> option to check the mobile view.</li>
                <li>Select the <strong>From–To Date</strong> to set the active period.</li>
                <li>Enter <strong>Time (in seconds)</strong> if a sending delay is required.</li>
                <li>Click <strong>Save</strong> to publish the announcement.</li>
              </ul>
            </div>
          </div>

          <div class="d-flex mt-4">
            <div class="flex-shrink-0">
              <i class="ri-checkbox-circle-fill text-success"></i>
            </div>
            <div class="flex-grow-1 ms-2">
              <p class="text-muted mb-0 fw-medium">Driver View</p>
              <p class="text-muted mb-0">
                Once published, the announcement is instantly available in the Driver Mobile App
                with a responsive and user-friendly layout.
              </p>
            </div>
          </div>

          <div class="modal-footer v-modal-footer">
            <BLink
              href="javascript:void(0);"
              class="btn btn-link link-success fw-medium"
              @click="announcement = false"
            >
              <i class="ri-close-line me-1 align-middle"></i> Close
            </BLink>
          </div>
        </BModal>
      </Layout>
    </template>
    <style scoped>
      /* Ensure grapes main CSS overrides admin theme in this area */
      @import url("https://unpkg.com/grapesjs/dist/css/grapes.min.css");
      
      .gjs-wrapper {
        /* keep the builder area separate in layout */
        width: 100%;
        min-height: 700px;
      }
      
      /* Ensure the canvas area is sized and shows its panels correctly */
      #gjs {
        height: 700px !important;
        background: #ffffff !important;
        border: 1px solid #ddd !important;
        overflow: auto !important;
      }
      
      /* Reduce chance of admin CSS inflating icons/panels */
      .gjs-pn * { font-size: 12px !important; }
      .gjs-block { max-width: 220px !important; }
      .gjs-block img { width: auto !important; height: auto !important; max-width: 100% !important; }
      
      /* If your admin sets global svg/img width:100%, limit it inside GJS panels */
      .gjs-blocks-c .gjs-block svg,
      .gjs-pn-panel svg {
        width: 34px !important;
        height: 34px !important;
      }
      
      /* Ensure canvas white background */
      .gjs-cv-canvas {
        background-color: #ffffff !important;
      }
      
      /* Small helpful reset to avoid common admin rules (padding/margins) */
      .gjs-wrapper .gjs-block,
      .gjs-wrapper .gjs-block-label,
      .gjs-wrapper .gjs-pn-panel {
        margin: 0 !important;
        padding: 0 !important;
      }
      @import url("https://unpkg.com/grapesjs/dist/css/grapes.min.css");
      
      .gjs-wrapper{ width:100%; min-height:700px; }
      #gjs{ height:700px !important; background:#fff !important; border:1px solid #ddd !important; overflow:auto !important;}
      .gjs-pn *{ font-size:12px !important; }
      .gjs-block{ max-width:220px !important; }
      .gjs-block img{ width:auto !important; height:auto !important; max-width:100% !important; }
      .gjs-blocks-c .gjs-block svg, .gjs-pn-panel svg { width:34px !important; height:34px !important; }
      .gjs-cv-canvas{ background-color:#fff !important; }
      
      
      /* Now reapply GJS styles */
      @import url("https://unpkg.com/grapesjs/dist/css/grapes.min.css");
      
      #builder-container #gjs {
        height: 700px !important;
        background: #fff !important;
        border: 1px solid #ddd !important;
      }
      </style>  