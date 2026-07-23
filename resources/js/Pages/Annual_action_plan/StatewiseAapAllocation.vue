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
							<span>Statewise AAP Allocation for FY {{ selectedFinancialYear }} (₹ In Lakhs)</span>
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
							<!-- Filters Section -->
							<div class="row mb-4">
								<div class="col-12">
									<div class="card border-primary">
										<div class="card-header bg-primary text-white">
											<h6 class="mb-0">
												<i class="fas fa-filter me-2"></i>Filters
											</h6>
										</div>
										<div class="card-body">
											<div class="row g-3">
												<div class="col-md-2">
													<label for="financialYearSelect" class="form-label fw-bold">Financial Year</label>
													<select
														id="financialYearSelect"
														class="form-select"
														v-model="selectedFinancialYear"
														@change="onFinancialYearChange"
													>
														<option
															v-for="year in financialYearOptions"
															:key="year.value"
															:value="year.value"
														>
															{{ year.label }}
														</option>
													</select>
												</div>

												<!-- State Filter (Multiselect) -->
												<div class="col-md-2">
													<label class="form-label fw-bold">State</label>
													<div class="custom-multiselect-container" @click.stop>
														<div
															class="custom-multiselect-input form-control"
															:class="{ 'is-open': showStateDropdown }"
															@click="toggleStateDropdown"
														>
															<div class="selected-tags-wrapper">
																<span
																	v-for="stateId in selectedStates"
																	:key="stateId"
																	class="custom-tag"
																>
																	{{ getStateName(stateId) }}
																	<span
																		class="tag-remove"
																		@click.stop="removeState(stateId)"
																	>×</span>
																</span>
																<input
																	type="text"
																	class="tag-input"
																	v-model="stateSearchTerm"
																	:placeholder="selectedStates.length === 0 ? 'Select states...' : ''"
																	@focus="showStateDropdown = true"
																	@click.stop="showStateDropdown = true"
																/>
															</div>
															<div class="dropdown-arrows">
																<i class="fas fa-chevron-up" v-if="showStateDropdown"></i>
																<i class="fas fa-chevron-down" v-else></i>
															</div>
														</div>
														<div
															class="custom-dropdown-menu"
															v-show="showStateDropdown"
															@click.stop
														>
															<div
																v-for="state in filteredAvailableStates"
																:key="state.state_id"
																class="dropdown-item"
																@click="selectState(state.state_id)"
															>
																{{ state.state_name }}
															</div>
															<div v-if="filteredAvailableStates.length === 0" class="dropdown-item text-muted">
																No states available
															</div>
														</div>
													</div>
												</div>

												<!-- Program Division Filter (Multiselect) -->
												<div class="col-md-2">
													<label class="form-label fw-bold">Program Division</label>
													<div class="custom-multiselect-container" @click.stop>
														<div
															class="custom-multiselect-input form-control"
															:class="{ 'is-open': showPdDropdown }"
															@click="togglePdDropdown"
														>
															<div class="selected-tags-wrapper">
																<span
																	v-for="pdId in selectedProgramDivisions"
																	:key="pdId"
																	class="custom-tag"
																>
																	{{ getProgramDivisionName(pdId) }}
																	<span
																		class="tag-remove"
																		@click.stop="removeProgramDivision(pdId)"
																	>×</span>
																</span>
																<input
																	type="text"
																	class="tag-input"
																	v-model="pdSearchTerm"
																	:placeholder="selectedProgramDivisions.length === 0 ? 'Select PDs...' : ''"
																	@focus="showPdDropdown = true"
																	@click.stop="showPdDropdown = true"
																/>
															</div>
															<div class="dropdown-arrows">
																<i class="fas fa-chevron-up" v-if="showPdDropdown"></i>
																<i class="fas fa-chevron-down" v-else></i>
															</div>
														</div>
														<div
															class="custom-dropdown-menu"
															v-show="showPdDropdown"
															@click.stop
														>
															<div
																v-for="pd in filteredAvailableProgramDivisions"
																:key="pd.division_id"
																class="dropdown-item"
																@click="selectProgramDivision(pd.division_id)"
															>
																{{ pd.division_name }}
															</div>
															<div v-if="filteredAvailableProgramDivisions.length === 0" class="dropdown-item text-muted">
																No program divisions available
															</div>
														</div>
													</div>
												</div>

												<div class="col-md-2">
													<label for="tentativeAmountFilter" class="form-label fw-bold">Tentative Amount</label>
													<input
														id="tentativeAmountFilter"
														type="text"
														class="form-control"
														v-model="tentativeAmountFilter"
														placeholder="Search amount..."
													/>
												</div>

												<div class="col-md-2">
													<label for="finalAllocationFilter" class="form-label fw-bold">Final Allocation</label>
													<input
														id="finalAllocationFilter"
														type="text"
														class="form-control"
														v-model="finalAllocationFilter"
														placeholder="Search amount..."
													/>
												</div>

												<div class="col-md-2">
													<label class="form-label fw-bold">&nbsp;</label>
													<button
														class="btn btn-secondary btn-sm w-100"
														@click="clearFilters"
														title="Clear All Filters"
													>
														<i class="fas fa-times"></i> Clear
													</button>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div ref="reportTableScrollWrapper" class="report-table-scroll-wrapper" @scroll="onTableWrapperScroll">
							<div class="table-responsive">
								<table class="table table-bordered table-hover align-middle text-center">
									<thead class="table-dark">
										<tr>
											<th rowspan="2" class="align-middle fw-sticky">State</th>
											<th v-for="pd in filteredProgramDivisions" :key="pd.division_id" colspan="2">
												{{ pd.division_name }}
											</th>
											<th rowspan="2" class="align-middle">Tentative Allocation <br/><small class="text-capitalize">(₹ In Lakhs)</small></th>
											<th rowspan="2" class="align-middle">Final Allocation <br/><small class="text-capitalize">(₹ In Lakhs)</small></th>
											<th rowspan="2" class="align-middle">Remarks</th>
										</tr>
										<tr>
											<template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												<th>Tentative Amount</th>
												<th>Final Allocation</th>
											</template>
										</tr>
									</thead>
									<tbody>
										<tr v-for="state in filteredStates" :key="state.state_id">
											<td class="fw-bold fw-sticky">{{ state.state_name }}</td>
											<template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												<td>
													<input 
														type="number" 
														class="form-control tableform-control-withoutbg" 
														v-model="tentativeAmountData[state.state_id][pd.division_id]"
														@blur="formatTentativeInputValue(state.state_id, pd.division_id)"
														placeholder="0.00000"
														step="0.00001"
														min="0"
													>
												</td>
												<td>
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
											</template>
											<td class="fw-bold text-center bg-info-subtle">
												{{ calculateTentativeRowTotal(state.state_id) }}
											</td>
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
											<template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												<td>{{ calculateTentativeColumnTotal(pd.division_id) }}</td>
												<td>{{ calculateColumnTotal(pd.division_id) }}</td>
											</template>
											<td class="fw-bold text-center grand-total">
												{{ calculateTentativeGrandTotal() }}
											</td>
											<td class="fw-bold text-center grand-total">
												{{ calculateGrandTotal() }}
											</td>
											<td>-</td>
										</tr>
									</tbody>
								</table>
							</div>
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
        <!-- Fixed horizontal scrollbar at bottom of viewport -->
        <div
          v-show="showFixedScrollBar"
          ref="fixedScrollBar"
          class="fixed-horizontal-scrollbar"
          @scroll="onFixedScrollBarScroll"
        >
          <div ref="fixedScrollBarInner" class="fixed-horizontal-scrollbar-inner"></div>
        </div>
        <Footer />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, onUpdated, computed, watch, nextTick } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios';
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

