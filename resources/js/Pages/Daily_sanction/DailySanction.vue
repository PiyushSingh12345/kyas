<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <!-- Flash Message Container -->
          <div v-if="flashMessage.show" :class="`alert alert-${flashMessage.type} alert-dismissible fade show`" role="alert">
            <i :class="flashMessage.icon"></i>
            {{ flashMessage.message }}
            <button type="button" class="btn-close" @click="hideFlashMessage"></button>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Daily Sanction Module</div>
                </div>
                <div class="card-body">
                  <!-- PDF Upload Section (hidden) -->
                  <div v-if="false" class="row mb-4">
                    <div class="col-md-12">
                      <div class="card bg-light">
                        <div class="card-header">
                          <h6 class="card-title mb-0">
                            <i class="fas fa-file-pdf text-danger me-2"></i>
                            Upload Daily Sanction PDF
                          </h6>
                        </div>
                        <div class="card-body">
                          <div class="row">
                            <div class="col-md-8">
                              <div class="form-group">
                                <label for="pdfFile" class="form-label">Select PDF File</label>
                                <input 
                                  type="file" 
                                  class="form-control" 
                                  id="pdfFile" 
                                  accept=".pdf"
                                  @change="handleFileSelect"
                                  ref="fileInput"
                                >
                                <div class="form-text">Upload the daily sanction PDF file to automatically populate form fields</div>
                              </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                              <button 
                                type="button" 
                                class="btn btn-primary me-2" 
                                @click="processPdf"
                                :disabled="!selectedFile || isProcessing"
                              >
                                <i class="fas fa-upload me-1"></i>
                                <span v-if="isProcessing">Processing...</span>
                                <span v-else>Process PDF</span>
                              </button>
                              <button 
                                type="button" 
                                class="btn btn-secondary" 
                                @click="clearUpload"
                                :disabled="isProcessing"
                              >
                                <i class="fas fa-times me-1"></i>
                                Clear
                              </button>
                            </div>
                          </div>
                          <div v-if="selectedFile" class="mt-2">
                            <small class="text-muted">
                              <i class="fas fa-file-pdf text-danger me-1"></i>
                              Selected: {{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})
                            </small>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <!-- Financial Year -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="financialYear">F.Y</label>
                        <select v-model="financialYear" class="form-select" id="financialYear">
                          <option disabled value="">Select Financial Year</option>
                          <option value="2026-2027">2026–2027</option>
                          <option value="2025-2026">2025–2026</option>
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

                    <!-- Daily Sanction No -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="dailySanctionNo">Daily Sanction No</label>
                        <input type="text" class="form-control" id="dailySanctionNo" v-model="dailySanctionNo" placeholder="Enter Daily Sanction No">
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
                        <label for="slsId">SLS Name</label>
                        <input type="text" class="form-control" id="slsId" :value="slsName" disabled>
                      </div>
                    </div>

                    <!-- Remark -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="remark">Remark</label>
                        <!-- <input type="text" class="form-control" id="remark" v-model="remark" :value="remark" > -->
                        <input type="text" class="form-control" id="remark" v-model="remark" >
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
                          <th>Balanced Fund Available</th>
                          <!-- <th>Center Share Amount</th> -->
                          <th>Daily Sanction Amount(CS)</th>
                        </tr>
                      </thead>
                      <tbody>
						  <tr v-for="(row, index) in sanctionDetails" :key="index">
                <!-- <td>
                  <input type="text" class="form-control" :value="row.budget_head" disabled>
                </td> -->
						    <td>
						      <input type="text" class="form-control" :value="row.budget_head" disabled>
						    </td>
						    <td>
						      <input type="text" class="form-control" :value="row.mother_sanction_amount" disabled>
						    </td>
						    <td>
						      <input type="text" class="form-control" :value="getBalancedFundAvailable(row)" disabled>
						    </td>
						    <td>
						      <input 
						        type="number" 
						        class="form-control" 
						        :class="{ 'is-invalid': isAmountExceeded(row) }"
						        v-model="row.center_share_amount"
						        :max="getBalancedFundAvailableNumeric(row)"
						        step="0.00001"
						        min="0"
						        @input="validateAmount(row)"
						        @blur="validateAmount(row)"
						        placeholder="0.00000"
						      >
						      <div v-if="isAmountExceeded(row)" class="invalid-feedback">
						        Amount cannot exceed Balanced Fund Available ({{ getBalancedFundAvailable(row) }})
						      </div>
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
const dailySanctionNo = ref('')
const motherSanctions = ref([])
const selectedMotherSanction = ref('')

