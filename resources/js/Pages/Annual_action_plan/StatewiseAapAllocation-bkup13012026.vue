<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
        <div class="container">
          <div class="page-inner allinsideform">
            <div class="page-header">
              <h3 class="fw-bold mb-3">Annual Action Plan Module</h3>
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
                  <a href="#">Statewise AAP Allocation</a>
                </li>
              </ul>
            </div>
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
					<div class="card-header">
						<div class="card-title d-flex justify-content-between align-items-center">
							<span>Statewise AAP Allocation for FY 2025-26 (₹ In Lakhs)</span>
							<button 
								class="btn btn-outline-info btn-sm d-flex align-items-center" 
								@click="viewHistory"
								title="View Allocation History"
							>
								<i class="fas fa-history"></i> &nbsp;History
							</button>
						</div>
					</div>

					<div class="card-body">
						<div v-if="loading" class="text-center">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading...</span>
							</div>
						</div>

						<div v-else-if="error" class="alert alert-danger">
							{{ error }}
						</div>

						<div v-else>
							<div class="table-responsive">
								<table class="table table-bordered table-hover align-middle text-center">
									<thead class="table-dark">
										<tr>
											<th rowspan="2" class="align-middle fw-sticky">State</th>
											<th v-for="pd in programDivisions" :key="pd.division_id" colspan="1">
												{{ pd.division_name }}
											</th>
											<th rowspan="2" class="align-middle">Final Allocation <br/><small class="text-capitalize">(₹ In Lakhs)</small></th>
											<th rowspan="2" class="align-middle">Remarks</th>
										</tr>
										<tr>
                      <!-- add class="fw-sticky" to the first th of the second row -->
											<th v-for="pd in programDivisions" :key="pd.division_id" >
												Final Allocation
											</th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="state in states" :key="state.state_id">
											<td class="fw-bold fw-sticky">{{ state.state_name }}</td>
											<td v-for="pd in programDivisions" :key="pd.division_id">
												<input 
													type="number" 
													class="form-control tableform-control-withoutbg" 
													v-model="allocationData[state.state_id][pd.division_id]"
													@blur="formatInputValue(state.state_id, pd.division_id)"
													placeholder="0.00000"
													step="0.00001"
													min="0"
												>
											</td>
											<!-- <td class="fw-bold text-center row-total"> -->
											<td class="fw-bold text-center bg-success-subtle">
												{{ calculateRowTotal(state.state_id) }}
											</td>
											<td>
												<input 
													type="text" 
													class="form-control tableform-control-withoutbg" 
													v-model="remarksData[state.state_id]"
													placeholder="Remark"
												>
											</td>
										</tr>
										
										<!-- Total Row -->
										<tr class="table-warning fw-bold">
											<td class="fw-sticky">Total</td>
											<!-- <td v-for="pd in programDivisions" :key="pd.division_id" class="total-cell"> -->
											<td v-for="pd in programDivisions" :key="pd.division_id" >
												{{ calculateColumnTotal(pd.division_id) }}
											</td>
											<td class="fw-bold text-center grand-total">
												{{ calculateGrandTotal() }}
											</td>
											<td>-</td>
										</tr>
									</tbody>
								</table>
							</div>

							<!-- Submit Button -->
							<div class="row mt-4">
								<div class="col-12 text-center">
									<button 
										@click="submitAllocation" 
										class="btn btn-primary btn-lg"
										:disabled="submitting"
									>
										<span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
										{{ submitting ? 'Saving...' : 'Submit Allocation' }}
									</button>
								</div>
							</div>
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

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios';
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

// Reactive data
const states = ref([])
const programDivisions = ref([])
const allocationData = ref({})
const remarksData = ref({})
const loading = ref(true)
const error = ref(null)
const submitting = ref(false)

// Fetch states from API
const fetchStates = async () => {
  try {
    const response = await fetch('/api/aap-states')
    if (!response.ok) throw new Error('Failed to fetch states')
    const data = await response.json()
    states.value = data
    console.log('States fetched successfully:', data)
  } catch (err) {
    console.error('Error fetching states:', err)
    error.value = 'Failed to load states'
  }
}

// Fetch program divisions from API
const fetchProgramDivisions = async () => {
  try {
    const response = await fetch('/api/aap-program-divisions')
    if (!response.ok) throw new Error('Failed to fetch program divisions')
    const data = await response.json()
    programDivisions.value = data
    console.log('Program divisions fetched successfully:', data)
  } catch (err) {
    console.error('Error fetching program divisions:', err)
    error.value = 'Failed to load program divisions'
  }
}