// Reactive data
const states = ref([])
const programDivisions = ref([])
const allocationData = ref({})
const tentativeAmountData = ref({})
const remarksData = ref({})
const loading = ref(true)
const error = ref(null)
const submitting = ref(false)

const getCurrentFinancialYear = () => {
  const now = new Date()
  const calendarYear = now.getFullYear()
  const startYear = now.getMonth() >= 3 ? calendarYear : calendarYear - 1
  return `${startYear}-${String(startYear + 1).slice(-2)}`
}

const buildFinancialYearOptions = (yearsBack = 4) => {
  const currentStartYear = parseInt(getCurrentFinancialYear().split('-')[0], 10)
  const options = []
  for (let i = 0; i <= yearsBack; i++) {
    const startYear = currentStartYear - i
    const endYearShort = String(startYear + 1).slice(-2)
    options.push({
      value: `${startYear}-${endYearShort}`,
      label: `${startYear}-${startYear + 1}`,
    })
  }
  return options
}

const financialYearOptions = buildFinancialYearOptions()
const selectedFinancialYear = ref(getCurrentFinancialYear())

// Filters
const selectedStates = ref([])
const selectedProgramDivisions = ref([])
const tentativeAmountFilter = ref('')
const finalAllocationFilter = ref('')
const stateSearchTerm = ref('')
const pdSearchTerm = ref('')
const showStateDropdown = ref(false)
const showPdDropdown = ref(false)

