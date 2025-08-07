<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Master Data</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#"><i class="icon-home"></i></a>
              </li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item">
                <a href="#">Budget Phase</a>
              </li>
            </ul>
          </div>

          <!-- Success/Error Messages -->
          <div v-if="message" :class="`alert alert-${messageType} alert-dismissible fade show`" role="alert">
            {{ message }}
            <button type="button" class="btn-close" @click="clearMessage"></button>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Budget Phase</div>
                </div>

                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label for="financialYear">F.Y</label>
                        <select class="form-select" id="financialYear" v-model="financialYear">
                          <option value="2025-26">2025-26</option>
                          <option value="2024-25">2024-25</option>
                          <!-- Add more years if needed -->
                        </select>
                      </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label for="budgetPhase">Budget Phase</label>
                        <select class="form-select" id="budgetPhase" v-model="selectedPhase" @change="fetchBudgetHeads">
                          <option disabled value="0">Select Budget Phase</option>
                          <option value="BE">BE</option>
                          <option value="RE">RE</option>
                          <option value="FE">FE</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- No data message -->
                  <div v-if="selectedPhase !== '0' && filteredBudgetHeads.length === 0" class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>No budget data found</strong> for the selected Financial Year ({{ financialYear }}) and Budget Phase ({{ selectedPhase }}).
                    <br>Please ensure that budget heads are available for this combination.
                  </div>

                  <div v-if="filteredBudgetHeads.length !== 0" class="table-responsive mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 float-end">
                      <!-- <span class="rscss">(Rs In Lakhs)</span>
                      <div class="alert alert-info py-2 px-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Total Budget Amount:</strong> 
                        <span class="fw-bold text-primary">{{ totalBudgetAmount.toLocaleString() }}</span> Lakhs
                        <span class="text-muted ms-2">({{ filteredBudgetHeads.length }} budget heads)</span>
                      </div> -->
                      <!-- <span class="rscss">(Rs In Lakhs)</span> -->
                      <div class="alert alert-info py-2 px-3 mb-0">
                        <!-- <i class="fas fa-info-circle me-2"></i> -->
                        <strong>(Rs In Lakhs)</strong> 
                        <!-- <span class="fw-bold text-primary">{{ totalBudgetAmount.toLocaleString() }}</span> Lakhs -->
                        <!-- <span class="text-muted ms-2">({{ filteredBudgetHeads.length }} budget heads)</span> -->
                      </div>
                    </div>
                    
                    <!-- Budget Summary Card -->
                    <div class="row mb-3">
                      <div class="col-md-4">
                        <div class="card bg-primary text-white">
                          <div class="card-body text-center">
                            <h5 class="card-title">Total Budget</h5>
                            <h3 class="mb-0">₹ {{ formattedTotalBudget }} Lakhs</h3>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="card bg-success text-white">
                          <div class="card-body text-center">
                            <h5 class="card-title">Allocated Amount</h5>
                            <h3 class="mb-0">₹ {{ formattedAllocatedAmount }} Lakhs</h3>
                          </div>
                        </div>
                      </div>
                    </div>
                    <table class="table table-bordered table-head-bg-primary">
                      <thead>
                        <tr>
                          <th>Budget Head</th>
                          <th>Head Description</th>
                          <th>Budget Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in filteredBudgetHeads" :key="item.id">
                          <td>
                            <input
                              type="text"
                              v-model="item.budget"
                              class="form-control tableform-control-withoutbg"
                              disabled
                            />
                          </td>
                          <td>
                            <input
                              type="text"
                              v-model="item.description"
                              class="form-control tableform-control-withoutbg"
                              disabled
                            />
                          </td>
                          <td>
                            <div class="d-flex align-items-center">
                              <input
                                v-if="item.draft_flag === 0"
                                type="number"
                                v-model="item.amount"
                                class="form-control tableform-control-withoutbg"
                                placeholder="Enter amount"
                              />
                              <input
                                v-else
                                type="number"
                                :value="item.amount"
                                class="form-control tableform-control-withoutbg fw-bold text-success"
                                readonly
                              />
                              <!-- <span v-if="item.amount && item.amount > 0" class="ms-2 badge bg-success">
                                <i class="fas fa-check"></i>
                              </span>
                              <span v-else class="ms-2 badge bg-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                              </span> -->
                            </div>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot class="table-dark">
                        <tr>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div> <!-- end of card-body -->

                <div class="card-footer" v-if="filteredBudgetHeads.length !== 0">
                  <div class="form">
                    <div class="col-12 d-flex justify-content-center">
                      <button 
                        class="btn btn-primary me-1" 
                        @click="saveAsDraft" 
                        :disabled="isSubmitted || isProcessing"
                        :class="{ 'opacity-50': isProcessing }"
                      >
                        <span v-if="isProcessing" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Save as Draft
                      </button>
                      <button 
                        class="btn btn-success me-1" 
                        @click="submit" 
                        :disabled="isSubmitted || isProcessing"
                        :class="{ 'opacity-50': isProcessing }"
                      >
                        <span v-if="isProcessing" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Submit
                      </button>
                      <button 
                        class="btn btn-danger me-1" 
                        @click="reset" 
                        :disabled="isSubmitted || isProcessing"
                      >
                        Reset
                      </button>
                    </div>
                  </div>
                </div> <!-- end of card-footer -->

              </div> <!-- end of card -->
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </div>
  </div>
