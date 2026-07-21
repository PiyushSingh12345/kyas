<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
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
                  <a href="#">StateWise Release</a>
                </li>
              </ul>
            </div>
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
					<div class="card-header">
						<div class="card-title d-flex justify-content-between align-items-center">
							<span>StateWise Release Report for FY {{ selectedFinancialYear }} (₹ In {{ amountInText }})</span>
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
												<!-- Financial Year Filter -->
												<div class="col-md-3">
													<label for="financialYear" class="form-label fw-bold">Financial Year</label>
													<select 
														id="financialYear" 
														class="form-select" 
														v-model="selectedFinancialYear"
														@change="onFinancialYearChange"
													>
														<option value="2026-27">2026-2027</option>
														<option value="2025-26">2025-2026</option>
														<option value="2024-25">2024–2025</option>
														<option value="2023-24">2023–2024</option>
														<option value="2022-23">2022–2023</option>
													</select>
												</div>
												
												<!-- Amount In Filter -->
												<AmountInFilter v-model="amountIn" col-class="col-md-3" input-id="amountInSelect" />

												<!-- State Filter (Multiselect) -->
												<div class="col-md-4">
													<label class="form-label fw-bold">State <span class="text-danger">*</span></label>
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
																	@input="filterStates"
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
																:class="{ 'highlighted': highlightedStateIndex === filteredAvailableStates.indexOf(state) }"
																@click="selectState(state.state_id)"
																@mouseenter="highlightedStateIndex = filteredAvailableStates.indexOf(state)"
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
												<div class="col-md-4">
													<label class="form-label fw-bold">Program Division <span class="text-danger">*</span></label>
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
																	:placeholder="selectedProgramDivisions.length === 0 ? 'Select program divisions...' : ''"
																	@input="filterProgramDivisions"
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
																:class="{ 'highlighted': highlightedPdIndex === filteredAvailableProgramDivisions.indexOf(pd) }"
																@click="selectProgramDivision(pd.division_id)"
																@mouseenter="highlightedPdIndex = filteredAvailableProgramDivisions.indexOf(pd)"
															>
																{{ pd.division_name }}
															</div>
															<div v-if="filteredAvailableProgramDivisions.length === 0" class="dropdown-item text-muted">
																No program divisions available
															</div>
														</div>
													</div>
												</div>

												<!-- Clear Filters Button -->
												<div class="col-md-1">
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

							<!-- Export Buttons -->
							<div class="row mb-3">
								<div class="col-12 d-flex justify-content-end align-items-center">
									<div class="export-buttons">
										<button 
											class="btn btn-success btn-sm me-2" 
											@click="exportToExcel"
											title="Export to Excel"
										>
											<i class="fas fa-file-excel me-1"></i>EXCEL
										</button>
										<button 
											class="btn btn-info btn-sm me-2" 
											@click="exportToCSV"
											title="Export to CSV"
										>
											<i class="fas fa-file-csv me-1"></i>CSV
										</button>
										<button 
											class="btn btn-danger btn-sm" 
											@click="exportToPDF"
											title="Export to PDF"
										>
											<i class="fas fa-file-pdf me-1"></i>PDF
										</button>
									</div>
								</div>
							</div>

							<div ref="reportTableScrollWrapper" class="report-table-scroll-wrapper" @scroll="onTableWrapperScroll">
							<div class="table-responsive" id="reportTable">
								<table class="table table-bordered table-hover align-middle text-center">
									<thead class="table-dark">
										<tr>
											<th rowspan="2" class="align-middle fw-sticky">State</th>
											<th v-for="pd in filteredProgramDivisions" :key="pd.division_id" colspan="3">
												{{ pd.division_name }}
											</th>
											<th rowspan="2" class="align-middle bg-info">Total Allocation</th>
											<th rowspan="2" class="align-middle bg-success">Total Release</th>
											<th rowspan="2" class="align-middle bg-success">Total Expenditure</th>
										</tr>
										<tr>
											<template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												<th>Allocation</th>
												<th>Release</th>
												<th>Expenditure</th>
											</template>
										</tr>
									</thead>
									<tbody>
										<tr v-for="state in filteredStates" :key="state.state_id">
											<td class="fw-bold fw-sticky">{{ state.state_name }}</td>
											<template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												<td class="text-center">
													{{ formatCell(getAllocation(state.state_id, pd.division_id)) }}
												</td>
												<td class="text-center">
													{{ formatCell(getRelease(state.state_id, pd.division_id)) }}
												</td>
												<td class="text-center">
													{{ formatCell(getExpenditure(state.state_id, pd.division_id)) }}
												</td>
											</template>
											<td class="text-center fw-bold bg-info-subtle">
												{{ formatCell(calculateStateAllocationTotal(state.state_id)) }}
											</td>
											<td class="text-center fw-bold bg-success-subtle">
												{{ formatCell(calculateStateReleaseTotal(state.state_id)) }}
											</td>
											<td class="text-center fw-bold bg-success-subtle">
												{{ formatCell(calculateStateExpenditureTotal(state.state_id)) }}
											</td>
										</tr>
										
										<!-- Total Row -->
										<tr class="table-warning fw-bold">
											<td class="fw-sticky">Total</td>
											<template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												<td class="text-center">
													{{ formatCell(calculateAllocationColumnTotal(pd.division_id)) }}
												</td>
												<td class="text-center">
													{{ formatCell(calculateReleaseColumnTotal(pd.division_id)) }}
												</td>
												<td class="text-center">
													{{ formatCell(calculateExpenditureColumnTotal(pd.division_id)) }}
												</td>
											</template>
											<td class="text-center bg-info-subtle">
												{{ formatCell(calculateGrandAllocationTotal()) }}
											</td>
											<td class="text-center bg-success-subtle">
												{{ formatCell(calculateGrandReleaseTotal()) }}
											</td>
											<td class="text-center bg-success-subtle">
												{{ formatCell(calculateGrandExpenditureTotal()) }}
											</td>
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
import { ref, onMounted, onBeforeUnmount, onUpdated, computed, nextTick } from 'vue'
import { Link } from '@inertiajs/vue3'
import * as XLSX from 'xlsx'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import AmountInFilter from '../../Components/Reports/AmountInFilter.vue'
import { useAmountIn } from '../../Composables/useAmountIn'

