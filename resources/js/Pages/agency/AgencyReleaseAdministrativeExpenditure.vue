<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Agency Release</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Administrative Expenditure</a>
              </li>
            </ul>
          </div>

          <!-- Flash Message - Positioned below heading -->
          <div v-if="flashMessage.show" :class="`alert alert-${flashMessage.type} alert-dismissible fade show mb-4`" role="alert">
            <i :class="flashMessage.icon"></i>
            {{ flashMessage.message }}
            <button type="button" class="btn-close" @click="hideFlashMessage" aria-label="Close"></button>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Administrative Expenditure</div>
                </div>

                <div class="card-body">
                  <form @submit.prevent="submitForm">
                    <div class="row">
                      <!-- Sanction Number -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="sanctionNumber">Sanction Number <span class="text-danger">*</span></label>
                          <input 
                            type="text" 
                            class="form-control" 
                            id="sanctionNumber" 
                            v-model="formData.sanctionNumber"
                            pattern="[A-Za-z0-9]+"
                            placeholder="Enter Sanction Number"
                            required
                          >
                        </div>
                      </div>

                      <!-- Date -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="date">Date <span class="text-danger">*</span></label>
                          <input 
                            type="date" 
                            class="form-control" 
                            id="date" 
                            v-model="formData.date"
                            required
                          >
                        </div>
                      </div>

                      <!-- Budget Head -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="budgetHead">Budget Head <span class="text-danger">*</span></label>
                          <select 
                            class="form-select" 
                            id="budgetHead" 
                            v-model="formData.budgetHead"
                            required
                          >
                            <option value="">--- Select Budget Head ---</option>
                            <!-- <option v-for="head in budgetHeads" :key="head.id" :value="head.budget">
                              {{ head.budget }} - {{ head.description }}
                            </option> -->

                            <option value="2435.60.103.04.00.09">2435.60.103.04.00.09</option>
                            <option value="2435.60.103.04.00.11">2435.60.103.04.00.11</option>
                            <option value="2435.60.103.04.00.12">2435.60.103.04.00.12</option>
                            <option value="2435.60.103.04.00.13">2435.60.103.04.00.13</option>
                            <option value="2435.60.103.04.00.16">2435.60.103.04.00.16</option>
                            <option value="2435.60.103.04.00.19">2435.60.103.04.00.19</option>
                            <option value="2435.60.103.04.00.26">2435.60.103.04.00.26</option>
                            <option value="2435.60.103.04.00.28">2435.60.103.04.00.28</option>
                            <option value="2435.60.103.04.00.29">2435.60.103.04.00.29</option>
                            <option value="2435.60.103.04.00.36">2435.60.103.04.00.36</option>
                            <option value="2435.60.103.04.00.49">2435.60.103.04.00.49</option>
                            <option value="2435.60.103.04.01.28">2435.60.103.04.01.28</option>
                            <option value="2435.60.103.04.96.13">2435.60.103.04.96.13</option>
                            <option value="2435.60.789.02.00.26">2435.60.789.02.00.26</option>
                            <option value="2435.60.796.02.00.26">2435.60.796.02.00.26</option>
                          </select>
                        </div>
                      </div>

                      <!-- Purpose Of Grant -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="purposeOfGrant">Purpose Of Grant <span class="text-danger">*</span></label>
                          <input 
                            type="text" 
                            class="form-control" 
                            id="purposeOfGrant" 
                            v-model="formData.purposeOfGrant"
                            pattern="[A-Za-z\s]+"
                            placeholder="Enter Purpose Of Grant"
                            required
                          >
                        </div>
                      </div>

                      <!-- Program Division -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="programDivision">Program Division <span class="text-danger">*</span></label>
                          <select 
                            class="form-select" 
                            id="programDivision" 
                            v-model="formData.programDivision"
                            required
                          >
                            <option value="">--- Select Program Division ---</option>
                            <option v-for="division in programDivisions" :key="division.division_id" :value="division.division_id">
                              {{ division.division_name }}
                            </option>
                          </select>
                        </div>
                      </div>

                      <!-- Balanced Fund Amount -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="balancedFundAmount">Balanced Fund Amount</label>
                          <input 
                            type="number" 
                            class="form-control" 
                            id="balancedFundAmount" 
                            v-model="balancedFundAmount"
                            step="0.01"
                            placeholder="Balanced Fund Amount"
                            disabled
                          >
                        </div>
                      </div>

                      <!-- Amount -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="amount">Amount <span class="text-danger">*</span></label>
                          <input 
                            type="number" 
                            class="form-control" 
                            :class="{ 'is-invalid': amountExceedsBalance }"
                            id="amount" 
                            v-model="formData.amount"
                            step="0.01"
                            min="0"
                            :max="balancedFundAmount > 0 ? balancedFundAmount : undefined"
                            placeholder="Enter Amount"
                            required
                          >
                          <div v-if="amountExceedsBalance" class="invalid-feedback">
                            Amount cannot exceed Balanced Fund Amount (₹{{ balancedFundAmount.toFixed(2) }} lakhs)
                          </div>
                        </div>
                      </div>

                      <!-- Agency/Vendor -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label for="agencyVendor">Agency/Vendor <span class="text-danger">*</span></label>
                          <input 
                            type="text" 
                            class="form-control" 
                            id="agencyVendor" 
                            v-model="formData.agencyVendor"
                            pattern="[A-Za-z0-9\s]+"
                            placeholder="Enter Agency/Vendor"
                            required
                          >
                        </div>
                      </div>

                      <!-- Remark -->
                      <div class="col-md-12">
                        <div class="form-group">
                          <label for="remark">Remark</label>
                          <textarea
                            class="form-control"
                            id="remark"
                            v-model="formData.remark"
                            placeholder="Enter Remark"
                            rows="3"
                          ></textarea>
                        </div>
                      </div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="card-footer">
                      <div class="form">
                        <div class="col-12 d-flex justify-content-center">
                          <button type="submit" class="btn btn-success me-1" :disabled="isSubmitting">
                            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            {{ isSubmitting ? 'Submitting...' : 'Submit' }}
                          </button>
                          <button type="button" class="btn btn-danger me-1" @click="resetForm" :disabled="isSubmitting">Reset</button>
                        </div>
                      </div>
                    </div>
                  </form>
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