</template>

<script>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { ref, reactive, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export default {
  name: 'BudgetPhase',
  components: {
    Header,
    Sidebar,
    Footer
  },
  setup() {
    const selectedPhase = ref('0')
    const financialYear = ref('2025-26')
    const filteredBudgetHeads = ref([])
    const isSubmitted = ref(false)
    const isProcessing = ref(false)
    const message = ref('')
    const messageType = ref('success')

    const clearMessage = () => {
      message.value = ''
      messageType.value = 'success'
    }

    const showMessage = (msg, type = 'success') => {
      message.value = msg
      messageType.value = type
      setTimeout(() => {
        clearMessage()
      }, 5000)
    }

    const fetchBudgetHeads = async () => {
      if (selectedPhase.value === '0') {
        filteredBudgetHeads.value = []
        return
      }

      try {
        const response = await fetch(`/api/budget-heads?phase=${selectedPhase.value}&year=${financialYear.value}`)
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        filteredBudgetHeads.value = data

        // If ALL draft_flag === 1, disable buttons
        isSubmitted.value = data.every(item => item.draft_flag === 1)
      } catch (error) {
        console.error('Error fetching budget heads:', error)
        showMessage('Error fetching budget heads. Please try again.', 'danger')
      }
    }

    const saveAsDraft = async () => {
      if (isProcessing.value) return
      
      isProcessing.value = true
      
      try {
        const allocations = filteredBudgetHeads.value
          .filter(item => item.draft_flag === 0 && item.amount !== null && item.amount !== '' && item.amount !== undefined)
          .map(item => ({
            financial_year: financialYear.value,
            budget_phase: selectedPhase.value,
            budget_head_id: item.id,
            budget_amount: item.amount,
            status: 1,
            draft_flag: 0
          }))

        if (allocations.length === 0) {
          showMessage('Please enter at least one budget amount before saving.', 'warning')
          isProcessing.value = false
          return
        }

        const response = await fetch('/budget-phase', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ allocations })
        })

        const result = await response.json()

        if (response.ok) {
          showMessage('Draft saved successfully!', 'success')
          isSubmitted.value = true
          // Refresh the data
          await fetchBudgetHeads()
        } else {
          throw new Error(result.message || 'Failed to save draft')
        }
      } catch (error) {
        console.error("Error saving draft:", error)
        showMessage(error.message || 'Error saving draft. Please try again.', 'danger')
      } finally {
        isProcessing.value = false
      }
    }

    const submit = async () => {
      if (isProcessing.value) return
      
      // Show confirmation dialog
      if (!confirm('Are you sure you want to submit? This action cannot be undone.')) {
        return
      }

      isProcessing.value = true
      
      try {
        const allocations = filteredBudgetHeads.value
          .filter(item => item.draft_flag === 0 && item.amount !== null && item.amount !== '' && item.amount !== undefined)
          .map(item => ({
            financial_year: financialYear.value,
            budget_phase: selectedPhase.value,
            budget_head_id: item.id,
            budget_amount: item.amount,
            status: 1,
            draft_flag: 1
          }))

        if (allocations.length === 0) {
          showMessage('Please enter at least one budget amount before submitting.', 'warning')
          isProcessing.value = false
          return
        }

        const response = await fetch('/budget-phase', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ allocations })
        })

        const result = await response.json()

        if (response.ok) {
          showMessage('Budget submitted successfully!', 'success')
          isSubmitted.value = true
          // Refresh the data
          await fetchBudgetHeads()
        } else {
          throw new Error(result.message || 'Failed to submit budget')
        }
      } catch (error) {
        console.error("Error submitting budget:", error)
        showMessage(error.message || 'Error submitting budget. Please try again.', 'danger')
      } finally {
        isProcessing.value = false
      }
    }

    const reset = () => {
      filteredBudgetHeads.value.forEach(item => {
        if (item.draft_flag === 0) {
          item.amount = null
        }
      })
      showMessage('Form has been reset.', 'info')
    }

    // Computed property for total budget amount
    const totalBudgetAmount = computed(() => {
      const total = filteredBudgetHeads.value.reduce((total, item) => {
        return total + (parseFloat(item.amount) || 0)
      }, 0)
      return total
    })

    // Computed property for allocated amount (amounts that are not null/empty)
    const allocatedAmount = computed(() => {
      const total = filteredBudgetHeads.value.reduce((total, item) => {
        return total + (parseFloat(item.amount) || 0)
      }, 0)
      return total
    })

    // Computed property for remaining amount (budget heads with no amount)
    const remainingAmount = computed(() => {
      const budgetHeadsWithAmount = filteredBudgetHeads.value.filter(item => 
        item.amount !== null && item.amount !== '' && item.amount !== undefined
      ).length
      const totalBudgetHeads = filteredBudgetHeads.value.length
      return totalBudgetHeads - budgetHeadsWithAmount
    })

    // Function to format numbers in Indian numbering format
    const formatIndianNumber = (num) => {
      if (num === null || num === undefined || num === '') return '0'
      
      const number = parseFloat(num)
      if (isNaN(number)) return '0'
      
      // Convert to string and split by decimal point
      const parts = number.toString().split('.')
      const integerPart = parts[0]
      const decimalPart = parts[1] || ''
      
      // Format integer part with Indian numbering
      let formattedInteger = ''
      const length = integerPart.length
      
      // Indian numbering: last 3 digits, then groups of 2
      if (length <= 3) {
        formattedInteger = integerPart
      } else {
        // Get the last 3 digits
        const lastThree = integerPart.slice(-3)
        // Get the remaining digits
        const remaining = integerPart.slice(0, -3)
        
        // Format remaining digits in groups of 2 from right to left
        let formattedRemaining = ''
        for (let i = remaining.length - 1; i >= 0; i -= 2) {
          const start = Math.max(0, i - 1)
          const group = remaining.slice(start, i + 1)
          formattedRemaining = group + (formattedRemaining ? ',' + formattedRemaining : '')
        }
        
        formattedInteger = formattedRemaining + ',' + lastThree
      }
      
      // Add decimal part if exists
      if (decimalPart) {
        return formattedInteger + '.' + decimalPart
      }
      
      return formattedInteger
    }

    // Computed properties for formatted amounts
    const formattedTotalBudget = computed(() => {
      return formatIndianNumber(totalBudgetAmount.value)
    })

    const formattedAllocatedAmount = computed(() => {
      return formatIndianNumber(allocatedAmount.value)
    })

    return {
      selectedPhase,
      financialYear,
      filteredBudgetHeads,
      isSubmitted,
      isProcessing,
      message,
      messageType,
      totalBudgetAmount,
      allocatedAmount,
      remainingAmount,
      fetchBudgetHeads,
      saveAsDraft,
      submit,
      reset,
      clearMessage,
      formatIndianNumber,
      formattedTotalBudget,
      formattedAllocatedAmount
    }
  }
}
</script>