// Fixed horizontal scrollbar at bottom of viewport
const reportTableScrollWrapper = ref(null)
const fixedScrollBar = ref(null)
const fixedScrollBarInner = ref(null)
const showFixedScrollBar = ref(false)
let scrollSyncLock = false

function updateFixedScrollBarWidth() {
  nextTick(() => {
    const wrapper = reportTableScrollWrapper.value
    const inner = fixedScrollBarInner.value
    const bar = fixedScrollBar.value
    if (!wrapper || !inner || !bar) return
    const tableEl = wrapper.querySelector('#reportTable table') || wrapper.querySelector('table')
    let contentWidth = tableEl && tableEl.scrollWidth > 0 ? tableEl.scrollWidth : wrapper.scrollWidth
    if (contentWidth <= 0) contentWidth = wrapper.scrollWidth
    const cw = wrapper.clientWidth
    inner.style.width = contentWidth + 'px'
    showFixedScrollBar.value = contentWidth > cw
    if (showFixedScrollBar.value) {
      const wrapperMax = wrapper.scrollWidth - wrapper.clientWidth
      const barMax = bar.scrollWidth - bar.clientWidth
      scrollSyncLock = true
      if (barMax > 0 && wrapperMax > 0) {
        bar.scrollLeft = (wrapper.scrollLeft / wrapperMax) * barMax
      } else {
        bar.scrollLeft = wrapper.scrollLeft
      }
      scrollSyncLock = false
    }
  })
}

function onTableWrapperScroll() {
  if (scrollSyncLock) return
  const wrapper = reportTableScrollWrapper.value
  const bar = fixedScrollBar.value
  if (!wrapper || !bar) return
  const wrapperMax = wrapper.scrollWidth - wrapper.clientWidth
  const barMax = bar.scrollWidth - bar.clientWidth
  if (wrapperMax <= 0 || barMax <= 0) return
  scrollSyncLock = true
  bar.scrollLeft = (wrapper.scrollLeft / wrapperMax) * barMax
  scrollSyncLock = false
}