<script setup>
import { ref, onMounted, nextTick, watch, computed } from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const formData = ref({
  sanctionNumber: '',
  date: '',
  budgetHead: '',
  purposeOfGrant: '',
  programDivision: '',
  amount: '',
  agencyVendor: '',
  remark: ''
})

const budgetHeads = ref([])
const programDivisions = ref([])
const balancedFundAmount = ref(0)
const isSubmitting = ref(false)

// Computed property to check if amount exceeds balanced fund amount
const amountExceedsBalance = computed(() => {
  const amount = parseFloat(formData.value.amount)
  return !isNaN(amount) && amount > 0 && balancedFundAmount.value > 0 && amount > balancedFundAmount.value
})

// Flash message state
const flashMessage = ref({
  show: false,
  type: 'success',
  message: '',
  icon: ''
})

const showFlashMessage = (type, message, icon) => {
  // First hide any existing message
  flashMessage.value.show = false
  
  // Use nextTick to ensure the DOM updates before showing new message
  nextTick(() => {
    flashMessage.value = {
      show: true,
      type,
      message,
      icon
    }
    
    // Scroll to top to ensure message is visible
    setTimeout(() => {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }, 100)
    
    // Auto-hide after 2 seconds
    setTimeout(() => {
      hideFlashMessage()
    }, 2000)
  })
}

const hideFlashMessage = () => {
  flashMessage.value.show = false
}