// Fetch existing allocation data
const fetchExistingAllocations = async () => {
  try {
    const response = await fetch('/api/statewise-aap-allocation?financial_year=2025-26')
    if (!response.ok) throw new Error('Failed to fetch existing allocations')
    const result = await response.json()
    
    console.log('Existing allocations result:', result)
    
    if (result.success && result.data) {
      // Populate existing data
      Object.keys(result.data).forEach(stateId => {
        const stateAllocations = result.data[stateId]
        console.log(`Processing state ${stateId}:`, stateAllocations)
        
        Object.keys(stateAllocations).forEach(pdId => {
          const allocation = stateAllocations[pdId]
          console.log(`Processing PD ${pdId} for state ${stateId}:`, allocation)
          
          if (allocationData.value[stateId] && allocationData.value[stateId][pdId] !== undefined) {
            // Use exact amount as stored - preserve the exact value from DB without parsing to float first
            // This prevents rounding issues (e.g., 4740.97500 should not become 4740.98000)
            const amount = allocation.amount
            allocationData.value[stateId][pdId] = formatToFiveDecimals(amount)
            console.log(`Set amount for state ${stateId}, PD ${pdId}: ${formatToFiveDecimals(amount)} (original: ${amount})`)
          } else {
            console.log(`Data structure not ready for state ${stateId}, PD ${pdId}`)
          }
        })
      })
      
      // Populate remarks if they exist
      if (result.remarks) {
        console.log('Processing remarks:', result.remarks)
        Object.keys(result.remarks).forEach(stateId => {
          if (remarksData.value[stateId] !== undefined) {
            remarksData.value[stateId] = result.remarks[stateId]
            console.log(`Set remark for state ${stateId}: ${result.remarks[stateId]}`)
          }
        })
      }
    } else {
      console.log('No existing data found or API returned error')
    }
  } catch (err) {
    console.error('Error fetching existing allocations:', err)
    // Don't show error for existing data, just log it
  }
}

// Initialize allocation data structure
const initializeAllocationData = () => {
  console.log('Initializing allocation data structure...')
  console.log('States:', states.value)
  console.log('Program Divisions:', programDivisions.value)
  
  states.value.forEach(state => {
    allocationData.value[state.state_id] = {}
    remarksData.value[state.state_id] = ''
    
    programDivisions.value.forEach(pd => {
      allocationData.value[state.state_id][pd.division_id] = ''
    })
    
    console.log(`Initialized data structure for state ${state.state_id}:`, allocationData.value[state.state_id])
  })
  
  console.log('Final allocation data structure:', allocationData.value)
}

// Helper function to format number to exactly 5 decimal places without rounding
// This preserves the exact value from the database
const formatToFiveDecimals = (value) => {
  if (value === null || value === undefined || value === '') {
    return '0.00000'
  }
  
  // Convert to string first to preserve precision
  const valueStr = String(value)
  
  // If it's already a string with decimal, preserve it
  if (valueStr.includes('.')) {
    const parts = valueStr.split('.')
    const integerPart = parts[0]
    let decimalPart = parts[1] || ''
    
    // Pad or truncate to exactly 5 decimal places
    if (decimalPart.length > 5) {
      // Truncate, don't round
      decimalPart = decimalPart.substring(0, 5)
    } else {
      // Pad with zeros
      decimalPart = decimalPart.padEnd(5, '0')
    }
    
    return `${integerPart}.${decimalPart}`
  } else {
    // No decimal point, add .00000
    return `${valueStr}.00000`
  }
}

// Format input value to 5 decimal places when field loses focus
const formatInputValue = (stateId, pdId) => {
  const currentValue = allocationData.value[stateId][pdId]
  if (currentValue !== null && currentValue !== undefined && currentValue !== '') {
    const numValue = parseFloat(currentValue)
    if (!isNaN(numValue)) {
      allocationData.value[stateId][pdId] = formatToFiveDecimals(numValue)
    }
  }
}

// Helper function to add two numbers with 5 decimal precision
const addWithPrecision = (a, b) => {
  const numA = parseFloat(a) || 0
  const numB = parseFloat(b) || 0
  // Multiply by 100000, add, then divide to maintain precision
  return Math.round((numA * 100000) + (numB * 100000)) / 100000
}

