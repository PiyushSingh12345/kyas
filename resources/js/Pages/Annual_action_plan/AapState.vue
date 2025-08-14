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
                  <a href="#">State Release Data (AAP)</a>
                </li>
              </ul>

            </div>
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
					<div class="card-header">
						<div class="card-title">State Release Data (AAP)</div>
						<!-- <h5 class="card-title">Krishonnati Yojana - State Release Data FY 2025-26 (₹ in Lakhs)</h5> -->
					</div>

					<div class="card-body">

						<div class="row mb-3 me-2">
							<div class="col-md-6">
								<label for="state" class="form-label">Select State</label>
								<div class="input-group">
									<select v-model="selectedState" @change="onStateChange" class="form-select" id="state" :disabled="isLoading">
										<option value="">Select State</option>
										<option v-for="state in states" :key="state.state_id" :value="state.state_id">
											{{ state.state_name }}
											</option>
										</select>
									<span v-if="isLoading" class="input-group-text">
										<div class="spinner-border spinner-border-sm text-primary" role="status">
											<span class="visually-hidden">Loading...</span>
										</div>
									</span>
								</div>
								<!-- Loading Status Text -->
								<div v-if="isLoading" class="form-text text-primary">
									<i class="fas fa-sync-alt fa-spin me-1"></i>
									Processing state change...
								</div>
							</div>
						</div>

						<!-- Budget Heads Display Section -->
						<!-- <div v-if="selectedState && budgetHeadsArray.length > 0" class="row mb-3 me-2">
							<div class="col-12">
								<div class="card">
									<div class="card-header">
										<div class="card-title">
											Budget Heads for State (Four Digits: {{ stateBudgetheadFourdigits }}) - Total: {{ budgetHeadsCount }}
										</div>
									</div>
									<div class="card-body">
										
										<div class="row mb-3">
											<div class="col-12">
												<div class="alert alert-info">
													<strong>Table Structure:</strong> 
													Fixed Columns: 5 (Scheme Component, SLS Details, Total Allocation, State Share, Center Share) | 
													Dynamic Columns: {{ budgetHeadsArray.length }} (Budget Heads) | 
													Total Columns: {{ tableColumnCount }}
												</div>
											</div>
										</div>
										
										
										<div class="row mb-3">
											<div class="col-12">
												<h6>Table Column Headers Array:</h6>
												<pre class="bg-light p-2 rounded" style="max-height: 150px; overflow-y: auto;">{{ JSON.stringify(tableColumnHeaders, null, 2) }}</pre>
											</div>
										</div>
										
										<div class="row">
											<div class="col-md-6">
												<h6>Budget Head Codes:</h6>
												<div class="list-group">
													<div v-for="budgetHead in budgetHeadsArray" :key="budgetHead.id" class="list-group-item d-flex justify-content-between align-items-center">
														<span class="badge bg-primary me-2">{{ budgetHead.code }}</span>
														<span>{{ budgetHead.description }}</span>
														<span class="badge bg-secondary">{{ budgetHead.category }}</span>
													</div>
												</div>
											</div>
											<div class="col-md-6">
												<h6>Budget Heads Array (JavaScript Format):</h6>
												<pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">{{ JSON.stringify(budgetHeadsArray, null, 2) }}</pre>
											</div>
										</div>
										
										
										<div class="row mt-3">
											<div class="col-md-3">
												<h6>Simple Array Format:</h6>
												<pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">{{ JSON.stringify(budgetHeadsSimpleArray, null, 2) }}</pre>
											</div>
											<div class="col-md-3">
												<h6>Budget Head Codes Only:</h6>
												<pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">{{ JSON.stringify(budgetHeadCodesArray, null, 2) }}</pre>
											</div>
											<div class="col-md-3">
												<h6>Budget Head Descriptions Only:</h6>
												<pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">{{ JSON.stringify(budgetHeadDescriptionsArray, null, 2) }}</pre>
											</div>
											<div class="col-md-3">
												<h6>Simple String Array:</h6>
												<pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">{{ JSON.stringify(budgetHeadsStringArray, null, 2) }}</pre>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div> -->

						<!-- Loading Spinner -->
						<div v-if="isLoading" class="row mb-3 me-2">
							<div class="col-12 text-center">
								<div class="card">
									<div class="card-body">
										<div class="d-flex justify-content-center align-items-center">
											<div class="spinner-border text-primary me-3" role="status">
												<span class="visually-hidden">Loading...</span>
											</div>
											<div>
												<h5 class="mb-1">Loading Data...</h5>
												<p class="text-muted mb-0">Fetching SLS components and budget heads for the selected state</p>
												
												<!-- Progress Steps -->
												<div class="mt-3">
													<div class="row text-center">
														<div class="col-4">
															<div class="d-flex flex-column align-items-center">
																<div class="bg-success rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 30px; height: 30px;">
																	<i class="fas fa-check text-white"></i>
																</div>
																<small class="text-muted">States Loaded</small>
															</div>
														</div>
														<div class="col-4">
															<div class="d-flex flex-column align-items-center">
																<div v-if="isLoadingSLS" class="spinner-border spinner-border-sm text-warning mb-2" role="status">
																	<span class="visually-hidden">Loading...</span>
																</div>
																<div v-else class="bg-success rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 30px; height: 30px;">
																	<i class="fas fa-check text-white"></i>
																</div>
																<small class="text-muted">SLS Components</small>
															</div>
														</div>
														<div class="col-4">
															<div class="d-flex flex-column align-items-center">
																<div v-if="isLoadingBudgetHeads" class="spinner-border spinner-border-sm text-info mb-2" role="status">
																	<span class="visually-hidden">Loading...</span>
																</div>
																<div v-else class="bg-success rounded-circle d-flex align-items-center justify-content-center mb-2" style="width: 30px; height: 30px;">
																	<i class="fas fa-check text-white"></i>
																</div>
																<small class="text-muted">Budget Heads</small>
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

						<div class="table-responsive">
							<!-- Table Loading State -->
							<div v-if="isLoading || !selectedState || budgetHeadsArray.length === 0" class="text-center py-5">
								<div v-if="isLoading" class="spinner-border text-primary mb-3" role="status">
									<span class="visually-hidden">Loading...</span>
								</div>
								<div v-else-if="!selectedState" class="text-muted">
									<h5>Please Select a State</h5>
									<p>Choose a state from the dropdown above to view the annual action plan data.</p>
								</div>
								<div v-else class="text-muted">
									<h5>No Budget Heads Found</h5>
									<p>No budget heads were found for the selected state.</p>
								</div>
							</div>
							
							<!-- Table Loading Overlay -->
							<div v-if="isLoading" class="position-relative">
								<div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.8); z-index: 10;">
									<div class="text-center">
										<div class="spinner-border text-primary mb-2" role="status">
											<span class="visually-hidden">Loading...</span>
										</div>
										<p class="text-muted mb-0">Rendering table with {{ budgetHeadsArray.length }} budget head columns...</p>
									</div>
								</div>
							</div>
							
							<!-- Actual Table -->
							<table v-else class="table table-bordered  table-hover align-middle text-center">
								<thead class="table-dark">
									<tr>
										<th>Scheme Component</th>
										<th>SLS Details</th>
										<th>Total Allocation <br>for FY 2025-26</th>
										<th>Allocation <br> for FY 2025-26 (State Share)</th>
										<th>Allocation <br>for FY 2025-26 (Center Share)</th>
										<!-- Dynamic columns based on budget heads -->
										<th v-for="budgetHead in budgetHeadsArray" :key="budgetHead.id">
											Annual Allocation <br> (Center Share) <small class="text-danger">{{ budgetHead.code }}</small>
										</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="(row, index) in tableData" :key="index">
										<td>{{ row.schemeComponent }}</td>
										<td>{{ row.slsDetails }}</td>
										<td><input type="text" v-model="row.amounts[0].amount" class="form-control tableform-control-withoutbg" placeholder="0.00"></td>
										<td><input type="text" v-model="row.amounts[1].amount" class="form-control tableform-control-withoutbg" placeholder="0.00"></td>
										<td><input type="text" v-model="row.amounts[2].amount" class="form-control tableform-control-withoutbg" placeholder="0.00"></td>
										<!-- Dynamic input fields based on budget heads -->
										<td v-for="(budgetHead, budgetIndex) in budgetHeadsArray" :key="budgetHead.id">
											<input type="text" 
												v-model="row.amounts[3 + budgetIndex].amount" 
												class="form-control tableform-control-withoutbg" 
												placeholder="0.00">
										</td>
									</tr>

									<tr class="table-warning fw-bold">
										<td>Total</td>
										<td></td>
										<td><input type="text" v-model="totalAmounts[0]" class="form-control tableform-control-withoutbg" readonly></td>
										<td><input type="text" v-model="totalAmounts[1]" class="form-control tableform-control-withoutbg" readonly></td>
										<td><input type="text" v-model="totalAmounts[2]" class="form-control tableform-control-withoutbg" readonly></td>
										<!-- Dynamic total fields based on budget heads -->
										<td v-for="(budgetHead, budgetIndex) in budgetHeadsArray" :key="budgetHead.id">
											<input type="text" 
												v-model="totalAmounts[3 + budgetIndex]" 
												class="form-control tableform-control-withoutbg" 
												readonly>
										</td>
									</tr>
								</tbody>
							</table>
						</div>

						<div class="row mt-3">
							<div class="col-12 text-center">
								<button type="button" @click="submitData" :disabled="!selectedState || isSubmitting || isLoading" class="btn btn-primary btn-lg me-3">
									<span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
									{{ isSubmitting ? 'Saving...' : 'Submit Data' }}
								</button>
								
								<!-- Debug Button -->
								<!-- <button type="button" @click="debugDataStructure" :disabled="!selectedState || tableData.length === 0" class="btn btn-info btn-lg me-3">
									<i class="fas fa-bug me-2"></i>
									Debug Data
								</button> -->

								<!-- Test Cell Independence Button -->
								<!-- <button type="button" @click="testCellIndependence" :disabled="!selectedState || tableData.length === 0" class="btn btn-warning btn-lg me-3">
									<i class="fas fa-flask me-2"></i>
									Test Cells
								</button> -->

								<!-- Clear Test Data Button -->
								<!-- <button type="button" @click="clearTestData" :disabled="!selectedState || tableData.length === 0" class="btn btn-secondary btn-lg">
									<i class="fas fa-undo me-2"></i>
									Clear Test Data
								</button> -->
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