// Reactive data
const states = ref([])
const programDivisions = ref([])
const reportData = ref({}) // Structure: { stateId: { pdId: { allocation, release, expenditure } } }
const loading = ref(true)
const error = ref(null)

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

// Filter data
const selectedFinancialYear = ref('2026-27')
const selectedStates = ref([])
const selectedProgramDivisions = ref([])
const tempState = ref('')
const tempProgramDivision = ref('')
const stateSearchTerm = ref('')
const pdSearchTerm = ref('')
const showStateDropdown = ref(false)
const showPdDropdown = ref(false)
const highlightedStateIndex = ref(-1)
const highlightedPdIndex = ref(-1)

// Amount In (base values in this report are Lakhs)
const { amountIn, amountInText, formatAmount } = useAmountIn('Lakh')
const amountFractionDigits = computed(() => (amountIn.value === 'Rupees' ? 2 : 5))
const formatCell = (value) => formatAmount(value, { fractionDigits: amountFractionDigits.value })

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

// Fetch report data
const fetchReportData = async () => {
  try {
    const response = await fetch(`/api/statewise-release-report?financial_year=${selectedFinancialYear.value}`)
    if (!response.ok) throw new Error('Failed to fetch report data')
    const result = await response.json()
    
    console.log('Report data result:', result)
    
    if (result.success && result.data) {
      reportData.value = result.data
    } else {
      console.log('No report data found')
      reportData.value = {}
    }
  } catch (err) {
    console.error('Error fetching report data:', err)
    error.value = 'Failed to load report data'
  }
}

// Computed properties for filters
const availableStates = computed(() => {
  return states.value.filter(state => !selectedStates.value.includes(state.state_id))
})

const filteredAvailableStates = computed(() => {
  if (!stateSearchTerm.value) {
    return availableStates.value
  }
  const search = stateSearchTerm.value.toLowerCase()
  return availableStates.value.filter(state => 
    state.state_name.toLowerCase().includes(search)
  )
})

const availableProgramDivisions = computed(() => {
  return programDivisions.value.filter(pd => !selectedProgramDivisions.value.includes(pd.division_id))
})

const filteredAvailableProgramDivisions = computed(() => {
  if (!pdSearchTerm.value) {
    return availableProgramDivisions.value
  }
  const search = pdSearchTerm.value.toLowerCase()
  return availableProgramDivisions.value.filter(pd => 
    pd.division_name.toLowerCase().includes(search)
  )
})

const filteredStates = computed(() => {
  if (selectedStates.value.length === 0) {
    return states.value
  }
  return states.value.filter(state => selectedStates.value.includes(state.state_id))
})