// Calculate column total
const calculateColumnTotal = (pdId) => {
  let total = 0
  states.value.forEach(state => {
    const value = parseFloat(allocationData.value[state.state_id][pdId]) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Calculate row total for a specific state
const calculateRowTotal = (stateId) => {
  let total = 0
  programDivisions.value.forEach(pd => {
    const value = parseFloat(allocationData.value[stateId][pd.division_id]) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Calculate grand total (sum of all allocations)
const calculateGrandTotal = () => {
  let total = 0
  states.value.forEach(state => {
    programDivisions.value.forEach(pd => {
      const value = parseFloat(allocationData.value[state.state_id][pd.division_id]) || 0
      total = addWithPrecision(total, value)
    })
  })
  return formatToFiveDecimals(total)
}

// Watch for changes in allocation data to trigger reactive updates
watch(allocationData, () => {
  // This will trigger reactive updates when allocation data changes
}, { deep: true })

// Submit allocation data
const submitAllocation = async () => {
  submitting.value = true
  
  try {
    // Prepare data for submission
    const submissionData = []
    
    states.value.forEach(state => {
      programDivisions.value.forEach(pd => {
        const amount = allocationData.value[state.state_id][pd.division_id]
        // Allow zero values to be saved - check if amount is not null/undefined/empty string
        // but allow 0 as a valid value
        if (amount !== null && amount !== undefined && amount !== '') {
          // Parse and format to 5 decimals before submission to ensure exact precision
          const exactAmount = parseFloat(amount)
          // Check if it's a valid number (including 0)
          // This will save 0 when user explicitly enters 0
          if (!isNaN(exactAmount) && exactAmount >= 0) {
            submissionData.push({
              financial_year: '2025-26',
              state_id: state.state_id,
              pd_id: pd.division_id,
              amount: exactAmount, // Save exact amount as entered (including 0, will be stored with 5 decimal precision in DB)
              status: 1
            })
          }
        }
      })
    })

	// console.log("========================submissionData=======================");
	// console.log(submissionData);
	// return false;

    if (submissionData.length === 0) {
      alert('Please enter at least one allocation amount (including 0)')
      submitting.value = false
      return
    }

    // Submit to backend
    const response = await fetch('/api/statewise-aap-allocation', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        allocations: submissionData,
        remarks: remarksData.value
      })
    })

    if (!response.ok) {
      throw new Error('Failed to save allocation data')
    }

    const result = await response.json()
    
    // Show success message without interrupting the form
    const successMessage = document.createElement('div')
    successMessage.className = 'alert alert-success alert-dismissible fade show position-fixed'
    successMessage.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;'
    successMessage.innerHTML = `
      <strong>Success!</strong> Allocation data saved successfully.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `
    document.body.appendChild(successMessage)
    
    // Auto-remove success message after 5 seconds
    setTimeout(() => {
      if (successMessage.parentNode) {
        successMessage.remove()
      }
    }, 5000)
    
    // Don't reset form - keep data intact for user to see
    // initializeAllocationData()
    
    // Refresh existing data from database to show the most current data
    await fetchExistingAllocations()
    
  } catch (err) {
    console.error('Error submitting allocation:', err)
    alert('Failed to save allocation data: ' + err.message)
  } finally {
    submitting.value = false
  }
}

// Navigate to history page in new tab
const viewHistory = () => {
  window.open('/statewise-aap-allocation-history', '_blank')
}

// Load data on component mount
onMounted(async () => {
  try {
    console.log('Component mounted, starting to load data...')
    
    console.log('Fetching states and program divisions...')
    await Promise.all([fetchStates(), fetchProgramDivisions()])
    
    console.log('Data fetched, initializing allocation data...')
    console.log('States:', states.value)
    console.log('Program divisions:', programDivisions.value)
    
    initializeAllocationData()
    
    console.log('Allocation data initialized, fetching existing allocations...')
    await fetchExistingAllocations()
    
    console.log('Component initialization completed')
  } catch (err) {
    console.error('Error initializing component:', err)
    error.value = 'Failed to initialize component'
  } finally {
    loading.value = false
    console.log('Component loading completed')
  }
})
</script>

<style scoped>
.tableform-control-withoutbg {
  background: transparent;
  border: 1px solid #dee2e6;
  text-align: center;
}

.tableform-control-withoutbg:focus {
  background: white;
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.table th {
  vertical-align: middle;
  white-space: nowrap;
}

.table td {
  vertical-align: middle;
}

.btn-lg {
  padding: 12px 30px;
  font-size: 18px;
}

/* Style for total cells */
.total-cell {
  background-color: #f8f9fa;
  font-weight: bold;
  color: #495057;
}

/* Style for row totals */
.row-total {
  background-color: #e9ecef;
  font-weight: bold;
  color: #495057;
}

/* Style for grand total */
.grand-total {
  background-color: #fff3cd;
  font-weight: bold;
  color: #856404;
}

/* to make first column sticky of the table */
.fw-sticky {
    position: sticky;
    left: 0;
    /* background-color: #f2f2f2; */
    z-index: 1;
}

</style>