function onFixedScrollBarScroll() {
  if (scrollSyncLock) return
  const wrapper = reportTableScrollWrapper.value
  const bar = fixedScrollBar.value
  if (!wrapper || !bar) return
  const wrapperMax = wrapper.scrollWidth - wrapper.clientWidth
  const barMax = bar.scrollWidth - bar.clientWidth
  if (wrapperMax <= 0 || barMax <= 0) return
  scrollSyncLock = true
  wrapper.scrollLeft = (bar.scrollLeft / barMax) * wrapperMax
  scrollSyncLock = false
}

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
    const response = await fetch(
      `/api/statewise-aap-allocation?financial_year=${encodeURIComponent(selectedFinancialYear.value)}`
    )
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
          
          // Populate tentative amount if it exists
          if (tentativeAmountData.value[stateId] && tentativeAmountData.value[stateId][pdId] !== undefined) {
            const tentativeAmount = allocation.tentative_amount
            if (tentativeAmount !== null && tentativeAmount !== undefined) {
              tentativeAmountData.value[stateId][pdId] = formatToFiveDecimals(tentativeAmount)
              console.log(`Set tentative amount for state ${stateId}, PD ${pdId}: ${formatToFiveDecimals(tentativeAmount)} (original: ${tentativeAmount})`)
            }
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
    tentativeAmountData.value[state.state_id] = {}
    remarksData.value[state.state_id] = ''
    
    programDivisions.value.forEach(pd => {
      allocationData.value[state.state_id][pd.division_id] = ''
      tentativeAmountData.value[state.state_id][pd.division_id] = ''
    })
    
    console.log(`Initialized data structure for state ${state.state_id}:`, allocationData.value[state.state_id])
  })
  
  console.log('Final allocation data structure:', allocationData.value)
  console.log('Final tentative amount data structure:', tentativeAmountData.value)
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