import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

// Reactive data
const states = ref([])
const selectedState = ref('')
const slsComponents = ref([])
const tableData = ref([])
const isSubmitting = ref(false)
const budgetHeads = ref([])
const budgetHeadsArray = ref([])
const stateBudgetheadFourdigits = ref('')
const budgetHeadsCount = ref(0)
const isLoading = ref(false)
const isLoadingSLS = ref(false)
const isLoadingBudgetHeads = ref(false)

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
  }
}

// Fetch SLS components for selected state
const fetchSLSComponents = async (stateId) => {
  isLoadingSLS.value = true
  try {
    const response = await fetch(`/api/aap-sls-components?state_id=${stateId}`)
    if (!response.ok) throw new Error('Failed to fetch SLS components')
    const data = await response.json()
    if (data.success) {
      slsComponents.value = data.data
      populateTableData()
    }
  } catch (err) {
    console.error('Error fetching SLS components:', err)
  } finally {
    isLoadingSLS.value = false
  }
}

// Fetch budget heads based on state's budgethead_fourdigits
const fetchBudgetHeadsByState = async (stateId) => {
  isLoadingBudgetHeads.value = true
  try {
    const response = await fetch(`/api/aap-budget-heads-by-state?state_id=${stateId}`)
    if (!response.ok) throw new Error('Failed to fetch budget heads')
    const data = await response.json()
    if (data.success) {
      budgetHeads.value = data.data
      budgetHeadsArray.value = data.budget_heads_array
      stateBudgetheadFourdigits.value = data.state_budgethead_fourdigits
      budgetHeadsCount.value = data.count
      console.log('Budget heads fetched successfully:', data.data)
      console.log('Budget heads array:', data.budget_heads_array)
      console.log('State budgethead_fourdigits:', data.state_budgethead_fourdigits)
      console.log('Count:', data.count)
    }
  } catch (err) {
    console.error('Error fetching budget heads:', err)
    budgetHeads.value = []
    budgetHeadsArray.value = []
    stateBudgetheadFourdigits.value = ''
    budgetHeadsCount.value = 0
  } finally {
    isLoadingBudgetHeads.value = false
  }
}