const filteredProgramDivisions = computed(() => {
  if (selectedProgramDivisions.value.length === 0) {
    return programDivisions.value
  }
  return programDivisions.value.filter(pd => selectedProgramDivisions.value.includes(pd.division_id))
})

// Filter functions
const toggleStateDropdown = () => {
  showStateDropdown.value = !showStateDropdown.value
  if (showStateDropdown.value) {
    highlightedStateIndex.value = -1
  }
}

const togglePdDropdown = () => {
  showPdDropdown.value = !showPdDropdown.value
  if (showPdDropdown.value) {
    highlightedPdIndex.value = -1
  }
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
  if (index > -1) {
    selectedStates.value.splice(index, 1)
  }
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
  if (index > -1) {
    selectedProgramDivisions.value.splice(index, 1)
  }
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
  selectedFinancialYear.value = '2026-27'
  selectedStates.value = []
  selectedProgramDivisions.value = []
  tempState.value = ''
  tempProgramDivision.value = ''
  stateSearchTerm.value = ''
  pdSearchTerm.value = ''
  showStateDropdown.value = false
  showPdDropdown.value = false
  highlightedStateIndex.value = -1
  highlightedPdIndex.value = -1
  fetchReportData()
}

const onFinancialYearChange = () => {
  fetchReportData()
}

const filterStates = () => {
  // This is handled by computed property
}

const filterProgramDivisions = () => {
  // This is handled by computed property
}

// Helper function to format number to exactly 5 decimal places
const formatToFiveDecimals = (value) => {
  if (value === null || value === undefined || value === '') {
    return '0.00000'
  }
  
  const valueStr = String(value)
  
  if (valueStr.includes('.')) {
    const parts = valueStr.split('.')
    const integerPart = parts[0]
    let decimalPart = parts[1] || ''
    
    if (decimalPart.length > 5) {
      decimalPart = decimalPart.substring(0, 5)
    } else {
      decimalPart = decimalPart.padEnd(5, '0')
    }
    
    return `${integerPart}.${decimalPart}`
  } else {
    return `${valueStr}.00000`
  }
}

// Get cell values for a state and PD (normalize keys for JSON string/number ids)
const getReportCell = (stateId, pdId) => {
  const data = reportData.value || {}
  const stateKeys = [stateId, String(stateId), Number(stateId)]
  const pdKeys = [pdId, String(pdId), Number(pdId)]
  for (const sk of stateKeys) {
    if (data[sk] == null) continue
    for (const pk of pdKeys) {
      if (data[sk][pk] != null) return data[sk][pk]
    }
  }
  return null
}

// Get allocation for a state and PD
const getAllocation = (stateId, pdId) => {
  const cell = getReportCell(stateId, pdId)
  if (!cell) return '0.00000'
  const amount = cell.allocation || 0
  return formatToFiveDecimals(amount)
}

// Get release for a state and PD
const getRelease = (stateId, pdId) => {
  const cell = getReportCell(stateId, pdId)
  if (!cell) return '0.00000'
  const amount = cell.release || 0
  return formatToFiveDecimals(amount)
}

// Get expenditure for a state and PD
const getExpenditure = (stateId, pdId) => {
  const cell = getReportCell(stateId, pdId)
  if (!cell) return '0.00000'
  const amount = cell.expenditure || 0
  return formatToFiveDecimals(amount)
}

// Helper function to add two numbers with 5 decimal precision
const addWithPrecision = (a, b) => {
  const numA = parseFloat(a) || 0
  const numB = parseFloat(b) || 0
  return Math.round((numA * 100000) + (numB * 100000)) / 100000
}