// Format tentative input value to 5 decimal places when field loses focus
const formatTentativeInputValue = (stateId, pdId) => {
  const currentValue = tentativeAmountData.value[stateId][pdId]
  if (currentValue !== null && currentValue !== undefined && currentValue !== '') {
    const numValue = parseFloat(currentValue)
    if (!isNaN(numValue)) {
      tentativeAmountData.value[stateId][pdId] = formatToFiveDecimals(numValue)
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

const matchesAmountFilter = (value, filterText) => {
  const filter = String(filterText || '').trim().toLowerCase()
  if (!filter) return true
  const raw = value === null || value === undefined || value === '' ? '0' : String(value)
  const formatted = formatToFiveDecimals(raw)
  return raw.toLowerCase().includes(filter) || formatted.toLowerCase().includes(filter)
}

const availableStates = computed(() => {
  return states.value.filter(state => !selectedStates.value.includes(state.state_id))
})

const filteredAvailableStates = computed(() => {
  if (!stateSearchTerm.value) return availableStates.value
  const search = stateSearchTerm.value.toLowerCase()
  return availableStates.value.filter(state => state.state_name.toLowerCase().includes(search))
})

const availableProgramDivisions = computed(() => {
  return programDivisions.value.filter(pd => !selectedProgramDivisions.value.includes(pd.division_id))
})

const filteredAvailableProgramDivisions = computed(() => {
  if (!pdSearchTerm.value) return availableProgramDivisions.value
  const search = pdSearchTerm.value.toLowerCase()
  return availableProgramDivisions.value.filter(pd => pd.division_name.toLowerCase().includes(search))
})

const filteredProgramDivisions = computed(() => {
  if (selectedProgramDivisions.value.length === 0) return programDivisions.value
  return programDivisions.value.filter(pd => selectedProgramDivisions.value.includes(pd.division_id))
})

const getStateTentativeTotalForFilter = (stateId) => {
  let total = 0
  filteredProgramDivisions.value.forEach(pd => {
    const value = parseFloat(tentativeAmountData.value[stateId]?.[pd.division_id]) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

const getStateFinalTotalForFilter = (stateId) => {
  let total = 0
  filteredProgramDivisions.value.forEach(pd => {
    const value = parseFloat(allocationData.value[stateId]?.[pd.division_id]) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

const filteredStates = computed(() => {
  let result = states.value
  if (selectedStates.value.length > 0) {
    result = result.filter(state => selectedStates.value.includes(state.state_id))
  }

  const tentativeFilter = tentativeAmountFilter.value.trim()
  if (tentativeFilter) {
    result = result.filter(state => {
      const hasMatchingCell = filteredProgramDivisions.value.some(pd =>
        matchesAmountFilter(tentativeAmountData.value[state.state_id]?.[pd.division_id], tentativeFilter)
      )
      return hasMatchingCell || matchesAmountFilter(getStateTentativeTotalForFilter(state.state_id), tentativeFilter)
    })
  }

  const finalFilter = finalAllocationFilter.value.trim()
  if (finalFilter) {
    result = result.filter(state => {
      const hasMatchingCell = filteredProgramDivisions.value.some(pd =>
        matchesAmountFilter(allocationData.value[state.state_id]?.[pd.division_id], finalFilter)
      )
      return hasMatchingCell || matchesAmountFilter(getStateFinalTotalForFilter(state.state_id), finalFilter)
    })
  }

  return result
})

const toggleStateDropdown = () => {
  showStateDropdown.value = !showStateDropdown.value
  if (showStateDropdown.value) showPdDropdown.value = false
}

const togglePdDropdown = () => {
  showPdDropdown.value = !showPdDropdown.value
  if (showPdDropdown.value) showStateDropdown.value = false
}

const selectState = (stateId) => {
  if (!selectedStates.value.includes(stateId)) {
    selectedStates.value.push(stateId)
    stateSearchTerm.value = ''
  }
  showStateDropdown.value = false
}

const removeState = (stateId) => {
  const index = selectedStates.value.indexOf(stateId)
  if (index > -1) selectedStates.value.splice(index, 1)
}

const selectProgramDivision = (pdId) => {
  if (!selectedProgramDivisions.value.includes(pdId)) {
    selectedProgramDivisions.value.push(pdId)
    pdSearchTerm.value = ''
  }
  showPdDropdown.value = false
}

const removeProgramDivision = (pdId) => {
  const index = selectedProgramDivisions.value.indexOf(pdId)
  if (index > -1) selectedProgramDivisions.value.splice(index, 1)
}

const getStateName = (stateId) => {
  const state = states.value.find(s => s.state_id === stateId)
  return state ? state.state_name : ''
}

const getProgramDivisionName = (pdId) => {
  const pd = programDivisions.value.find(p => p.division_id === pdId)
  return pd ? pd.division_name : ''
}

const clearFilters = () => {
  selectedStates.value = []
  selectedProgramDivisions.value = []
  tentativeAmountFilter.value = ''
  finalAllocationFilter.value = ''
  stateSearchTerm.value = ''
  pdSearchTerm.value = ''
  showStateDropdown.value = false
  showPdDropdown.value = false
  nextTick(updateFixedScrollBarWidth)
}

const handleClickOutside = () => {
  showStateDropdown.value = false
  showPdDropdown.value = false
}

// Calculate column total (visible states only)
const calculateColumnTotal = (pdId) => {
  let total = 0
  filteredStates.value.forEach(state => {
    const value = parseFloat(allocationData.value[state.state_id][pdId]) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Calculate tentative column total (visible states only)
const calculateTentativeColumnTotal = (pdId) => {
  let total = 0
  filteredStates.value.forEach(state => {
    const value = parseFloat(tentativeAmountData.value[state.state_id][pdId]) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Calculate row total for a specific state (visible PDs only)
const calculateRowTotal = (stateId) => {
  let total = 0
  filteredProgramDivisions.value.forEach(pd => {
    const value = parseFloat(allocationData.value[stateId][pd.division_id]) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Calculate tentative row total for a specific state (visible PDs only)
const calculateTentativeRowTotal = (stateId) => {
  let total = 0
  filteredProgramDivisions.value.forEach(pd => {
    const value = parseFloat(tentativeAmountData.value[stateId][pd.division_id]) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Calculate grand total (visible states + PDs)
const calculateGrandTotal = () => {
  let total = 0
  filteredStates.value.forEach(state => {
    filteredProgramDivisions.value.forEach(pd => {
      const value = parseFloat(allocationData.value[state.state_id][pd.division_id]) || 0
      total = addWithPrecision(total, value)
    })
  })
  return formatToFiveDecimals(total)
}

// Calculate tentative grand total (visible states + PDs)
const calculateTentativeGrandTotal = () => {
  let total = 0
  filteredStates.value.forEach(state => {
    filteredProgramDivisions.value.forEach(pd => {
      const value = parseFloat(tentativeAmountData.value[state.state_id][pd.division_id]) || 0
      total = addWithPrecision(total, value)
    })
  })
  return formatToFiveDecimals(total)
}

// Watch for changes in allocation data to trigger reactive updates
watch(allocationData, () => {
  // This will trigger reactive updates when allocation data changes
}, { deep: true })

watch([selectedStates, selectedProgramDivisions, tentativeAmountFilter, finalAllocationFilter], () => {
  nextTick(updateFixedScrollBarWidth)
})

// Submit allocation data
const submitAllocation = async () => {
  submitting.value = true
  
  try {
    // Prepare data for submission
    const submissionData = []
    
    states.value.forEach(state => {
      programDivisions.value.forEach(pd => {
        const amount = allocationData.value[state.state_id][pd.division_id]
        const tentativeAmount = tentativeAmountData.value[state.state_id][pd.division_id]
        
        // Allow zero values to be saved - check if amount is not null/undefined/empty string
        // but allow 0 as a valid value
        if (amount !== null && amount !== undefined && amount !== '') {
          // Parse and format to 5 decimals before submission to ensure exact precision
          const exactAmount = parseFloat(amount)
          // Check if it's a valid number (including 0)
          // This will save 0 when user explicitly enters 0
          if (!isNaN(exactAmount) && exactAmount >= 0) {
            // Parse tentative amount - default to 0 if not provided (matching amount column constraint)
            const exactTentativeAmount = (tentativeAmount !== null && tentativeAmount !== undefined && tentativeAmount !== '') 
              ? parseFloat(tentativeAmount) 
              : 0
            
            submissionData.push({
              financial_year: selectedFinancialYear.value,
              state_id: state.state_id,
              pd_id: pd.division_id,
              amount: exactAmount, // Save exact amount as entered (including 0, will be stored with 5 decimal precision in DB)
              tentative_amount: (!isNaN(exactTentativeAmount) && exactTentativeAmount >= 0) ? exactTentativeAmount : 0,
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

const onFinancialYearChange = async () => {
  try {
    loading.value = true
    initializeAllocationData()
    await fetchExistingAllocations()
  } catch (err) {
    console.error('Error loading data for financial year:', err)
    error.value = 'Failed to load allocation data for selected financial year'
  } finally {
    loading.value = false
    nextTick(updateFixedScrollBarWidth)
  }
}

// Load data on component mount
onMounted(async () => {
  window.addEventListener('resize', updateFixedScrollBarWidth)
  document.addEventListener('click', handleClickOutside)
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
    nextTick(updateFixedScrollBarWidth)
    setTimeout(updateFixedScrollBarWidth, 300)
  }
})

onUpdated(() => {
  if (!loading.value && !error.value) updateFixedScrollBarWidth()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateFixedScrollBarWidth)
  document.removeEventListener('click', handleClickOutside)
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

/* Horizontal scroll wrapper for report table - scrollbar at bottom of wrapper + fixed bar at viewport bottom */
.report-table-scroll-wrapper {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  overflow-y: visible;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  background: #fff;
  padding: 0;
  margin-top: 4px;
}

.report-table-scroll-wrapper::-webkit-scrollbar {
  height: 10px;
}

.report-table-scroll-wrapper::-webkit-scrollbar-track {
  background: #f1f3f5;
  border-radius: 0 0 6px 6px;
}

.report-table-scroll-wrapper::-webkit-scrollbar-thumb {
  background: #868e96;
  border-radius: 5px;
}

.report-table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
  background: #495057;
}

.report-table-scroll-wrapper {
  scrollbar-width: thin;
  scrollbar-color: #868e96 #f1f3f5;
}

.report-table-scroll-wrapper .table-responsive {
  margin-bottom: 0;
  min-width: max-content;
  width: max-content;
  overflow-x: visible;
  overflow-y: visible;
}

.report-table-scroll-wrapper table {
  min-width: max-content;
  width: max-content;
}

/* Fixed horizontal scrollbar at bottom of viewport */
.fixed-horizontal-scrollbar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 14px;
  overflow-x: auto;
  overflow-y: hidden;
  background: #f1f3f5;
  z-index: 1030;
  border-top: 1px solid #dee2e6;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.06);
}

.fixed-horizontal-scrollbar-inner {
  height: 1px;
  pointer-events: none;
}

.fixed-horizontal-scrollbar::-webkit-scrollbar {
  height: 12px;
}

.fixed-horizontal-scrollbar::-webkit-scrollbar-track {
  background: #e9ecef;
  border-radius: 0;
}

.fixed-horizontal-scrollbar::-webkit-scrollbar-thumb {
  background: #868e96;
  border-radius: 6px;
}

.fixed-horizontal-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #495057;
}

.fixed-horizontal-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #868e96 #e9ecef;
}

.table-warning .fw-sticky {
  background-color: #fff3cd;
}

.table-dark .fw-sticky {
  background-color: #212529;
  color: #fff;
}

/* Custom Multiselect Styles */
.custom-multiselect-container {
  position: relative;
}

.custom-multiselect-input {
  min-height: 38px;
  padding: 4px 8px;
  display: flex;
  align-items: center;
  cursor: text;
  position: relative;
}

.custom-multiselect-input.is-open {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.selected-tags-wrapper {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  flex: 1;
  min-width: 0;
}

.custom-tag {
  display: inline-flex;
  align-items: center;
  background-color: #b3d9ff;
  color: #0056b3;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 0.875rem;
  white-space: nowrap;
  margin: 2px 0;
}

.tag-remove {
  margin-left: 6px;
  cursor: pointer;
  font-weight: bold;
  font-size: 1rem;
  line-height: 1;
  color: #0056b3;
  padding: 0 2px;
}

.tag-remove:hover {
  color: #003d82;
}

.tag-input {
  border: none;
  outline: none;
  background: transparent;
  flex: 1;
  min-width: 100px;
  padding: 2px 4px;
  font-size: 0.875rem;
}

.tag-input::placeholder {
  color: #6c757d;
  opacity: 1;
}

.dropdown-arrows {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0 8px;
  color: #6c757d;
  font-size: 0.75rem;
  cursor: pointer;
}

.custom-dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ced4da;
  border-top: none;
  border-radius: 0 0 0.25rem 0.25rem;
  max-height: 200px;
  overflow-y: auto;
  overflow-x: hidden;
  z-index: 1000;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-top: -1px;
}

.custom-dropdown-menu::-webkit-scrollbar {
  width: 8px;
}

.custom-dropdown-menu::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.custom-dropdown-menu::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.custom-dropdown-menu::-webkit-scrollbar-thumb:hover {
  background: #555;
}

.custom-dropdown-menu {
  scrollbar-width: thin;
  scrollbar-color: #888 #f1f1f1;
}

.dropdown-item {
  padding: 8px 12px;
  cursor: pointer;
  font-size: 0.875rem;
  border-bottom: 1px solid #f0f0f0;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
}

.dropdown-item.text-muted {
  cursor: default;
  color: #6c757d;
}

</style>