const fetchBudgetHeads = async () => {
  try {
    const response = await fetch('/api/budget-heads-by-major-head?major_head=2435')
    if (response.ok) {
      const data = await response.json()
      budgetHeads.value = data
      
      // Initialize select2 after data is loaded
      await nextTick()
      if (window.$ && window.$.fn && window.$.fn.select2) {
        window.$('#budgetHead').select2({
          theme: 'bootstrap',
          placeholder: '--- Select Budget Head ---',
          allowClear: true
        }).on('change', function() {
          formData.value.budgetHead = window.$(this).val()
        })
      }
    } else {
      console.error('Failed to fetch budget heads')
      showFlashMessage('danger', 'Failed to load budget heads', 'fas fa-exclamation-triangle')
    }
  } catch (error) {
    console.error('Error fetching budget heads:', error)
    showFlashMessage('danger', 'Error loading budget heads', 'fas fa-exclamation-triangle')
  }
}

const fetchProgramDivisions = async () => {
  try {
    const response = await fetch('/api/aap-program-divisions')
    if (response.ok) {
      programDivisions.value = await response.json()
    } else {
      console.error('Failed to fetch program divisions')
    }
  } catch (error) {
    console.error('Error fetching program divisions:', error)
  }
}

const fetchBalancedFundAmount = async () => {
  const budgetHead = formData.value.budgetHead
  const programDivisionId = formData.value.programDivision
  
  if (!budgetHead) {
    balancedFundAmount.value = 0
    return
  }

  try {
    // Build URL with budget head and optionally program division
    let url = `/api/balanced-fund-amount-admin-exp?budget_head=${encodeURIComponent(budgetHead)}`
    
    // When both budget head and program division are selected, pass both
    if (programDivisionId) {
      url += `&program_division_id=${encodeURIComponent(programDivisionId)}`
    }
    
    const response = await fetch(url)
    if (response.ok) {
      const data = await response.json()
      // Calculate: Allocated Amount - Total Releases (from all 3 tables when PD is selected)
      const allocatedAmount = parseFloat(data.allocated_amount || 0)
      const totalReleases = parseFloat(data.total_releases || 0)
      balancedFundAmount.value = allocatedAmount - totalReleases
    } else {
      console.error('Failed to fetch balanced fund amount')
      balancedFundAmount.value = 0
    }
  } catch (error) {
    console.error('Error fetching balanced fund amount:', error)
    balancedFundAmount.value = 0
  }
}

// Watch for changes in budget head OR program division to update balanced fund amount
watch(() => [formData.value.budgetHead, formData.value.programDivision], () => {
  fetchBalancedFundAmount()
})

const resetForm = (hideMessage = true) => {
  formData.value = {
    sanctionNumber: '',
    date: '',
    budgetHead: '',
    purposeOfGrant: '',
    programDivision: '',
    amount: '',
    agencyVendor: '',
    remark: ''
  }
  
  // Reset balanced fund amount
  balancedFundAmount.value = 0
  
  // Reset select2
  if (window.$ && window.$('#budgetHead').data('select2')) {
    window.$('#budgetHead').val(null).trigger('change')
  }
  
  // Only hide message if explicitly requested (e.g., user clicks Reset button)
  if (hideMessage) {
    hideFlashMessage()
  }
}