// Populate table data with SLS components
const populateTableData = () => {
  tableData.value = slsComponents.value.map(component => {
    // Create a fresh amounts array for each row to ensure independence
    const rowAmounts = [
      // First 3 fixed columns - use actual IDs from state_release_generic
      { budget_head_id: 1, amount: '' }, // Total Allocation (ID 1)
      { budget_head_id: 2, amount: '' }, // State Share (ID 2)  
      { budget_head_id: 3, amount: '' }, // Center Share (ID 3)
      // Dynamic columns based on budget heads from budget_heads table
      ...budgetHeadsArray.value.map(budgetHead => ({
        budget_head_id: budgetHead.id,
        amount: ''
      }))
    ]

    return {
      sls_id: component.id,
      schemeComponent: component.component === 'PD' ? component.name : 'Agricultural Extension',
      slsDetails: component.component === 'SL' ? component.name : component.name,
      amounts: rowAmounts
    }
  })
}

// Handle state change
const onStateChange = () => {
  if (selectedState.value) {
    console.log(" selected state value")
    console.log(selectedState.value)
    
    // Set loading state
    isLoading.value = true
    
    // Clear previous data
    tableData.value = []
    budgetHeads.value = []
    budgetHeadsArray.value = []
    stateBudgetheadFourdigits.value = ''
    budgetHeadsCount.value = 0
    
    // Fetch data
    Promise.all([
      fetchSLSComponents(selectedState.value),
      fetchBudgetHeadsByState(selectedState.value)
    ]).finally(() => {
      // Stop loading when both requests complete
      isLoading.value = false
    })
  } else {
    tableData.value = []
    budgetHeads.value = []
    budgetHeadsArray.value = []
    stateBudgetheadFourdigits.value = ''
    budgetHeadsCount.value = 0
    isLoading.value = false
  }
}

