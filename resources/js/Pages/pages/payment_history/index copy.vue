<script>
    import { Link, Head, useForm } from '@inertiajs/vue3'
    import Layout from "@/Layouts/main.vue"
    import PageHeader from "@/Components/page-header.vue"
    import Pagination from "@/Components/Pagination.vue"
    import Swal from "sweetalert2"
    import { ref, watch } from "vue"
    import axios from "axios"
    import { debounce } from "lodash"
    import { useI18n } from "vue-i18n"
    
    export default {
      components: {
        Layout,
        PageHeader,
        Head,
        Pagination,
        Link,
      },
    
      setup() {
        const { t } = useI18n()
    

        const selectedType = ref('')
        const searchTerm = ref('')
        const users = ref([])
        const drivers = ref([])
        const owners = ref([])
    
        const selectedUserId = ref(null)
        const selectedEntity = ref(null)
        const showWalletSection = ref(false)
    
        const results = ref([])
        const paginator = ref({})
    
        const filter = useForm({ limit: 10 })
    
// wallet api
        const walletApiMap = {
          user: {
            add: (id) => `/users/wallet-add-amount/${id}`,
            history: (id) => `/users/wallet-history/list/${id}`,
          },
          driver: {
            add: (id) => `/approved-drivers/wallet-add-amount/${id}`,
            history: (id) => `/approved-drivers/wallet-history/list/${id}`,
          },
          owner: {
            add: (id) => `/manage-owners/wallet-add-amount/${id}`,
            history: (id) => `/manage-owners/wallet-history/list/${id}`,
          },
        }
    
// search user
        const fetchDatas = async () => {
          if (!selectedType.value || !searchTerm.value) {
            users.value = []
            drivers.value = []
            owners.value = []
            return
          }
    
          const res = await axios.get('/manage-payment/list', {
            params: {
              type: selectedType.value,
              search: searchTerm.value,
            },
          })
    
          if (selectedType.value === 'user') {
            users.value = res.data.results ?? []
            drivers.value = []
            owners.value = []
          }
    
          if (selectedType.value === 'driver') {
            drivers.value = res.data.results ?? []
            users.value = []
            owners.value = []
          }
    
          if (selectedType.value === 'owner') {
            owners.value = res.data.results ?? []
            users.value = []
            drivers.value = []
          }
        }
    
        watch(searchTerm, debounce(fetchDatas, 300))
    

        const selectUserForWallet = () => {
          if (!selectedUserId.value) return
    
          selectedEntity.value = {
            id: Number(selectedUserId.value),
            type: selectedType.value,
          }
    
          showWalletSection.value = true
          fetchWalletHistory()
        }
    
// wallet add or debit
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
    
// wallet history
        const fetchWalletHistory = async (page = 1) => {
          const res = await axios.get(
            walletApiMap[selectedEntity.value.type].history(
              selectedEntity.value.id
            ),
            { params: { page, limit: filter.limit } }
          )
    
          results.value = res.data.results ?? []
          paginator.value = res.data.paginator ?? {}
        }
// clear search
        const clearSearch = () => {
            searchTerm.value = ''
            users.value = []
            drivers.value = []
            owners.value = []
            selectedUserId.value = null
            selectedEntity.value = null
            showWalletSection.value = false
            results.value = []
            paginator.value = {}
        }
    
  
        watch(selectedType, () => {
          searchTerm.value = ''
          users.value = []
          drivers.value = []
          owners.value = []
          selectedUserId.value = null
          selectedEntity.value = null
          showWalletSection.value = false
          results.value = []
        })
        watch(searchTerm, (val) => {
  if (!val) {
    clearSearch()
  }
})
    
        return {
          t,
          selectedType,
          searchTerm,
          users,
          drivers,
          owners,
          selectedUserId,
          showWalletSection,
          form,
          results,
          paginator,
          selectUserForWallet,
          handleSubmit,
          fetchWalletHistory,
          clearSearch
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
                <BCard no-body id="tasksList">

                    <BCardHeader class="border-0">
      
          <!-- ROLE + SEARCH -->
          <div class="row mb-3">
            <div class="col-md-4">
              <label>{{ $t('select_role') }}</label>
              <select v-model="selectedType" class="form-select">
                <option value="">Select</option>
                <option value="user">User</option>
                <option value="driver">Driver</option>
                <option value="owner">Owner</option>
              </select>
            </div>
      
            <!-- <div class="col-md-4">
              <label>{{ $t('search') }}</label>
              <input
                type="text"
                class="form-control"
                v-model="searchTerm"
                placeholder="Search by name"
              />
            </div> -->
            <div class="col-md-4">
  <label>{{ $t('search') }}</label>

  <div class="d-flex gap-2">
    <input
      type="text"
      class="form-control"
      v-model="searchTerm"
      placeholder="Search by name/email/mobile"
    />

    <button
      class="btn btn-outline-secondary"
      type="button"
      @click="clearSearch"
      :disabled="!searchTerm"
    >
      Clear
    </button>
  </div>
</div>
          </div>
      
          <!-- SEARCH RESULT DROPDOWN -->
          <div class="row mb-3" v-if="searchTerm">
            <div class="col-md-6">
              <label>{{ $t('select_user') }}</label>
              <select v-model="selectedUserId" class="form-select">
                <option value="">Select</option>
      
                <option v-for="u in users" :key="u.id" :value="u.id">
                  {{ u.name }}
                </option>
      
                <option v-for="d in drivers" :key="d.id" :value="d.id">
                  {{ d.name }}
                </option>
      
                <option v-for="o in owners" :key="o.id" :value="o.id">
                  {{ o.name }}
                </option>
              </select>
      
              <button
                class="btn btn-primary mt-2"
                :disabled="!selectedUserId"
                @click="selectUserForWallet"
              >
                View Wallet
              </button>
            </div>
          </div>
      
          <!-- WALLET ADD / DEBIT -->
          <div v-if="showWalletSection" class="card mb-3">
            <div class="card-body">
              <form @submit.prevent="handleSubmit" class="row">
                <div class="col-md-4">
                  <label>Amount</label>
                  <input type="number" class="form-control" v-model="form.amount" />
                </div>
      
                <div class="col-md-4">
                  <label>Operation</label>
                  <select class="form-select" v-model="form.operation">
                    <option value="add">Credit</option>
                    <option value="subtract">Debit</option>
                  </select>
                </div>
      
                <div class="col-md-4 d-flex align-items-end">
                  <button class="btn btn-success w-100">Submit</button>
                </div>
              </form>
            </div>
          </div>
      
          <!-- WALLET HISTORY -->
          <div v-if="showWalletSection" class="card">
            <div class="card-header">Wallet History</div>
            <div class="card-body">
              <table class="table" v-if="results.length">
                <tr v-for="row in results" :key="row.id">
                  <td>{{ row.created_at }}</td>
                  <td>{{ row.amount }}</td>
                  <td>{{ row.remarks }}</td>
                  <td>
                    <span
                      class="badge"
                      :class="row.is_credit ? 'bg-success' : 'bg-danger'"
                    >
                      {{ row.is_credit ? 'Credited' : 'Debited' }}
                    </span>
                  </td>
                </tr>
              </table>
      
              <div v-else class="text-center">
                {{ $t('no_data_found') }}
              </div>
      
              <Pagination
                :paginator="paginator"
                @page-changed="fetchWalletHistory"
              />
            </div>
          </div>
          </BCardHeader>
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