// Calculate allocation column total
const calculateAllocationColumnTotal = (pdId) => {
  let total = 0
  filteredStates.value.forEach(state => {
    const value = parseFloat(getAllocation(state.state_id, pdId)) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Calculate release column total
const calculateReleaseColumnTotal = (pdId) => {
  let total = 0
  filteredStates.value.forEach(state => {
    const value = parseFloat(getRelease(state.state_id, pdId)) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Calculate expenditure column total
const calculateExpenditureColumnTotal = (pdId) => {
  let total = 0
  filteredStates.value.forEach(state => {
    const value = parseFloat(getExpenditure(state.state_id, pdId)) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Calculate state row totals
const calculateStateAllocationTotal = (stateId) => {
  let total = 0
  filteredProgramDivisions.value.forEach(pd => {
    const value = parseFloat(getAllocation(stateId, pd.division_id)) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

const calculateStateReleaseTotal = (stateId) => {
  let total = 0
  filteredProgramDivisions.value.forEach(pd => {
    const value = parseFloat(getRelease(stateId, pd.division_id)) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

const calculateStateExpenditureTotal = (stateId) => {
  let total = 0
  filteredProgramDivisions.value.forEach(pd => {
    const value = parseFloat(getExpenditure(stateId, pd.division_id)) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Calculate grand totals for summary columns
const calculateGrandAllocationTotal = () => {
  let total = 0
  filteredStates.value.forEach(state => {
    const value = parseFloat(calculateStateAllocationTotal(state.state_id)) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

const calculateGrandReleaseTotal = () => {
  let total = 0
  filteredStates.value.forEach(state => {
    const value = parseFloat(calculateStateReleaseTotal(state.state_id)) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

const calculateGrandExpenditureTotal = () => {
  let total = 0
  filteredStates.value.forEach(state => {
    const value = parseFloat(calculateStateExpenditureTotal(state.state_id)) || 0
    total = addWithPrecision(total, value)
  })
  return formatToFiveDecimals(total)
}

// Function to prepare table data for export
const prepareTableData = () => {
  const data = []
  
  // First header row - Program Division names
  const headerRow1 = ['State']
  filteredProgramDivisions.value.forEach(pd => {
    headerRow1.push(pd.division_name, '', '') // 3 columns per PD
  })
  headerRow1.push('Total Allocation', 'Total Release', 'Total Expenditure')
  data.push(headerRow1)
  
  // Second header row - Column types
  const headerRow2 = ['']
  filteredProgramDivisions.value.forEach(() => {
    headerRow2.push('Allocation', 'Release', 'Expenditure')
  })
  headerRow2.push('', '', '')
  data.push(headerRow2)
  
  // Data rows for each state
  filteredStates.value.forEach(state => {
    const row = [state.state_name]
    
    // Add PD columns
    filteredProgramDivisions.value.forEach(pd => {
      row.push(
        formatCell(getAllocation(state.state_id, pd.division_id)),
        formatCell(getRelease(state.state_id, pd.division_id)),
        formatCell(getExpenditure(state.state_id, pd.division_id))
      )
    })
    
    // Add summary columns
    row.push(
      formatCell(calculateStateAllocationTotal(state.state_id)),
      formatCell(calculateStateReleaseTotal(state.state_id)),
      formatCell(calculateStateExpenditureTotal(state.state_id))
    )
    
    data.push(row)
  })
  
  // Total row
  const totalRow = ['Total']
  filteredProgramDivisions.value.forEach(pd => {
    totalRow.push(
      formatCell(calculateAllocationColumnTotal(pd.division_id)),
      formatCell(calculateReleaseColumnTotal(pd.division_id)),
      formatCell(calculateExpenditureColumnTotal(pd.division_id))
    )
  })
  totalRow.push(
    formatCell(calculateGrandAllocationTotal()),
    formatCell(calculateGrandReleaseTotal()),
    formatCell(calculateGrandExpenditureTotal())
  )
  data.push(totalRow)
  
  return data
}

// Function to export to Excel (.xlsx)
const exportToExcel = () => {
  const data = prepareTableData()
  const wb = XLSX.utils.book_new()
  const ws = XLSX.utils.aoa_to_sheet(data)
  XLSX.utils.book_append_sheet(wb, ws, 'StateWise Release')
  XLSX.writeFile(wb, `StateWise_Release_Report_${selectedFinancialYear.value}_${new Date().getTime()}.xlsx`)
}

// Function to export to CSV
const exportToCSV = () => {
  const data = prepareTableData()
  const sanitizeCsvCell = (value) => {
    // CSV/Formula injection mitigation for spreadsheet programs (Excel, LibreOffice).
    // If a cell starts with: = + - @ (after leading whitespace), prefix with apostrophe.
    const cellString = String(value || '')
    const trimmedStart = cellString.replace(/^\s+/, '')
    if (trimmedStart && ['=', '+', '-', '@'].includes(trimmedStart[0])) {
      return "'" + cellString
    }
    return cellString
  }
  let csvContent = ''
  
  data.forEach(row => {
    const csvRow = row.map(cell => {
      const cellValue = sanitizeCsvCell(cell)
      if (cellValue.includes(',') || cellValue.includes('"') || cellValue.includes('\n')) {
        return `"${cellValue.replace(/"/g, '""')}"`
      }
      return cellValue
    })
    csvContent += csvRow.join(',') + '\n'
  })
  
  const BOM = '\uFEFF'
  const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  
  link.setAttribute('href', url)
  link.setAttribute('download', `StateWise_Release_Report_${selectedFinancialYear.value}_${new Date().getTime()}.csv`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

// Function to export to PDF using print
const exportToPDF = () => {
  // Create a new window with the table content
  const printWindow = window.open('', '_blank')
  const tableElement = document.getElementById('reportTable')
  
  if (!tableElement) {
    alert('Table not found')
    return
  }
  
  // Get the table HTML
  const tableHTML = tableElement.outerHTML
  
  // Build HTML content using string concatenation to avoid Vue parsing issues
  const headStart = '<head>'
  const titleTag = '<title>StateWise Release Report - ' + selectedFinancialYear.value + '</title>'
  const styleStart = '<style>'
  const styles = 'body { font-family: Arial, sans-serif; margin: 20px; }' +
    'h2 { text-align: center; color: #333; }' +
    '.meta-info { text-align: center; margin-bottom: 20px; color: #666; }' +
    'table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 10px; }' +
    'table th, table td { border: 1px solid #ddd; padding: 6px; text-align: center; }' +
    'table th { background-color: #343a40; color: white; font-weight: bold; }' +
    '.bg-info, .bg-info-subtle { background-color: #d1ecf1 !important; }' +
    '.bg-success, .bg-success-subtle { background-color: #d4edda !important; }' +
    '.table-warning { background-color: #fff3cd !important; }' +
    '.fw-bold { font-weight: bold; }' +
    '.fw-sticky { position: sticky; left: 0; background-color: #fff; z-index: 1; }' +
    '@media print { @page { size: landscape; margin: 1cm; } body { margin: 0; } }'
  const styleEnd = '</style>'
  const headEnd = '</head>'
  
  const bodyStart = '<body>'
  const h2Tag = '<h2>StateWise Release Report</h2>'
  const metaInfoStart = '<div class="meta-info">'
  const financialYearP = '<p><strong>Financial Year:</strong> ' + selectedFinancialYear.value + '</p>'
  const generatedP = '<p><strong>Generated on:</strong> ' + new Date().toLocaleString() + '</p>'
  const statesP = selectedStates.value.length > 0 
    ? '<p><strong>States:</strong> ' + selectedStates.value.map(id => getStateName(id)).join(', ') + '</p>' 
    : '<p><strong>States:</strong> All States</p>'
  const programDivisionsP = selectedProgramDivisions.value.length > 0 
    ? '<p><strong>Program Divisions:</strong> ' + selectedProgramDivisions.value.map(id => getProgramDivisionName(id)).join(', ') + '</p>' 
    : '<p><strong>Program Divisions:</strong> All Program Divisions</p>'
  const metaInfoEnd = '</div>'
  const scriptStart = '<' + 'script' + '>'
  const scriptContent = 'window.onload = function() { window.print(); }'
  const scriptEnd = '<' + '/' + 'script' + '>'
  const scriptTag = scriptStart + scriptContent + scriptEnd
  const bodyEnd = '<' + '/' + 'body' + '>'
  const htmlEnd = '<' + '/' + 'html' + '>'
  
  const htmlContent = '<!DOCTYPE html><html>' +
    headStart + titleTag + styleStart + styles + styleEnd + headEnd +
    bodyStart + h2Tag + metaInfoStart + financialYearP + generatedP + statesP + programDivisionsP + metaInfoEnd +
    tableHTML + scriptTag + bodyEnd + htmlEnd
  
  printWindow.document.write(htmlContent)
  printWindow.document.close()
}

// Close dropdowns when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.custom-multiselect-container')) {
    showStateDropdown.value = false
    showPdDropdown.value = false
  }
}

// Load data on component mount
onMounted(async () => {
  window.addEventListener('resize', updateFixedScrollBarWidth)
  try {
    console.log('Component mounted, starting to load data...')
    
    // Add click outside listener
    document.addEventListener('click', handleClickOutside)
    
    await Promise.all([fetchStates(), fetchProgramDivisions()])
    
    await fetchReportData()
    
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

// Cleanup on unmount
onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('resize', updateFixedScrollBarWidth)
})
</script>

<style scoped>
.table th {
  vertical-align: middle;
  white-space: nowrap;
}

.table td {
  vertical-align: middle;
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

/* to make first column sticky of the table */
.fw-sticky {
    position: sticky;
    left: 0;
    background-color: #fff;
    z-index: 1;
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

/* Custom Scrollbar Styling */
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

/* Firefox Scrollbar */
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

.dropdown-item:hover,
.dropdown-item.highlighted {
  background-color: #f8f9fa;
}

.dropdown-item:active {
  background-color: #e9ecef;
}
</style>