// Watch for changes in budget heads array and log to console
watch(budgetHeadsArray, (newValue) => {
  if (newValue.length > 0) {
    console.log('=== Budget Heads Arrays Available ===')
    console.log('Full Array:', newValue)
    console.log('Simple Array:', budgetHeadsSimpleArray.value)
    console.log('Codes Only:', budgetHeadCodesArray.value)
    console.log('Descriptions Only:', budgetHeadDescriptionsArray.value)
    console.log('Count:', budgetHeadsCount.value)
    console.log('Four Digits:', stateBudgetheadFourdigits.value)
    console.log('Column Headers:', tableColumnHeaders.value)
    console.log('Total Columns:', tableColumnCount.value)
    console.log('=====================================')
    
    // Update table data when budget heads change
    if (slsComponents.value.length > 0) {
      populateTableData()
    }
  }
}, { deep: true })

// Calculate totals
const totalAmounts = computed(() => {
  // Calculate total columns: 3 fixed + dynamic budget heads
  const totalColumns = 3 + budgetHeadsArray.value.length
  const totals = new Array(totalColumns).fill(0)
  
  tableData.value.forEach(row => {
    row.amounts.forEach((amountData, index) => {
      const amount = parseFloat(amountData.amount) || 0
      totals[index] += amount
    })
  })
  
  return totals.map(total => total.toFixed(2))
})

