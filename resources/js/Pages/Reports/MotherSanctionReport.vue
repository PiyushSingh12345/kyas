<template>
  <div class="wrapper">
    <Sidebar />

    <div class="main-panel ">
      <Header />

      <div class="container">
        <div class="page-inner allinsideform">

          <div class="page-header">
            <h3 class="fw-bold mb-3">MIS Reports & Dashboards</h3>

            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="login.html">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Mother Sanction Summary</a>
              </li>
               
            </ul>
          </div>

          <div class="row">
              <div class="col-md-12">
                <div class="card">
                
                  <div class="card-header">
                    <div class="card-title">Mother Sanction Summary</div>
                  </div>

                <div class="card-body">
                  <div class="row">
                    <!-- Financial Year -->
                    <div class="col-md-6 col-lg-3">
                      <label for="financialYear" class="form-label fw-semibold">Financial Year (F.Y.)</label>
                      <select class="form-select shadow-sm" v-model="financialYear" id="financialYear">
                        <option disabled value="">Select Financial Year</option>
                        <option value="2024-2025">2024–2025</option>
                      </select>
                    </div>

                    <!-- State -->
                    <div class="col-md-6 col-lg-3">
                      <label for="stateSelect" class="form-label fw-semibold">State</label>
                      <select 
                        v-model="selectedState" 
                       
                        class="form-select shadow-sm" 
                        id="stateSelect"
                      >
                        <option value="">--- Select State ---</option>
                        <option v-for="state in states" :key="state.id" :value="state.id">
                          {{ state.name }}
                        </option>
                      </select>
                    </div>

                    <!-- Sanction Date -->
                    <div class="col-md-6 col-lg-3">
                      <label for="sanctionDate" class="form-label fw-semibold">Sanction Date</label>
                      <input 
                        type="date" 
                        class="form-control shadow-sm" 
                        id="sanctionDate" 
                        v-model="sanctionDate"
                      >
                    </div>
                     <!-- motherSanctions -->
                    <div class="col-md-6 col-lg-3">
                      <label for="stateSelect" class="form-label fw-semibold">Mother Sanction</label>
                      <select 
                        v-model="selectedMotherSanctionsI" 
                        @change="fetchMotherSanctions" 
                        class="form-select shadow-sm" 
                        id="stateSelect"
                      >
                        <option value="">--- Select MS ---</option>
                        <option v-for="motherSanction in motherSanctions" :key="motherSanction.ky_ms_no" :value="motherSanction.ky_ms_no">
                          {{ motherSanction.ky_ms_no }}
                        </option>
                      </select>
                    </div>
                  </div>

                  <!-- Budget Table -->
                  <div class="table-responsive border rounded shadow-sm">
                  <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-primary text-center">
                      <tr>
                        <th>Budget Head</th>
                        <th>Category</th>
                        <th>Available Fund Amount</th>
                        <th>Mother Sanction Amount</th>
                       
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, index) in reappropriations" :key="index" class="align-middle">
                        <!-- Budget Head (Dropdown with filtering for duplicates) -->
                        <td>
                          <input
                            type="text"
                            v-model="row.budget_head"
                            class="form-control form-control-sm bg-light"
                            disabled
                          />
                         
                        </td>

                        <!-- Category (read-only) -->
                        <td>
                          <input
                            type="text"
                            v-model="row.category"
                            class="form-control form-control-sm bg-light"
                            disabled
                          />
                        </td>

                        <!-- Available Fund (read-only) -->
                        <td>
                          <input
                            type="text"
                            v-model="row.available_amount"
                            class="form-control form-control-sm bg-light text-end"
                            disabled
                          />
                        </td>

                        <!-- Mother Sanction Amount (editable, validated) -->
                        <td>
                          <input
                            type="number"
                            v-model="row.sanction_amount"
                            class="form-control form-control-sm text-end"
                            @input="checkSanctionAmount(row)"
                            min="0"
                            step="0.01"
                            disabled
                          />
                        </td>

                     
                      </tr>
                    </tbody>
                  </table>

                  </div>

                  <!-- File Uploads -->
                  <div class="bg-white border rounded shadow-sm p-4 mt-4">
                    <div class="row gy-3">
                      <div class="col-md-6">
                        <label for="ucFile" class="form-label fw-semibold">UC Received From State</label>
                        <input 
                          type="file" 
                          class="form-control" 
                          id="ucFile" 
                          @change="handleUcFileChange" 
                          name="uc_file_path" 
                          accept="image/*,application/pdf"
                        />
                       <div v-if="ucFilePreview || ucFileUrl" class="mt-3">
                          <template v-if="ucFilePreview">
                            <template v-if="ucFile?.type?.startsWith('image/')">
                              <img 
                                :src="ucFilePreview" 
                                alt="UC File Preview" 
                                class="img-thumbnail" 
                                style="max-width: 250px; max-height: 150px; object-fit: contain;"
                              />
                            </template>
                            <template v-else>
                              <a 
                                :href="ucFilePreview" 
                                target="_blank" 
                                class="btn btn-outline-primary btn-sm"
                              >
                                <i class="bi bi-file-earmark-text"></i> Preview UC File
                              </a>
                            </template>
                          </template>
                          <template v-else>
                            <a 
                              :href="ucFileUrl" 
                              target="_blank" 
                              class="btn btn-outline-primary btn-sm"
                            >
                              <i class="bi bi-file-earmark-text"></i> View UC File
                            </a>
                          </template>
                        </div>

                      </div>

                      <div class="col-md-6">
                        <label for="sanctionFile" class="form-label fw-semibold">Signed Copy of Mother Sanction</label>
                        <input 
                          type="file" 
                          class="form-control" 
                          id="sanctionFile" 
                          @change="handleSanctionFileChange" 
                          name="signed_copy_path" 
                          accept="image/*,application/pdf"
                        />
                        <div v-if="sanctionFilePreview || sanctionFileUrl" class="mt-3">
                            <template v-if="sanctionFilePreview">
                              <template v-if="sanctionFile?.type?.startsWith('image/')">
                                <img 
                                  :src="sanctionFilePreview" 
                                  alt="Sanction File Preview" 
                                  class="img-thumbnail" 
                                  style="max-width: 250px; max-height: 150px; object-fit: contain;"
                                />
                              </template>
                              <template v-else>
                                <a 
                                  :href="sanctionFilePreview" 
                                  target="_blank" 
                                  class="btn btn-outline-primary btn-sm"
                                >
                                  <i class="bi bi-file-earmark-text"></i> Preview Sanction File
                                </a>
                              </template>
                            </template>
                            <template v-else>
                              <a 
                                :href="sanctionFileUrl" 
                                target="_blank" 
                                class="btn btn-outline-primary btn-sm"
                              >
                                <i class="bi bi-file-earmark-text"></i> View Sanction File
                              </a>
                            </template>
                          </div>

                      </div>
                    </div>
                  </div>

                  <!-- Total Sanction Amount Summary -->
                  <div class="d-flex justify-content-end mt-4">
                    <h6 class="fw-bold text-primary">
                      Total Sanction Amount: 
                      <span class="text-success">
                        {{ totalSanctionAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                      </span>
                    </h6>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <Footer />
    </div>
  </div>
</template>

<style scoped>
.wrapper {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.page-inner {
  max-width: 1200px;
  margin: 0 auto;
}

.highlight_textbox {
  font-weight: 600;
  color: #0d6efd;
}

.breadcrumb-item a {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.breadcrumb-item a i {
  font-size: 1.1rem;
}

/* Smooth hover for buttons */
button.btn:hover {
  filter: brightness(0.9);
  transition: filter 0.2s ease-in-out;
}

/* Table row hover */
table.table tbody tr:hover {
  background-color: #e9f5ff;
}

/* Responsive tweaks */
@media (max-width: 767px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }
  .page-header nav {
    margin-top: 0.5rem;
  }
}
</style>


<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { router } from '@inertiajs/vue3'

// State and form data
const motherSanctions = ref([])
const reportData = ref([])
const isLoadingReport = ref(false)

const states = ref([])
const selectedState = ref('')
const selectedMotherSanctionsI = ref('')
const selectedSlsId = ref('')
const slsData = ref([])
const financialYear = ref('')
const msSequenceNo = ref('')
const sanctionNo = ref('')
const ifdNo = ref('')
const sanctionDate = ref('')
const pdComponent = ref('')

const budgetHeads = ref([])
const fundAllocations = ref([])

const ucFile = ref(null)
const sanctionFile = ref(null)
const ucFilePreview = ref(null)
const sanctionFilePreview = ref(null)
const ucFileUrl = ref(null)
const sanctionFileUrl = ref(null)

// Table rows for reappropriation
const reappropriations = ref([
  { budget_head: '', category: '', available_amount: '', sanction_amount: '' }
])

// ------------------------ Computed ------------------------
const kyMsNo = computed(() => {
  if (!financialYear.value || !selectedState.value || !msSequenceNo.value || !selectedSlsId.value) {
    return ''
  }

  const yearPart = financialYear.value.split('-')[0].slice(-2)
  const stateCode = selectedState.value
  const sequenceNo = msSequenceNo.value.toString().padStart(2, '0')
  const sls = selectedSlsId.value

  return `MS${yearPart}${stateCode}${sequenceNo}${sls}`
})

const selectedBudgetHeads = computed(() =>
  reappropriations.value.map(row => row.budget_head).filter(Boolean)
)

const totalSanctionAmount = computed(() => {
  return reappropriations.value.reduce((sum, row) => {
    const amount = parseFloat(row.sanction_amount)
    return sum + (isNaN(amount) ? 0 : amount)
  }, 0)
})

// ------------------------ Watchers ------------------------
watch(
  [financialYear, selectedState, sanctionDate, selectedMotherSanctionsI],
  ([newYear, newState, newDate, newMs]) => {
    if (newYear && newState && newDate && newMs) {
      fetchMotherSanctions()
    }
  }
)
// ------------------------ Lifecycle ------------------------
onMounted(async () => {
  try {
    const [statesRes, budgetHeadsRes, resp] = await Promise.all([
      fetch('/api/states'),
      fetch('/api/budget-heads'),
      fetch('/api/mother-sanctions-list')
    ])

    if (statesRes.ok) states.value = await statesRes.json()
    if (budgetHeadsRes.ok) budgetHeads.value = await budgetHeadsRes.json()
    if (resp.ok) motherSanctions.value = await resp.json()

  } catch (error) {
    console.error('Fetch error:', error)
  }
})

// ------------------------ Methods ------------------------
async function fetchMotherSanctions() {
  if (!financialYear.value || !selectedState.value) return;
 
  isLoadingReport.value = true;
  reportData.value = [];
  try {
    const params = new URLSearchParams({
      year: financialYear.value,
      state_id: selectedState.value,
      sanction_date: sanctionDate.value || '',
      ky_ms_no: selectedMotherSanctionsI.value || ''
    });
    const res = await fetch(`/reports/mother-sanctions-data?${params}`);
    if (res.ok) {
      const data = await res.json();
      reportData.value = data;

      // ✅ Set reappropriations from response
      if (Array.isArray(data) && data.length > 0) {
        reappropriations.value = data.map(item => ({
          budget_head: item.budget_head || '',
          category: item.category || '',
          available_amount: item.available_fund || '',
          sanction_amount: item.mother_sanction_amount || ''
        }));
        console.log("reappropriations",reappropriations)

        ucFileUrl.value = data[0].uc_received_from_state
    ? `/storage/${data[0].uc_received_from_state}`
    : null

  sanctionFileUrl.value = data[0].signed_copy_of_mother_sanction
    ? `/storage/${data[0].signed_copy_of_mother_sanction}`
    : null
      } else {
        reappropriations.value = [
          { budget_head: '', category: '', available_amount: '', sanction_amount: '' }
        ];
      }
    }
  } catch (error) {
    console.error('Error fetching mother sanctions:', error);
  } finally {
    isLoadingReport.value = false;
  }
}


async function fetchSlsData() {
  if (!selectedState.value) {
    slsData.value = []
    return
  }

  try {
    const res = await fetch(`/api/sls-data/${selectedState.value}`)
    if (res.ok) {
      slsData.value = await res.json()
    } else {
      console.error('Failed to fetch SLS data')
    }
  } catch (error) {
    console.error('Error fetching SLS data:', error)
  }
}

async function fetchFundAllocationData() {
  if (!selectedSlsId.value || !selectedState.value) return

  try {
    const response = await fetch(`/api/fund-allocation/${selectedSlsId.value}/${selectedState.value}`)
    if (response.ok) {
      const data = await response.json()
      fundAllocations.value = data
      pdComponent.value = data.length > 0 ? data[0].slsPD : ''
    } else {
      fundAllocations.value = []
      pdComponent.value = ''
      console.error('Failed to fetch fund allocation data')
    }
  } catch (error) {
    console.error('Error fetching fund allocation data:', error)
    fundAllocations.value = []
    pdComponent.value = ''
  }
}

async function fetchBudgetDetails(row) {
  if (!row.budget_head || !selectedSlsId.value || !selectedState.value) return

  try {
    const res = await fetch(`/api/fund-allocation/by-budget?budget=${encodeURIComponent(row.budget_head)}&sls_id=${encodeURIComponent(selectedSlsId.value)}&state_id=${selectedState.value}`)
    if (res.ok) {
      const data = await res.json()
      row.category = data.category
      row.available_amount = data.amount
    } else {
      row.category = ''
      row.available_amount = ''
      console.error('Budget details not found')
    }
  } catch (error) {
    console.error('Error fetching budget details:', error)
  }
}

function handleUcFileChange(e) {
  const file = e.target.files[0]
  ucFile.value = file
  if (file) {
    ucFilePreview.value = URL.createObjectURL(file)
  }
}

function handleSanctionFileChange(e) {
  const file = e.target.files[0]
  sanctionFile.value = file
  if (file) {
    sanctionFilePreview.value = URL.createObjectURL(file)
  }
}

function checkSanctionAmount(row) {
  const sanction = parseFloat(row.sanction_amount)
  const available = parseFloat(row.available_amount)

  if (!isNaN(sanction) && !isNaN(available) && sanction > available) {
    alert("Sanction amount exceeds available funds!")
    row.sanction_amount = ''
  }
}

function removeReappropriationRow(index) {
  reappropriations.value.splice(index, 1)
}

function addReappropriationRow() {
  reappropriations.value.push({
    budget_head: '',
    category: '',
    available_amount: '',
    sanction_amount: ''
  })
}
</script>
