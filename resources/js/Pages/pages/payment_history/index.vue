<script>
    import { Head, useForm } from '@inertiajs/vue3'
    import Layout from "@/Layouts/main.vue"
    import PageHeader from "@/Components/page-header.vue"
    import Pagination from "@/Components/Pagination.vue"
    import Multiselect from "@vueform/multiselect"
    import Swal from "sweetalert2"
    import { ref, watch } from "vue"
    import axios from "axios"
    import { debounce } from "lodash"
    import { useI18n } from "vue-i18n"
    import '@vueform/multiselect/themes/default.css'
    
    export default {
      components: {
        Layout,
        PageHeader,
        Head,
        Pagination,
        Multiselect,
      },
    
      setup() {
        const { t } = useI18n()
    
        const selectedType = ref('')
        const searchTerm = ref('')
        const options = ref([])
    
        const selectedUserId = ref(null)
        const selectedEntity = ref(null)
        const showWalletSection = ref(false)
    
        const results = ref([])
        const paginator = ref({})
    
        const filter = useForm({ limit: 10 })
    
        const walletApiMap = {
          user: {
            add: id => `/users/wallet-add-amount/${id}`,
            history: id => `/users/wallet-history/list/${id}`,
          },
          driver: {
            add: id => `/approved-drivers/wallet-add-amount/${id}`,
            history: id => `/approved-drivers/wallet-history/list/${id}`,
          },
          owner: {
            add: id => `/manage-owners/wallet-add-amount/${id}`,
            history: id => `/manage-owners/wallet-history/list/${id}`,
          },
        }
    
        /* 🔍 Fetch users on search */
        const fetchUsers = async (query) => {
          if (!selectedType.value || !query) {
            options.value = []
            return
          }
    
          const res = await axios.get('/manage-payment/list', {
            params: {
              type: selectedType.value,
              search: query,
            },
          })
    
          options.value = (res.data.results || []).map(u => ({
            value: u.id,
            label: `${u.name} (${u.email ?? ''})`,
          }))
        }
    
        const onSearch = debounce(fetchUsers, 300)
    
        const selectUserForWallet = () => {
          if (!selectedUserId.value) return
    
          selectedEntity.value = {
            id: selectedUserId.value,
            type: selectedType.value,
          }
    
          showWalletSection.value = true
          fetchWalletHistory()
        }
    
        const form = ref({
          amount: '',
          operation: 'add',
        })
    
        const handleSubmit = async () => {
          try {
            const fd = new FormData()
            fd.append('amount', form.value.amount)
            fd.append('operation', form.value.operation)
    
            await axios.post(
              walletApiMap[selectedEntity.value.type].add(selectedEntity.value.id),
              fd
            )
    
            Swal.fire('Success', 'Amount updated successfully', 'success')
            form.value.amount = ''
            form.value.operation = 'add'
            fetchWalletHistory()
          } catch (e) {
            Swal.fire('Error', e.response?.data?.message || 'Failed', 'error')
          }
        }
    
        const fetchWalletHistory = async (page = 1) => {
          const res = await axios.get(
            walletApiMap[selectedEntity.value.type].history(selectedEntity.value.id),
            { params: { page, limit: filter.limit } }
          )
    
          results.value = res.data.results ?? []
          paginator.value = res.data.paginator ?? {}
        }
    
        watch(selectedType, () => {
          selectedUserId.value = null
          options.value = []
          showWalletSection.value = false
          results.value = []
        })

        watch(selectedUserId, (val) => {
  if (!val) {
    showWalletSection.value = false
    results.value = []
    paginator.value = {}
    selectedEntity.value = null
  }
})
    
        return {
          t,
          selectedType,
          selectedUserId,
          options,
          onSearch,
          selectUserForWallet,
          showWalletSection,
          form,
          results,
          paginator,
          handleSubmit,
          fetchWalletHistory,
        }
      },
    }
    </script>
    
    
    
    <template>
        <Layout>
          <Head title="Payment History" />
          <PageHeader :title="$t('payment_history')" />
      
          <BRow>
            <BCol lg="12">
              <BCard no-body>
                <BCardHeader>
      
                  <!-- ROLE -->
                  <div class="mb-3 col-md-4">
                    <label>Select Role</label>
                    <select v-model="selectedType" class="form-select">
                      <option value="">Select</option>
                      <option value="user">User</option>
                      <option value="driver">Driver</option>
                      <option value="owner">Owner</option>
                    </select>
                  </div>
      
                  <!-- SEARCHABLE DROPDOWN -->
                  <div class="mb-3 col-md-6" v-if="selectedType">
                    <label>Select User</label>
      
                    <Multiselect
                      v-model="selectedUserId"
                      :close-on-select="true"
                      :options="options"
                      :searchable="true"
                      :internal-search="false"
                      :clear-on-select="false"
                      placeholder="Search name/email/mobile..."
                      @search-change="onSearch"
                    />
                    <button
                      class="btn btn-primary mt-3"
                      :disabled="!selectedUserId"
                      @click="selectUserForWallet"
                    >
                      View Wallet
                    </button>
                  </div>
      
                </BCardHeader>
      
                <!-- WALLET FORM -->
                <BCardBody v-if="showWalletSection">
                  <form @submit.prevent="handleSubmit" class="row mb-5">
                    
                    <div class="col-md-4">
                        <label for="amount" class="form-label">{{$t("amount")}}
                                                    <span class="text-danger">*</span>
                                                </label>
                      <input type="number" class="form-control" v-model="form.amount" placeholder="Amount" />
                    </div>
      
                    <div class="col-md-4">
                        <label for="operation" class="form-label">{{$t("operation")}}
                                                    <span class="text-danger">*</span>
                                                </label>
                      <select class="form-select" v-model="form.operation">
                        <option value="add">Credit</option>
                        <option value="subtract">Debit</option>
                      </select>
                    </div>
      
                    <div class="col-md-2 mt-4">
                      <button class="btn btn-success w-100">{{$t("submit")}}</button>
                    </div>
                  </form>
      
                  <table class="table" v-if="results.length">
                     <thead class="table-active">
                        <tr>
                            <th scope="col">{{$t("date")}}</th>
                            <th scope="col">{{$t("amount")}}</th>
                            <th scope="col">{{$t("remarks")}}</th>
                            <th scope="col">{{$t("status")}}</th>

                        </tr>
                    </thead>
                    <tr v-for="row in results" :key="row.id">
                      <td>{{ row.created_at }}</td>
                      <td>{{ row.amount }}</td>
                      <td>{{ row.remarks }}</td>
                      <td>
                        <span :class="row.is_credit ? 'badge bg-success' : 'badge bg-danger'">
                          {{ row.is_credit ? 'Credited' : 'Debited' }}
                        </span>
                      </td>
                    </tr>
                  </table>
      
                  <div v-else class="text-center">No data found</div>
      
                  <Pagination
                    :paginator="paginator"
                    @page-changed="fetchWalletHistory"
                  />
                </BCardBody>
              </BCard>
            </BCol>
          </BRow>
        </Layout>
      </template>
      
      
      
    <style>
    .custom-alert {
        max-width: 600px;
        float: right;
        position: fixed;
        top: 90px;
        right: 20px;
    }
    .rtl .custom-alert {
      max-width: 600px;
      float: left;
      top: -300px;
      right: 10px;
    }
    @media only screen and (max-width: 1024px) {
      .custom-alert {
      max-width: 600px;
      float: right;
      position: fixed;
      top: 90px;
      right: 20px;
    }
    .rtl .custom-alert {
      max-width: 600px;
      float: left;
      top: -230px;
      right: 10px;
    }
    }
    </style>
    <style scoped>
    /* Toggle Button Styles */
    .toggle-buttons {
      display: flex;
      gap: 5px;
      /* margin-bottom: 15px; */
    }
    .list-btn {
      padding: 8px 15px;
      border: none;
      cursor: pointer;
      background: #ddd;
      border-radius: 5px;
    }
    .list-btn.active {
      background: #0ab39c;
      color: white;
    }
    
    /* .card {
      padding: 15px;
      background: #f9f9f9;
      border-radius: 8px;
      box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
    } */
    
    </style>