const submitForm = async () => {
  // Validation
  if (!formData.value.sanctionNumber || !formData.value.date || !formData.value.budgetHead || 
      !formData.value.purposeOfGrant || !formData.value.programDivision || 
      !formData.value.amount || !formData.value.agencyVendor) {
    showFlashMessage('danger', 'Please fill in all required fields', 'fas fa-exclamation-triangle')
    return
  }

  // Check if amount exceeds balanced fund amount
  if (amountExceedsBalance.value) {
    showFlashMessage('danger', `Amount cannot exceed Balanced Fund Amount of ₹${balancedFundAmount.value.toFixed(2)} lakhs`, 'fas fa-exclamation-triangle')
    return
  }

  // Set submitting state
  isSubmitting.value = true

  try {
    // Prepare data with proper types
    const submitData = {
      sanctionNumber: formData.value.sanctionNumber,
      date: formData.value.date,
      budgetHead: formData.value.budgetHead,
      purposeOfGrant: formData.value.purposeOfGrant,
      programDivision: parseInt(formData.value.programDivision),
      amount: parseFloat(formData.value.amount),
      agencyVendor: formData.value.agencyVendor,
      remark: formData.value.remark
    }

    const response = await fetch('/api/agency-release-administrative-expenditure', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(submitData)
    })

    if (response.ok) {
      const result = await response.json()
      showFlashMessage('success', 'Administrative Expenditure data saved successfully!', 'fas fa-check-circle')
      resetForm(false) // Don't hide the success message when resetting after successful submission
    } else {
      const errorData = await response.json().catch(() => ({}))
      const errorMessage = errorData.message || 'Failed to save data. Please try again.'
      showFlashMessage('danger', errorMessage, 'fas fa-exclamation-triangle')
    }
  } catch (error) {
    console.error('Error submitting form:', error)
    showFlashMessage('danger', 'An error occurred while submitting the form. Please try again.', 'fas fa-exclamation-triangle')
  } finally {
    // Always reset submitting state
    isSubmitting.value = false
  }
}

// Function to prefill form from URL parameters
const prefillFormFromURL = async () => {
  const urlParams = new URLSearchParams(window.location.search);
  
  if (urlParams.get('revise') === 'true') {
    // Prefill form fields from URL parameters
    if (urlParams.get('sanctionNumber')) {
      formData.value.sanctionNumber = urlParams.get('sanctionNumber');
    }
    if (urlParams.get('date')) {
      formData.value.date = urlParams.get('date');
    }
    if (urlParams.get('budgetHead')) {
      formData.value.budgetHead = urlParams.get('budgetHead');
      // Set select2 value after it's initialized
      await nextTick();
      if (window.$ && window.$('#budgetHead').data('select2')) {
        window.$('#budgetHead').val(formData.value.budgetHead).trigger('change');
      }
    }
    if (urlParams.get('purposeOfGrant')) {
      formData.value.purposeOfGrant = urlParams.get('purposeOfGrant');
    }
    if (urlParams.get('programDivision')) {
      formData.value.programDivision = urlParams.get('programDivision');
    }
    if (urlParams.get('amount')) {
      formData.value.amount = urlParams.get('amount');
    }
    if (urlParams.get('agencyVendor')) {
      formData.value.agencyVendor = urlParams.get('agencyVendor');
    }
    if (urlParams.get('remark')) {
      formData.value.remark = urlParams.get('remark');
    }
  }
}

onMounted(async () => {
  await fetchBudgetHeads()
  await fetchProgramDivisions()
  await prefillFormFromURL()
})
</script>

<style scoped>
.alert {
  border-radius: 8px;
  border: none;
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
  margin-bottom: 20px;
  font-weight: 600;
  font-size: 1rem;
  padding: 1rem 1.5rem;
  position: relative;
  z-index: 100;
}

.alert-success {
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  color: #155724;
  border-left: 5px solid #28a745;
}

.alert-danger {
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
  color: #721c24;
  border-left: 5px solid #dc3545;
}

.alert i {
  margin-right: 10px;
  font-size: 1.2em;
}

.btn-close {
  opacity: 0.7;
  transition: opacity 0.2s;
}

.btn-close:hover {
  opacity: 1;
}

.text-danger {
  color: #dc3545;
}

.is-invalid {
  border-color: #dc3545 !important;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right calc(0.375em + 0.1875rem) center;
  background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
  padding-right: calc(1.5em + 0.75rem);
}

.invalid-feedback {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #dc3545;
  font-weight: 500;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.2em;
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.alert {
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