// Fetched details
const ifdNo = ref('')
const slsName = ref('')
const slsID = ref('')
const remark = ref('')
const sanctionDetails = ref([])
// Store balanced fund data by budget head: { total_ms_amount, total_daily_sanctioned }
const balancedFundData = ref({})

// PDF Upload state
const selectedFile = ref(null)
const isProcessing = ref(false)
const fileInput = ref(null)

// Flash message state
const flashMessage = ref({
  show: false,
  type: 'success',
  message: '',
  icon: ''
})

const showFlashMessage = (type, message, icon) => {
  flashMessage.value = {
    show: true,
    type,
    message,
    icon
  }
  
  // Auto-hide after 5 seconds
  setTimeout(() => {
    hideFlashMessage()
  }, 5000)
}

const hideFlashMessage = () => {
  flashMessage.value.show = false
}

const clearDetails = () => {
  ifdNo.value = ''
  slsName.value = ''
  slsID.value = ''
  remark.value = ''
  sanctionDetails.value = []
  balancedFundData.value = {}
}

const resetForm = () => {
  financialYear.value = ''
  selectedState.value = ''
  dsDate.value = ''
  dailySanctionNo.value = ''
  selectedMotherSanction.value = ''
  remark.value = ''
  clearDetails()
  clearUpload()
}

// PDF Upload Functions
const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file && file.type === 'application/pdf') {
    selectedFile.value = file
  } else {
    showFlashMessage('danger', 'Please select a valid PDF file', 'fas fa-exclamation-triangle')
    event.target.value = ''
  }
}

const clearUpload = () => {
  selectedFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const processPdf = async () => {
  if (!selectedFile.value) {
    showFlashMessage('danger', 'Please select a PDF file first', 'fas fa-exclamation-triangle')
    return
  }

  isProcessing.value = true

  try {
    const formData = new FormData()
    formData.append('pdf_file', selectedFile.value)

    const response = await fetch('/api/daily-sanction/process-pdf', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: formData
    })

    const result = await response.json()

    if (result.success) {
      // Populate form fields with extracted data
      populateFormFromPdf(result.data)
      
      showFlashMessage(
        'success', 
        'PDF processed successfully! Form fields have been populated.', 
        'fas fa-check-circle'
      )
    } else {
      showFlashMessage(
        'danger', 
        result.message || 'Failed to process PDF', 
        'fas fa-exclamation-triangle'
      )
    }
  } catch (error) {
    console.error('Error processing PDF:', error)
    showFlashMessage(
      'danger', 
      'An error occurred while processing the PDF. Please try again.', 
      'fas fa-exclamation-triangle'
    )
  } finally {
    isProcessing.value = false
  }
}

