<script setup>
  import Layout from "@/Layouts/main.vue";
  import PageHeader from "@/Components/page-header.vue";
  import Pagination from "@/Components/Pagination.vue";
  import { ref, onMounted } from "vue";
  import { router } from "@inertiajs/vue3";
  import axios from "axios";
  import Swal from "sweetalert2";
  import { useI18n } from 'vue-i18n';

  const { t } = useI18n();
  const results = ref([]);
  const paginator = ref({});
  const search = ref("");

  const showPreviewModal = ref(false);
  const previewImageUrl = ref("");

  const openPreview = (url) => {
    previewImageUrl.value = url;
    showPreviewModal.value = true;
  };

  const closePreview = () => {
    showPreviewModal.value = false;
    previewImageUrl.value = "";
  };
  
  const fetchDatas = async (page = 1) => {
    const res = await axios.get("/promotion/templates/list", {
      params: { search: search.value, page }
    });
    results.value = res.data.results;
    paginator.value = res.data.paginator;
  };
  
  const deleteTemplate = async (id) => {
    Swal.fire({
      title: "Are you sure?",
      icon: "warning",
      showCancelButton: true,
    }).then(async (result) => {
      if (result.isConfirmed) {
        await axios.delete(`/promotion/templates/delete/${id}`);
        fetchDatas();
        Swal.fire(t('success'), t('template_deleted_successfully'), 'success');
      }
    });
  };
//   const toggleActive = async (template) => {
//   await axios.post(
//     `/promotion/templates/${template.id}/toggle-active`
//   );

//   template.active = !template.active;
// };
  
const togglePromotionStatus = async (id, status) => {
  Swal.fire({
    title: 'Are you sure?',
    text: 'You are about to change status',
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#34c38f",
    cancelButtonColor: "#f46a6a",
    confirmButtonText: 'Yes',
    cancelButtonText: 'Cancel'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.post(
          `/promotion/templates/${id}/toggle-active`,
          { status }   //  send desired status
        );

        const index = results.value.findIndex(item => item.id === id);
        if (index !== -1) {
          results.value[index].active = status;
        }

        Swal.fire(t('success'), t('status_updated_successfully'), 'success');
      } catch (error) {
        console.error(error);
        Swal.fire(t('error'), t('failed_to_update_status'), 'error');
      }
    }
  });
};
const isExpired = (promotion) => {
  return new Date(promotion.to) < new Date();
};
const formatDateDMY = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  return `${day}-${month}-${year}`;
};
  onMounted(fetchDatas);
  </script>
  
  <template>
    <Layout>
      <PageHeader :title="$t('announcement')" :pageTitle="$t('announcement_template')" />
  
      <BCard>
        <BCardHeader class="d-flex justify-content-between">
          <input
            type="text"
            class="form-control w-25"
            placeholder="Search subject..."
            v-model="search"
            @input="fetchDatas"
          />
  
          <button class="btn btn-primary" @click="router.get('/promotion/templates/create')">
            <i class="ri-add-line align-bottom me-1"></i> {{ $t("add_templates") }}  
          </button>
        </BCardHeader>
  
        <BCardBody class="border border-dashed border-end-0 border-start-0">
          <div class="table-responsive">
            <table class="table align-middle position-relative table-nowrap">
            <thead class="table-active">
              <tr>
                <th scope="col">{{ $t("preview") }}</th>
                <th scope="col">{{ $t("subject") }}</th>
                <th scope="col">{{ $t("from_to_date") }}</th>
                <th scope="col">{{ $t("status") }}</th>
                <th scope="col">{{ $t("action") }}</th>
              </tr>
            </thead>
  
            <tbody v-if="results.length > 0">
              <tr v-for="t in results" :key="t.id">
                <td>
                  <!-- <img
                    :src="`/storage/uploads/promotion/previews/${t.preview_image}`"
                    style="width:120px"
                  /> -->
                  <img :src="`/storage/uploads/promotion/previews/${t.preview_image}`" class="promo-thumb"
                    @click="openPreview(`/storage/uploads/promotion/previews/${t.preview_image}`)"
                  />
                  <!-- <img :src="t.preview_image_url" style="width:120px" /> -->
                </td>
                <td>{{ t.subject }}</td>
                <td>
                    <div class="d-flex">
                        <div class="flex-grow-1">{{ formatDateDMY(t.from) }}  -  {{ formatDateDMY(t.to) }}</div>
                    </div>
                </td>
                <td>
                  <div
                  :class="{
                    'form-check': true,
                    'form-switch': true,
                    'form-switch-lg': true,
                    'form-switch-success': t.active,
                  }" >
                  <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    :disabled="isExpired(t)"
                    @click.prevent="togglePromotionStatus(t.id, !t.active)"
                    :checked="t.active"
                  >
                </div>
                </td>
                <td>
                  <!-- <button class="btn btn-sm btn-info me-2"
                    @click="router.get(`/promotion/templates/edit/${t.id}`)">
                    Edit
                  </button> -->
                  <button class="btn btn-sm btn-danger"
                    @click="deleteTemplate(t.id)">
                    {{$t("delete")}}
                  </button>
                </td>
              </tr>
            </tbody>
            <tbody v-else>
                        <tr>
                            <td colspan="12" class="text-center">
                                <img src="@assets/images/search-file.gif" alt="Loading..." style="width:100px" />
                                <h5>{{ $t("no_data_found") }}</h5>
                            </td>
                        </tr>
                    </tbody>
          </table>
          </div>
        </BCardBody>
      </BCard>
  
      <Pagination :paginator="paginator" @page-changed="fetchDatas" />
      <BModal
  v-model="showPreviewModal"
  title="Promotion Preview"
  size="lg"
  hide-footer
>
  <div class="text-center">
    <img
      :src="previewImageUrl"
      class="img-fluid promo-full"
    />
  </div>
</BModal>
    </Layout>
  </template>
  <style scoped>
    .promo-thumb {
  width: 120px;
  height: 50px;
  object-fit: cover;     /* crop */
  cursor: pointer;
  border-radius: 4px;
}
.promo-thumb:hover {
  opacity: 0.85;
  box-shadow: 0 0 0 2px #dddddd;
}
.promo-full {
  max-height: 80vh;
  width: auto;
}
  </style>
  