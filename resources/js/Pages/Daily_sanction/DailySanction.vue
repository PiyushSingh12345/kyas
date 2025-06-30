<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Daily Sanction Module</div>
                </div>
                <div class="card-body">
                  <div class="row">
                    <!-- Financial Year -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="financialYear">F.Y</label>
                        <select v-model="financialYear" class="form-select" id="financialYear">
                          <option disabled value="">Select Financial Year</option>
                          <option value="2024-2025">2024–2025</option>
                          <option value="2023-2024">2023–2024</option>
                          <option value="2022-2023">2022–2023</option>
                          <option value="2021-2022">2021–2022</option>
                        </select>
                      </div>
                    </div>

                    <!-- State -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="stateSelect">State</label>
                        <select v-model="selectedState" class="form-select" id="stateSelect">
                          <option disabled value="">--- Select State ---</option>
                          <option v-for="state in states" :key="state.id" :value="state.id">
                            {{ state.name }}
                          </option>
                        </select>
                      </div>
                    </div>

                    <!-- DS Date -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="dsDate">DS Date</label>
                        <input type="date" class="form-control" id="dsDate" v-model="dsDate">
                      </div>
                    </div>

                    <!-- Mother Sanction -->
                    <div class="col-md-6 col-lg-3">
					  <div class="form-group">
					    <label for="motherSanction">Mother Sanction</label>
					    <select v-model="selectedMotherSanction" class="form-select" id="motherSanction">
					      <option disabled value="">-- Select --</option>
					      <option v-for="item in motherSanctions" :key="item.id" :value="item.ky_ms_no">
					        {{ item.ky_ms_no }}
					      </option>
					    </select>
					  </div>
					</div>

                    <!-- IFD No. -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="ifdNo">IFD No.</label>
                        <input type="text" class="form-control" id="ifdNo" :value="ifdNo" disabled>

                      </div>
                    </div>

                    <!-- SLS ID -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="slsId">SLS ID</label>
                        <input type="text" class="form-control" id="slsId" :value="slsName" disabled>
                      </div>
                    </div>
                  </div>

                  <!-- Table -->
                  <div class="table-responsive mt-5">
                    <table class="table table-bordered table-head-bg-primary">
                      <thead>
                        <tr>
                          <th>Budget Head</th>
                          <th>Mother Sanctioned Amount</th>
                          <th>Available MS Amount</th>
                          <th>Center Share Amount</th>
                        </tr>
                      </thead>
                      <tbody>
						  <tr v-for="(row, index) in sanctionDetails" :key="index">
						    <td>
						      <input type="text" class="form-control" :value="row.budget_head" disabled>
						    </td>
						    <td>
						      <input type="text" class="form-control" :value="row.mother_sanction_amount" disabled>
						    </td>
						    <td>
						      <input type="text" class="form-control" :value="row.mother_sanction_amount" disabled>
						    </td>
						    <td>
						      <input type="text" class="form-control" v-model="row.center_share_amount">
						    </td>
						  </tr>
						</tbody>
                    </table>
                  </div>

                </div>

                <!-- Footer Buttons -->
                <div class="card-footer">
                  <div class="form">
                    <div class="col-12 d-flex justify-content-center">
                      <button class="btn btn-success me-1" @click="submitForm">Submit</button>

                      <button class="btn btn-danger me-1" @click="resetForm">Reset</button>

                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { ref, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3' 

const states = ref([])
const selectedState = ref('')
const financialYear = ref('')
const dsDate = ref('')
const motherSanctions = ref([])
const selectedMotherSanction = ref('')

// Fetched details
const ifdNo = ref('')
const slsName = ref('')
const sanctionDetails = ref([])
const resetForm = () => {
  financialYear.value = ''
  selectedState.value = ''
  dsDate.value = ''
  selectedMotherSanction.value = ''
  clearDetails()
  form.reset() // only if you're using Inertia's useForm
}

onMounted(async () => {
  try {
    const res = await fetch('/api/states')
    if (res.ok) {
      states.value = await res.json()
    }
  } catch (error) {
    console.error('Error fetching states:', error)
  }
})

const fetchMotherSanctions = async (stateId) => {
  try {
    const res = await fetch(`/api/mother-sanctions?state_id=${stateId}`)
    if (res.ok) {
      motherSanctions.value = await res.json()
    }
  } catch (error) {
    console.error('Error fetching mother sanctions:', error)
  }
}

const fetchMotherSanctionDetails = async (kyMsNo) => {
  try {
    const res = await fetch(`/api/mother-sanction-details/${kyMsNo}`)
    if (res.ok) {
      const data = await res.json()
      ifdNo.value = data.meta.ifd_no
      slsName.value = data.meta.sls_name
      sanctionDetails.value = data.entries
    } else {
      ifdNo.value = ''
      slsName.value = ''
      sanctionDetails.value = []
    }
  } catch (error) {
    console.error('Error fetching mother sanction details:', error)
  }
}

watch(selectedState, (newState) => {
  selectedMotherSanction.value = ''
  fetchMotherSanctions(newState)
  sanctionDetails.value = []
  ifdNo.value = ''
  slsName.value = ''
})

watch(selectedMotherSanction, (newKyMsNo) => {
  if (newKyMsNo) {
    fetchMotherSanctionDetails(newKyMsNo)
  }
})

const submitForm = async () => {
  const payload = {
    financial_year: financialYear.value,
    state_id: selectedState.value,
    ds_date: dsDate.value,
    mother_sanction: selectedMotherSanction.value,
    ifd_no: ifdNo.value,
    sls_name: slsName.value,
    entries: sanctionDetails.value.map(entry => ({
      budget_head: entry.budget_head,
      mother_sanction_amount: entry.mother_sanction_amount,
      available_amount: entry.available_fund,
      center_share_amount: entry.center_share_amount || 0,
    }))
  }

  router.post(route('addDailySanction'), payload, {
    onSuccess: () => {
      alert('Submitted successfully!')
      resetForm()
    },
    onError: (errors) => {
      console.error('Validation failed:', errors)
      alert('Validation failed. Check input and try again.')
    }
  })
}

</script>