const populateFormFromPdf = async (data) => {
  // Populate basic form fields
  if (data.financial_year) {
    financialYear.value = data.financial_year
  }
  
  if (data.state_id) {
    selectedState.value = data.state_id
    // Fetch mother sanctions for the selected state
    fetchMotherSanctions(data.state_id)
  }
  
  if (data.ds_date) {
    dsDate.value = data.ds_date
  }
  
  if (data.daily_sanction_no) {
    dailySanctionNo.value = data.daily_sanction_no
  }
  
  if (data.mother_sanction) {
    selectedMotherSanction.value = data.mother_sanction
  }
  
  if (data.ifd_no) {
    ifdNo.value = data.ifd_no
  }
  
  if (data.sls_name) {
    slsName.value = data.sls_name
  }
  
  if (data.remark) {
    remark.value = data.remark
  }
  
  // Populate sanction details
  if (data.sanction_details && data.sanction_details.length > 0) {
    sanctionDetails.value = data.sanction_details.map(detail => ({
      budget_head: detail.budget_head || '',
      mother_sanction_amount: detail.mother_sanction_amount || 0,
      available_fund: detail.available_fund || 0,
      center_share_amount: detail.center_share_amount || 0
    }))
    
    // Fetch balanced fund available for all budget heads
    await fetchBalancedFundAvailable(data.sanction_details.map(d => d.budget_head).filter(Boolean))
  }
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
  if (!kyMsNo || !selectedState.value) {
    clearDetails()
    return
  }

  try {
    const params = new URLSearchParams({ state_id: String(selectedState.value) })
    const res = await fetch(`/api/mother-sanction-details/${encodeURIComponent(kyMsNo)}?${params.toString()}`)
    if (res.ok) {
      const data = await res.json()
      // console.log(data)
      ifdNo.value = data.meta.ifd_no
      slsName.value = data.meta.sls_name
      slsID.value = data.meta.sls_code
      sanctionDetails.value = data.entries
      
      // Fetch balanced fund available for all budget heads
      if (data.entries && data.entries.length > 0) {
        await fetchBalancedFundAvailable(data.entries.map(e => e.budget_head))
      }
    } else {
      ifdNo.value = ''
      slsName.value = ''
      slsID.value = ''
      sanctionDetails.value = []
      balancedFundData.value = {}
    }
  } catch (error) {
    console.error('Error fetching mother sanction details:', error)
  }
}

// Fetch balanced fund available for budget heads of the selected SLS:
// Net total MS (without carry forward) - total daily sanctions (same budget head, SLS, and state)
const fetchBalancedFundAvailable = async (budgetHeads) => {
  if (!budgetHeads || budgetHeads.length === 0) {
    balancedFundData.value = {}
    return
  }

  try {
    const params = new URLSearchParams({
      budget_heads: JSON.stringify(budgetHeads)
    })
    
    if (selectedState.value) {
      params.append('state_id', selectedState.value)
    }

    if (slsName.value) {
      params.append('sls_name', slsName.value)
    }

    const res = await fetch(`/api/daily-sanction-amounts-by-budget-head?${params.toString()}`)
    if (res.ok) {
      const data = await res.json()
      if (data.success && data.data) {
        balancedFundData.value = data.data
      } else {
        balancedFundData.value = {}
      }
    } else {
      balancedFundData.value = {}
    }
  } catch (error) {
    console.error('Error fetching balanced fund available:', error)
    balancedFundData.value = {}
  }
}

// Balanced Fund Available = net MS total (excl. carry forward) - daily sanctions for same budget head, SLS, and state
const getBalancedFundAvailable = (row) => {
  return getBalancedFundAvailableNumeric(row).toFixed(5)
}

// Get balanced fund available as numeric value for comparison
const getBalancedFundAvailableNumeric = (row) => {
  if (!row || !row.budget_head) return 0
  const fundData = balancedFundData.value[row.budget_head] || {}
  const totalMsAmount = parseFloat(fundData.total_ms_amount || 0)
  const alreadySanctioned = parseFloat(fundData.total_daily_sanctioned || 0)
  return Math.max(0, totalMsAmount - alreadySanctioned)
}

// Check if the entered amount exceeds balanced fund available
const isAmountExceeded = (row) => {
  if (!row.budget_head || !row.center_share_amount) return false
  const enteredAmount = parseFloat(row.center_share_amount) || 0
  const balancedFund = getBalancedFundAvailableNumeric(row)
  return enteredAmount > balancedFund
}