// Get budget heads as simple array for JavaScript usage
const budgetHeadsSimpleArray = computed(() => {
  return budgetHeadsArray.value.map(item => ({
    id: item.id,
    code: item.code,
    description: item.description,
    category: item.category
  }))
})

// Get budget head codes only as array
const budgetHeadCodesArray = computed(() => {
  return budgetHeadsArray.value.map(item => item.code)
})

// Get budget head descriptions only as array
const budgetHeadDescriptionsArray = computed(() => {
  return budgetHeadsArray.value.map(item => item.description)
})

// Get budget heads as simple string array (most basic format)
const budgetHeadsStringArray = computed(() => {
  return budgetHeadsArray.value.map(item => `${item.code} - ${item.description}`)
})

// Get table column headers as array
const tableColumnHeaders = computed(() => {
  const fixedHeaders = [
    'Scheme Component',
    'SLS Details', 
    'Total Allocation for FY 2025-26',
    'Allocation for FY 2025-26 (State Share)',
    'Allocation for FY 2025-26 (Center Share)'
  ]
  
  const dynamicHeaders = budgetHeadsArray.value.map(budgetHead => 
    `Annual Allocation (Center Share) ${budgetHead.description}`
  )
  
  return [...fixedHeaders, ...dynamicHeaders]
})

// Get table column count
const tableColumnCount = computed(() => {
  return 5 + budgetHeadsArray.value.length
})

// Submit data
const submitData = async () => {
  if (!selectedState.value) {
    alert('Please select a state first')
    return
  }

  // Validate that at least some amounts are entered
  const hasData = tableData.value.some(row => 
    row.amounts.some(amountData => amountData.amount && amountData.amount.trim() !== '')
  )

  if (!hasData) {
    alert('Please enter at least one amount before submitting')
    return
  }

  isSubmitting.value = true

  try {
    // Prepare data for submission
    const submissionData = tableData.value.map(row => ({
      sls_id: row.sls_id
    }))

    // Prepare amounts array with proper structure for controller processing
    const amounts = []
    tableData.value.forEach((row, rowIndex) => {
      row.amounts.forEach((amountData, colIndex) => {
        // Only include amounts that have values
        if (amountData.amount && amountData.amount.trim() !== '') {
          amounts.push({
            budget_head_id: amountData.budget_head_id,
            amount: amountData.amount,
            row_index: rowIndex,
            col_index: colIndex
          })
        }
      })
    })

    const payload = {
      state_id: selectedState.value,
      fy: '2025-26',
      data: submissionData,
      amounts: amounts
    }

    console.log('=== SUBMISSION DATA DEBUG ===')
    console.log('Table Data:', tableData.value)
    console.log('Submission Data:', submissionData)
    console.log('Amounts:', amounts)
    console.log('Payload:', payload)
    console.log('=============================')

    const response = await fetch('/api/aap-state-release-data', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(payload)
    })

    const result = await response.json()

    console.log('=== RESPONSE DEBUG ===')
    console.log('Response:', result)
    console.log('=====================')

    if (result.success) {
      // Show success message
      showSuccessMessage(`Data saved successfully! ${result.savedCount} records updated.`)
      
      // Optionally refresh the data or clear the form
      // You can add logic here to refresh the table or clear inputs
    } else {
      showErrorMessage(`Error: ${result.message}`)
      if (result.errors) {
        console.error('Validation errors:', result.errors)
        showErrorMessage(`Validation errors: ${result.errors.join(', ')}`)
      }
    }
  } catch (error) {
    console.error('Error submitting data:', error)
    showErrorMessage('Error submitting data. Please try again.')
  } finally {
    isSubmitting.value = false
  }
}

