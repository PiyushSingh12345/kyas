<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container py-4">
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
                <a href="#">Mother Sanction List</a>
              </li>
            </ul>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Mother Sanction List</div>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="financialYear">F.Y</label>
                        <select class="form-select" v-model="financialYear" id="financialYear">
														<option value="" disabled>Select Financial Year</option>
														<option v-for="year in financialYears" :key="year" :value="year">{{ year }}</option>
												</select>
                        <!-- <select class="form-select" id="financialYear" v-model="financialYear">
                          <option value="2024-2025">2024–2025</option> -->
                          <!-- Add more years as needed -->
                        <!-- </select> -->
                      </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="programDivision">Program Division</label>
                        <select class="form-select" id="programDivision" v-model="selectedProgramDivision">
                          <option value="" selected disabled>Select Program</option>
                          <option v-for="division in programDivisions" :key="division.division_id" :value="division.division_id">
                            {{ division.division_name }}
                          </option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="stateSelect">State</label>
                        <select class="form-select" id="stateSelect" v-model="selectedState">
                          <option value="" selected disabled>Select State</option>
                          <option v-for="state in states" :key="state.id" :value="state.id">
                            {{ state.name }}
                          </option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="sanctionDate">Sanction Date</label>
                        <input type="date" class="form-control" id="sanctionDate" v-model="sanctionDate">
                      </div>
                    </div>
                  </div>
                  <div class="table-responsive mt-3">
                    <table class="table table-bordered table-head-bg-primary">
                      <thead>
                        <tr>
                          <th>S.No</th>
                          <th>No.of Sanction letter</th>
                          <th>Date of Sanction letter</th>
                          <th>Concerned Budget Head</th>
                          <th>Purpose of grant</th>
                          <th>Category</th>
                          <th>Concerned PD</th>
                          <th>Amount Sanctioned (in lakh)</th>
                          <th>Amount of bill (in Lakh)</th>
                          <th>Concerned State/UT</th>
                          <th>NER</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-if="isLoading">
                          <td colspan="11" class="text-center">Loading...</td>
                        </tr>
                        <tr v-else-if="motherSanctions.length === 0">
                          <td colspan="11" class="text-center">No records found.</td>
                        </tr>
                        <tr v-for="(item, index) in motherSanctions" :key="item.id">
                          <td>{{ index + 1 }}</td>
                          <td>{{ item.ky_ms_no }}</td>
                          <td>{{ item.sanction_date }}</td>
                          <td>{{ item.budget_head }}</td>
                          <td>{{ item.pd_component || item.sls_name }}</td>
                          <td>{{ item.category }}</td>
                          <td>{{ item.pd_component }}</td>
                          <td>{{ item.mother_sanction_amount }}</td>
                          <td></td>
                          <td>{{ item.state && item.state.name ? item.state.name : '' }}</td>
                          <td></td>
                        </tr>
                      </tbody>
                    </table>
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
  background-color: #f9fafb;
  min-height: 100vh;
}
.page-header h3 {
  letter-spacing: 0.05em;
}
.table thead.table-primary {
  background-color: #cfe2ff;
  border-color: #b6d4fe;
}
.table-hover tbody tr:hover {
  background-color: #f1f5ff;
}
.form-control-plaintext {
  background-color: transparent;
  border: none;
  padding-left: 0;
  font-size: 1rem;
}
.form-select {
  border-radius: 0.375rem;
  border: 1px solid #ced4da;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.25rem rgb(13 110 253 / 0.25);
  outline: none;
}
.editable-input {
  transition: background-color 0.3s ease, border-color 0.3s ease;
}
.editable-input:focus {
  border-color: #0d6efd;
  background-color: #e7f1ff;
  outline: none;
}
.sticky-header {
  position: sticky;
  top: 0;
  z-index: 2;
}

/* Buttons */
.btn {
  min-width: 110px;
  padding: 0.5rem 1.25rem;
  font-weight: 600;
  border-radius: 0.375rem;
  transition: background-color 0.25s ease, box-shadow 0.25s ease;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #0d6efd;
  border: none;
  color: white;
}
.btn-primary:hover:not(:disabled) {
  background-color: #0b5ed7;
  box-shadow: 0 0 10px rgba(13, 110, 253, 0.5);
}

.btn-outline-primary {
  border: 2px solid #0d6efd;
  color: #0d6efd;
  background: none;
}
.btn-outline-primary:hover:not(:disabled) {
  background-color: #0d6efd;
  color: white;
  box-shadow: 0 0 10px rgba(13, 110, 253, 0.5);
}

.btn-secondary {
  background-color: #6c757d;
  border: none;
  color: white;
}
.btn-secondary:hover:not(:disabled) {
  background-color: #5c636a;
  box-shadow: 0 0 8px rgba(108, 117, 125, 0.5);
}

/* Responsive tweaks */
@media (max-width: 575px) {
  .d-flex.justify-content-end.flex-wrap {
    justify-content: center !important;
  }
}
</style>

<script>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { ref, onMounted, watch } from 'vue'

export default {
  name: 'MotherSanctionListReport',
  components: {
    Header,
    Sidebar,
    Footer
  },
  setup() {
    // Filters
    const financialYears = ref([
      '2024-2025',
      '2023-2024',
      '2022-2023',
      '2021-2022'
    ])
    const financialYear = ref('')
    const selectedProgramDivision = ref('')
    const selectedState = ref('')
    const sanctionDate = ref('')

    // Dropdown data
    const states = ref([])
    const programDivisions = ref([])
    
    // Table data
    const motherSanctions = ref([])
    const isLoading = ref(false)

    // Fetch dropdowns
    const fetchStates = async () => {
      try {
        const res = await fetch('/api/states')
        states.value = res.ok ? await res.json() : []
      } catch (e) {
        states.value = []
      }
    }
    const fetchProgramDivisions = async () => {
      try {
        const res = await fetch('/md-program-divisions')
        programDivisions.value = res.ok ? await res.json() : []
      } catch (e) {
        programDivisions.value = []
      }
    }

    // Fetch mother sanction list with filters
    const fetchMotherSanctions = async () => {
      if (!financialYear.value || !selectedProgramDivision.value) {
        motherSanctions.value = []
        return
      }
      isLoading.value = true
      try {
        const params = new URLSearchParams()
        if (financialYear.value) params.append('year', financialYear.value)
        if (selectedProgramDivision.value) params.append('program_division', selectedProgramDivision.value)
        if (selectedState.value) params.append('state_id', selectedState.value)
        if (sanctionDate.value) params.append('sanction_date', sanctionDate.value)
        const res = await fetch(`/api/mother-sanctions-list?${params}`)
        motherSanctions.value = res.ok ? await res.json() : []
      } catch (e) {
        motherSanctions.value = []
      } finally {
        isLoading.value = false
      }
    }

    // Watch filters
    watch([financialYear, selectedProgramDivision, selectedState, sanctionDate], fetchMotherSanctions)

    onMounted(async () => {
      await Promise.all([
        fetchStates(),
        fetchProgramDivisions()
      ])
      // Do not fetchMotherSanctions on mount (table should be blank initially)
    })

    const resetFilters = () => {
      financialYear.value = ''
      selectedProgramDivision.value = ''
      selectedState.value = ''
      sanctionDate.value = ''
    }

    return {
      financialYear,
      financialYears,
      selectedProgramDivision,
      selectedState,
      sanctionDate,
      states,
      programDivisions,
      motherSanctions,
      isLoading,
      resetFilters
    }
  }
}
</script>