// Validate amount on input/blur and cap at maximum
const validateAmount = (row) => {
  if (!row.budget_head || !row.center_share_amount) return
  
  const enteredAmount = parseFloat(row.center_share_amount) || 0
  const balancedFund = getBalancedFundAvailableNumeric(row)
  
  if (enteredAmount > balancedFund) {
    // Cap the value at the maximum allowed
    row.center_share_amount = balancedFund.toFixed(5)
    
    showFlashMessage(
      'warning',
      `Daily Sanction Amount has been capped at Balanced Fund Available (${getBalancedFundAvailable(row)}) for Budget Head: ${row.budget_head}`,
      'fas fa-exclamation-triangle'
    )
  } else if (enteredAmount < 0) {
    // Prevent negative values
    row.center_share_amount = '0.00000'
  }
}

watch(selectedState, (newState) => {
  selectedMotherSanction.value = ''
  fetchMotherSanctions(newState)
  sanctionDetails.value = []
  ifdNo.value = ''
  slsName.value = ''
  slsID.value = ''
})

watch(selectedMotherSanction, (newKyMsNo) => {
  if (newKyMsNo && selectedState.value) {
    fetchMotherSanctionDetails(newKyMsNo)
  } else {
    clearDetails()
  }
})

const submitForm = async () => {
  // Validate all amounts before submission
  const validationErrors = []
  sanctionDetails.value.forEach((row, index) => {
    if (row.center_share_amount && isAmountExceeded(row)) {
      const balancedFund = getBalancedFundAvailable(row)
      validationErrors.push(
        `Row ${index + 1} (${row.budget_head}): Daily Sanction Amount exceeds Balanced Fund Available (${balancedFund})`
      )
    }
  })

  if (validationErrors.length > 0) {
    showFlashMessage(
      'danger',
      'Validation failed: ' + validationErrors.join('; '),
      'fas fa-exclamation-triangle'
    )
    return
  }

  const payload = {
    financial_year: sanitizeTextInput(financialYear.value),
    state_id: selectedState.value,
    ds_date: dsDate.value,
    daily_sanction_no: sanitizeTextInput(dailySanctionNo.value),
    mother_sanction: sanitizeTextInput(selectedMotherSanction.value),
    ifd_no: sanitizeTextInput(ifdNo.value),
    sls_name: sanitizeTextInput(slsName.value), // Use slsName instead of slsID
    remark: sanitizeTextInput(remark.value),
    entries: sanctionDetails.value.map(entry => ({
      budget_head: sanitizeTextInput(entry.budget_head),
      mother_sanction_amount: entry.mother_sanction_amount,
      available_amount: entry.available_fund,
      center_share_amount: entry.center_share_amount || 0,
    }))
  }

  try {
    // Debug: Log the payload being sent
    console.log('Daily Sanction Payload:', payload);
    
    const response = await fetch(route('addDailySanction'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(payload)
    })

    if (response.ok) {
      const result = await response.json()
      
      // Show success flash message
      showFlashMessage(
        'success', 
        'Daily sanction entries saved successfully', 
        'fas fa-check-circle'
      )
      
      resetForm()
    } else {
      // Get error details from response
      const errorData = await response.json().catch(() => ({}))
      console.error('Daily Sanction Error Response:', errorData)
      
      // Show error flash message with more details
      const errorMessage = errorData.message || 'Failed to save daily sanction entries. Please try again.'
      showFlashMessage(
        'danger', 
        errorMessage, 
        'fas fa-exclamation-triangle'
      )
    }
  } catch (error) {
    console.error('Error submitting form:', error)
    
    // Show error flash message
    showFlashMessage(
      'danger', 
      'An error occurred while submitting the form. Please try again.', 
      'fas fa-exclamation-triangle'
    )
  }
}

const sanitizeTextInput = (value) => {
  return String(value ?? '')
    .replace(/<[^>]*>/g, '')
    .replace(/[\u0000-\u001F\u007F]/g, '')
    .trim()
}

</script>