// Debug data structure
const debugDataStructure = () => {
  if (!selectedState.value) {
    alert('Please select a state first to debug data.')
    return
  }

  if (tableData.value.length === 0) {
    alert('No data loaded for the selected state. Please select a state and load data.')
    return
  }

  console.log('=== DEBUGGING DATA STRUCTURE ===')
  console.log('Selected State:', selectedState.value)
  console.log('Budget Heads Array:', budgetHeadsArray.value)
  console.log('Budget Heads Count:', budgetHeadsCount.value)
  console.log('State Budgethead Four Digits:', stateBudgetheadFourdigits.value)
  console.log('SLS Components:', slsComponents.value)
  console.log('Table Data:', tableData.value)
  console.log('Total Amounts:', totalAmounts.value)
  
  // Show budget head mapping details
  console.log('=== BUDGET HEAD MAPPING ===')
  console.log('Column 0 (Total Allocation): budget_head_id = 1 (state_release_generic)')
  console.log('Column 1 (State Share): budget_head_id = 2 (state_release_generic)')
  console.log('Column 2 (Center Share): budget_head_id = 3 (state_release_generic)')
  console.log('Columns 3+ (Dynamic):', budgetHeadsArray.value.map((bh, index) => `Column ${3 + index}: budget_head_id = ${bh.id} (budget_heads)`))
  console.log('=================================')
  
  // Show sample data structure
  if (tableData.value.length > 0) {
    console.log('=== SAMPLE ROW DATA ===')
    const sampleRow = tableData.value[0]
    console.log('Sample Row:', sampleRow)
    console.log('Sample Amounts:', sampleRow.amounts)
    
    // Show budget head ID mapping for each column
    console.log('=== BUDGET HEAD ID MAPPING FOR SAMPLE ROW ===')
    sampleRow.amounts.forEach((amountData, index) => {
      if (index < 3) {
        console.log(`Column ${index}: budget_head_id = ${amountData.budget_head_id} (state_release_generic)`)
      } else {
        console.log(`Column ${index}: budget_head_id = ${amountData.budget_head_id} (budget_heads)`)
      }
    })
    console.log('=============================================')
  }

  alert('Data structure debugged. Check console for details.')
}

// Test function to populate different values in each cell
const testCellIndependence = () => {
  if (!selectedState.value || tableData.value.length === 0) {
    alert('Please select a state and load data first.')
    return
  }

  // Populate different test values in each cell to demonstrate independence
  tableData.value.forEach((row, rowIndex) => {
    row.amounts.forEach((amountData, colIndex) => {
      // Create unique test values for each cell
      const testValue = ((rowIndex + 1) * 100) + (colIndex + 1)
      amountData.amount = testValue.toString()
    })
  })

  console.log('=== TEST CELL INDEPENDENCE ===')
  console.log('Populated test values in all cells')
  console.log('Each cell now has a unique value')
  console.log('Table Data:', tableData.value)
  console.log('================================')

  showSuccessMessage('Test values populated! Each cell now has a different value.')
}

// Clear all test data and reset to empty values
const clearTestData = () => {
  if (!selectedState.value || tableData.value.length === 0) {
    alert('Please select a state and load data first.')
    return
  }

  // Clear all amounts in the table
  tableData.value.forEach(row => {
    row.amounts.forEach(amountData => {
      amountData.amount = ''
    })
  })

  console.log('=== CLEARED TEST DATA ===')
  console.log('All cells reset to empty values')
  console.log('Table Data:', tableData.value)
  console.log('==========================')

  showSuccessMessage('All test data cleared! Table is now empty and ready for new input.')
}

// Success message display
const showSuccessMessage = (message) => {
  // Create success alert
  const alertDiv = document.createElement('div')
  alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed'
  alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;'
  alertDiv.innerHTML = `
    <i class="fas fa-check-circle me-2"></i>
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `
  
  document.body.appendChild(alertDiv)
  
  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.remove()
    }
  }, 5000)
}

// Error message display
const showErrorMessage = (message) => {
  // Create error alert
  const alertDiv = document.createElement('div')
  alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed'
  alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;'
  alertDiv.innerHTML = `
    <i class="fas fa-exclamation-circle me-2"></i>
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `
  
  document.body.appendChild(alertDiv)
  
  // Auto-remove after 8 seconds
  setTimeout(() => {
    if (alertDiv.parentNode) {
      alertDiv.remove()
    }
  }, 8000)
}

onMounted(async () => {
    await fetchStates()
});
</script>