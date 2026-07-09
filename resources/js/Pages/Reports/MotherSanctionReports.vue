<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
        <div class="container">
          <div class="page-inner allinsideform">
            <div class="page-header">
              <h3 class="fw-bold mb-3">Reports</h3>
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
                  <a href="#">Mother Sanction Reports</a>
                </li>
              </ul>

            </div>
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <div class="card-title">Mother Sanction Reports</div>
                     <!-- <Link :href="route('mother-sanction')" class="btn btn-primary me-1" style="margin-left:91%">ADD</Link> -->

                  </div>
                                    <div class="card-body">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="text-center py-4">
                      <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                      </div>
                      <p class="mt-2 text-muted">Loading mother sanction data...</p>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="alert alert-danger" role="alert">
                      <i class="fas fa-exclamation-triangle me-2"></i>
                      {{ error }}
                      <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchMotherSanctions">
                        <i class="fas fa-redo me-1"></i>Retry
                      </button>
                    </div>

                    <!-- Filters Section -->
                    <div v-if="!isLoading && !error" class="row mb-4">
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
                                  <option value="">All Financial Years</option>
                                  <option value="2026-27">2026-27</option>
                                  <option value="2025-26">2025-26</option>
                                  <option value="2024-25">2024-25</option>
                                  <option value="2023-24">2023-24</option>
                                  <option value="2022-23">2022-23</option>
                                </select>
                              </div>

                              <!-- Amount In Filter -->
                              <AmountInFilter v-model="amountIn" col-class="col-md-3" input-id="amountInSelect" />

                              <!-- State Filter -->
                              <div class="col-md-3">
                                <label for="stateFilter" class="form-label fw-bold">State</label>
                                <select 
                                  id="stateFilter" 
                                  class="form-select" 
                                  v-model="selectedState"
                                >
                                  <option value="">All States</option>
                                  <option v-for="state in uniqueStates" :key="state" :value="state">
                                    {{ state }}
                                  </option>
                                </select>
                              </div>

                              <!-- Status Filter -->
                              <div class="col-md-3">
                                <label for="statusFilter" class="form-label fw-bold">Status</label>
                                <select 
                                  id="statusFilter" 
                                  class="form-select" 
                                  v-model="selectedStatus"
                                >
                                  <option value="">All Status</option>
                                  <option value="active">Active</option>
                                  <option value="inactive">Inactive</option>
                                </select>
                              </div>

                              <!-- Date From Filter -->
                              <div class="col-md-3">
                                <label for="dateFrom" class="form-label fw-bold">Date From</label>
                                <input 
                                  type="date" 
                                  id="dateFrom" 
                                  class="form-control" 
                                  v-model="dateFrom"
                                >
                              </div>

                              <!-- Date To Filter -->
                              <div class="col-md-3">
                                <label for="dateTo" class="form-label fw-bold">Date To</label>
                                <input 
                                  type="date" 
                                  id="dateTo" 
                                  class="form-control" 
                                  v-model="dateTo"
                                >
                              </div>

                              <!-- Search Filter -->
                              <div class="col-md-9">
                                <label for="searchTerm" class="form-label fw-bold">Search</label>
                                <input 
                                  type="text" 
                                  id="searchTerm" 
                                  class="form-control" 
                                  v-model="searchTerm"
                                  placeholder="Search by MS No, SLS, IFD No..."
                                >
                              </div>
                            </div>

                            <!-- Filter Actions -->
                            <div class="row mt-3">
                              <div class="col-12">
                                <button 
                                  class="btn btn-secondary btn-sm me-2" 
                                  @click="clearFilters"
                                >
                                  <i class="fas fa-times me-1"></i>Clear Filters
                                </button>
                                <span class="text-muted ms-2">
                                  Showing {{ filteredSecondTableData.length }} of {{ secondTableData.length }} records
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Export Buttons -->
                    <div v-if="!isLoading && !error && filteredSecondTableData.length > 0" class="row mb-3">
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

                    <!-- Data Tables -->
                    <div v-if="!isLoading && !error">
                      <div ref="reportTableScrollWrapper" class="report-table-scroll-wrapper" @scroll="onTableWrapperScroll">
                      <div class="table-responsive" id="reportTable">
                        <table class="table table-bordered table-head-bg-primary">
                          <thead>
                            <tr>
                              <th>Fy</th>
                              <th>State</th>
                              <th>MS NO</th>
                              <th>Date</th>
                              <th>SLS Details</th>
                              <th>SL  Scode</th>
                              <th>Annual Allocation</th>
                              <th>MS Total Amount</th>
                              <th>Budget Head</th>
                              <th>Status</th>
                            </tr>
                          </thead>
                          
                          <tbody>
                          <tr v-for="(item, index) in filteredSecondTableData" :key="item.ky_ms_no" :class="{ 'table-secondary': item.status === 'inactive' }">
                            <td>{{ item.financial_year }}</td>
                            <td>{{ item.state }}</td>
                            <td>{{ item.ky_ms_no }}</td>
                            <td>{{ formatDate(item.sanction_date) }}</td>
                            <td>{{ item.sls_name }}</td>
                            <td>{{ item.sl_scode }}</td>
                            <td class="currency-cell">{{ formatCurrency(item.annual_allocation) }}</td>
                            <td class="currency-cell">{{ formatCurrency(item.total_mother_sanction_amount) }}</td>

                            <td>
                              <div class="budget-head-table">
                                <table class="table table-sm mb-0">
                                  <!-- <table class="table table-sm table-bordered mb-0"> -->
                                  <thead>
                                    <tr class="table-light">
                                      <th class="text-center">Budget Head</th>
                                      <th class="text-center">Category</th>
                                      <th class="text-center">MS Amount</th>
                                      <th class="text-center">Expenditure</th>
                                      <th class="text-center">Available Fund</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(budget, budgetIndex) in item.budget_heads" :key="budgetIndex">
                                      <td class="text-center">{{ budget.budget_head }}</td>
                                      <td class="text-center">{{ budget.category }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.mother_sanction_amount) }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.expenditure) }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.available_fund) }}</td>
                                    </tr>
                                    <tr v-if="!item.budget_heads || item.budget_heads.length === 0">
                                      <td colspan="5" class="text-center text-muted">No budget heads available</td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                            </td>

                            <td class="text-center status-column">
                              <div class="form-check form-switch d-flex justify-content-center">
                                <!-- <input 
                                  class="form-check-input" 
                                  type="checkbox" 
                                  :id="`status-${index}`"
                                  :checked="item.status === 'active'"
                                  @click="handleStatusToggle($event, item, index)"
                                > -->
                                <label class="form-check-label ms-2" :for="`status-${index}`">
                                  {{ item.status === 'active' ? 'Active' : 'Inactive' }}
                                </label>
                              </div>
                            </td>
                          </tr>
                          
                          <tr v-if="filteredSecondTableData.length === 0">
                            <td colspan="10" class="text-center text-muted py-4">
                              <i class="fas fa-info-circle me-2"></i>
                              No mother sanction data available
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
    
    <!-- Confirmation Dialog -->
    <div v-if="showConfirmDialog" class="modal fade show d-block" tabindex="-1" role="dialog" style="z-index: 1055;" @click="closeConfirmDialog">
      <div class="modal-backdrop fade show" style="z-index: 1050; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.5);"></div>
      <div class="modal-dialog modal-dialog-centered" role="document" style="z-index: 1055; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;" @click.stop>
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Status Change</h5>
            <button type="button" class="btn-close" @click="closeConfirmDialog"></button>
          </div>
          <div class="modal-body">
            <p v-if="selectedItem && selectedItem.status === 'active'">
              Do you want to proceed? This will deactivate the current record and redirect to create a new instance.
            </p>
            <p v-else>
              Do you want to proceed? This will activate the record.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeConfirmDialog">Cancel</button>
            <button type="button" class="btn btn-primary" @click="confirmStatusChange">Yes, Proceed</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import * as XLSX from 'xlsx'

import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import AmountInFilter from '../../Components/Reports/AmountInFilter.vue'
import { useAmountIn } from '../../Composables/useAmountIn'
import { useFixedHorizontalScroll } from '../../Composables/useFixedHorizontalScroll'

const motherSanctions = ref([])
const isLoading = ref(false)
const error = ref(null)

const {
  reportTableScrollWrapper,
  fixedScrollBar,
  fixedScrollBarInner,
  showFixedScrollBar,
  onTableWrapperScroll,
  onFixedScrollBarScroll,
  refreshFixedHorizontalScroll,
} = useFixedHorizontalScroll({
  shouldUpdate: () => !isLoading.value && !error.value,
})
const showConfirmDialog = ref(false)
const selectedItem = ref(null)
const selectedIndex = ref(null)
const originalStatus = ref(null)
const selectedFinancialYear = ref('')
const selectedState = ref('')
const selectedStatus = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const searchTerm = ref('')
const { amountIn, amountInText, formatAmount } = useAmountIn('Rupees', 'Rupees')

onMounted(async () => {
  // Fetch all data initially
  await fetchMotherSanctions('')
  refreshFixedHorizontalScroll()
  
  // Log available financial years from the data
  if (motherSanctions.value.length > 0) {
    const uniqueYears = [...new Set(motherSanctions.value.map(item => item.financial_year))]
    console.log('Available financial years in data:', uniqueYears)
  }
})

// Helper function to normalize financial year format for comparison
const normalizeFinancialYear = (year) => {
  if (!year) return ''
  // Convert "2025-26" to "2025-2026" or keep as is if already in full format
  if (year.match(/^\d{4}-\d{2}$/)) {
    const [start, end] = year.split('-')
    const startYear = parseInt(start)
    const endYear = parseInt('20' + end)
    return `${startYear}-${endYear}`
  }
  return year
}

// Helper function to check if financial year matches (handles both formats)
const matchesFinancialYear = (dataYear, filterYear) => {
  if (!filterYear) return true
  if (!dataYear) return false
  
  // Normalize both to full format for comparison
  const normalizedData = normalizeFinancialYear(dataYear)
  const normalizedFilter = normalizeFinancialYear(filterYear)
  
  return normalizedData === normalizedFilter || dataYear === filterYear
}

const fetchMotherSanctions = async (financialYear = '') => {
  isLoading.value = true
  error.value = null
  
  try {
    // Use the list endpoint which provides the correct data structure
    const res = await fetch('/api/mother-sanctions-list')
    if (res.ok) {
      let data = await res.json()
      
      // Log first item to see actual format
      if (data.length > 0) {
        console.log('Sample financial_year from API:', data[0].financial_year)
        console.log('Filter financial_year:', financialYear)
      }
      
      // Filter by financial year on client side since the API doesn't support it
      if (financialYear) {
        data = data.filter(item => matchesFinancialYear(item.financial_year, financialYear))
        console.log('Filtered data count:', data.length)
      }
      
      motherSanctions.value = data
      
      // Debug: Log the first item to verify sls_code is received
      if (data.length > 0) {
        console.log('First mother sanction item:', data[0])
        console.log('SLS Code received:', data[0].sls_code)
        console.log('Budget heads received:', data[0].budget_heads)
        console.log('Data structure:', JSON.stringify(data[0], null, 2))
      } else {
        console.log('No data found after filtering')
      }
    } else {
      console.error('Failed to fetch data')
      error.value = 'Failed to fetch data from server'
    }
  } catch (err) {
    console.error('Error fetching data:', err)
    error.value = 'Network error occurred while fetching data'
  } finally {
    isLoading.value = false
    refreshFixedHorizontalScroll()
  }
}

// Function to handle financial year change
const onFinancialYearChange = async () => {
  // Clear other filters when financial year changes
  selectedState.value = ''
  selectedStatus.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  searchTerm.value = ''
  
  // Fetch data for the selected financial year
  await fetchMotherSanctions(selectedFinancialYear.value || '')
}

// Computed property to transform data for the second table
const secondTableData = computed(() => {
  if (!motherSanctions.value.length) return [];
  
  // The backend now provides data already grouped with budget_heads
  // We just need to transform it for the second table format
  return motherSanctions.value.map(item => ({
    ky_ms_no: item.ky_ms_no,
    financial_year: item.financial_year,
    sanction_date: item.sanction_date,
    state: item.state?.name || '',
    state_id: item.state?.id || '',
    sls_name: item.sls_name,
    sls_id: item.sls_id || '',
    ms_sequence_no: item.ms_sequence_no || '',
    total_mother_sanction_amount: item.total_mother_sanction_amount,
    budget_heads: item.budget_heads || [],
    total_expenditure: 0, // This would come from daily sanctions if available
    annual_allocation: item.total_available_fund || 0, // Use total available fund from backend
    sl_scode: item.sls_code || item.sls_name?.substring(0, 2) || '', // Use sls_code from DB, fallback to substring
    status: item.status || 'active', // Default to active if not specified
    ifd_no: item.ifd_no || '',
  }));
});

// Computed property for unique states
const uniqueStates = computed(() => {
  const states = new Set()
  secondTableData.value.forEach(item => {
    if (item.state) {
      states.add(item.state)
    }
  })
  return Array.from(states).sort()
})

// Computed property for filtered data
const filteredSecondTableData = computed(() => {
  let filtered = secondTableData.value

  // Note: Financial year filtering is done when fetching data, but we also filter here for consistency
  if (selectedFinancialYear.value) {
    filtered = filtered.filter(item => matchesFinancialYear(item.financial_year, selectedFinancialYear.value))
  }

  // Filter by state
  if (selectedState.value) {
    filtered = filtered.filter(item => item.state === selectedState.value)
  }

  // Filter by status
  if (selectedStatus.value) {
    filtered = filtered.filter(item => item.status === selectedStatus.value)
  }

  // Filter by date range
  if (dateFrom.value) {
    filtered = filtered.filter(item => {
      if (!item.sanction_date) return false
      const itemDate = new Date(item.sanction_date)
      return itemDate >= new Date(dateFrom.value)
    })
  }

  if (dateTo.value) {
    filtered = filtered.filter(item => {
      if (!item.sanction_date) return false
      const itemDate = new Date(item.sanction_date)
      const toDate = new Date(dateTo.value)
      toDate.setHours(23, 59, 59, 999)
      return itemDate <= toDate
    })
  }

  // Filter by search term
  if (searchTerm.value && searchTerm.value.trim() !== '') {
    const searchLower = searchTerm.value.toLowerCase().trim()
    filtered = filtered.filter(item => {
      const msNo = String(item.ky_ms_no || '').toLowerCase()
      const slsName = String(item.sls_name || '').toLowerCase()
      const ifdNo = String(item.ifd_no || '').toLowerCase()
      return msNo.includes(searchLower) || slsName.includes(searchLower) || ifdNo.includes(searchLower)
    })
  }

  return filtered
})

// Function to clear filters
const clearFilters = async () => {
  selectedFinancialYear.value = ''
  selectedState.value = ''
  selectedStatus.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  searchTerm.value = ''
  // Fetch all data when filters are cleared
  await fetchMotherSanctions('')
}

const getStatusLabel = (status) => (status === 'active' ? 'Active' : 'Inactive')

const getExportFilterSummary = () => {
  const parts = [`Amount in ${amountInText.value}`]
  if (selectedFinancialYear.value) parts.push(`FY: ${selectedFinancialYear.value}`)
  if (selectedState.value) parts.push(`State: ${selectedState.value}`)
  if (selectedStatus.value) parts.push(`Status: ${getStatusLabel(selectedStatus.value)}`)
  if (dateFrom.value) parts.push(`From: ${formatDate(dateFrom.value)}`)
  if (dateTo.value) parts.push(`To: ${formatDate(dateTo.value)}`)
  if (searchTerm.value?.trim()) parts.push(`Search: ${searchTerm.value.trim()}`)
  parts.push(`Records: ${filteredSecondTableData.value.length}`)
  return parts.join(' | ')
}

// Flatten the currently filtered table into export rows (one row per budget head).
const prepareTableData = () => {
  const data = []

  data.push([`Mother Sanction Report (${getExportFilterSummary()})`])
  data.push([
    'Fy',
    'State',
    'MS NO',
    'Date',
    'SLS Details',
    'SL Scode',
    'Annual Allocation',
    'MS Total Amount',
    'Budget Head',
    'Category',
    'MS Amount',
    'Expenditure',
    'Available Fund',
    'Status',
  ])

  filteredSecondTableData.value.forEach((item) => {
    const parentCols = [
      item.financial_year || '',
      item.state || '',
      item.ky_ms_no || '',
      formatDate(item.sanction_date),
      item.sls_name || '',
      item.sl_scode || '',
      formatCurrency(item.annual_allocation),
      formatCurrency(item.total_mother_sanction_amount),
    ]
    const statusLabel = getStatusLabel(item.status)
    const budgetHeads = item.budget_heads?.length ? item.budget_heads : [null]

    budgetHeads.forEach((budget) => {
      data.push([
        ...parentCols,
        budget?.budget_head || '',
        budget?.category || '',
        budget ? formatCurrency(budget.mother_sanction_amount) : '',
        budget ? formatCurrency(budget.expenditure) : '',
        budget ? formatCurrency(budget.available_fund) : '',
        statusLabel,
      ])
    })
  })

  return data
}

const buildExportTableHtml = () => {
  const rows = filteredSecondTableData.value.map((item) => {
    const budgetRows = (item.budget_heads?.length ? item.budget_heads : []).map((budget) => `
      <tr>
        <td class="text-center">${budget.budget_head || ''}</td>
        <td class="text-center">${budget.category || ''}</td>
        <td class="text-center currency-cell">${formatCurrency(budget.mother_sanction_amount)}</td>
        <td class="text-center currency-cell">${formatCurrency(budget.expenditure)}</td>
        <td class="text-center currency-cell">${formatCurrency(budget.available_fund)}</td>
      </tr>
    `).join('')

    const budgetTableBody = budgetRows || `
      <tr>
        <td colspan="5" class="text-center text-muted">No budget heads available</td>
      </tr>
    `

    return `
      <tr>
        <td>${item.financial_year || ''}</td>
        <td>${item.state || ''}</td>
        <td>${item.ky_ms_no || ''}</td>
        <td>${formatDate(item.sanction_date)}</td>
        <td>${item.sls_name || ''}</td>
        <td>${item.sl_scode || ''}</td>
        <td class="currency-cell">${formatCurrency(item.annual_allocation)}</td>
        <td class="currency-cell">${formatCurrency(item.total_mother_sanction_amount)}</td>
        <td>
          <table class="nested-table">
            <thead>
              <tr>
                <th>Budget Head</th>
                <th>Category</th>
                <th>MS Amount</th>
                <th>Expenditure</th>
                <th>Available Fund</th>
              </tr>
            </thead>
            <tbody>${budgetTableBody}</tbody>
          </table>
        </td>
        <td class="text-center">${getStatusLabel(item.status)}</td>
      </tr>
    `
  }).join('')

  const emptyRow = filteredSecondTableData.value.length === 0
    ? '<tr><td colspan="10" class="text-center text-muted">No mother sanction data available</td></tr>'
    : ''

  return `
    <table class="report-table">
      <thead>
        <tr>
          <th>Fy</th>
          <th>State</th>
          <th>MS NO</th>
          <th>Date</th>
          <th>SLS Details</th>
          <th>SL Scode</th>
          <th>Annual Allocation</th>
          <th>MS Total Amount</th>
          <th>Budget Head</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>${rows}${emptyRow}</tbody>
    </table>
  `
}

// Function to export to Excel (.xlsx)
const exportToExcel = () => {
  const data = prepareTableData()
  const wb = XLSX.utils.book_new()
  const ws = XLSX.utils.aoa_to_sheet(data)
  XLSX.utils.book_append_sheet(wb, ws, 'Mother Sanction Report')
  XLSX.writeFile(wb, `Mother_Sanction_Report_${new Date().getTime()}.xlsx`)
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
  link.setAttribute('download', `Mother_Sanction_Report_${new Date().getTime()}.csv`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

// Function to export to PDF
const exportToPDF = () => {
  const printWindow = window.open('', '_blank')

  if (!printWindow) {
    alert('Unable to open print window. Please allow pop-ups for this site.')
    return
  }

  const tableHTML = buildExportTableHtml()
  const filterSummary = getExportFilterSummary()

  const headStart = '<head>'
  const titleTag = '<title>Mother Sanction Report</title>'
  const styleStart = '<style>'
  const styles = 'body { font-family: Arial, sans-serif; margin: 20px; }' +
    'h2 { text-align: center; color: #333; }' +
    '.meta-info { text-align: center; margin-bottom: 20px; color: #666; }' +
    '.report-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 8px; }' +
    '.report-table th, .report-table td { border: 1px solid #ddd; padding: 4px; text-align: left; vertical-align: top; }' +
    '.report-table th { background-color: #007bff; color: white; font-weight: bold; text-align: center; }' +
    '.nested-table { width: 100%; border-collapse: collapse; font-size: 8px; }' +
    '.nested-table th, .nested-table td { border: 1px solid #ddd; padding: 3px; text-align: center; }' +
    '.nested-table th { background-color: #e9ecef; color: #495057; }' +
    '.currency-cell { text-align: right; font-family: "Courier New", monospace; }' +
    '@media print { @page { size: landscape; margin: 1cm; } body { margin: 0; } }'
  const styleEnd = '</style>'
  const headEnd = '</head>'

  const bodyStart = '<body>'
  const h2Tag = '<h2>Mother Sanction Report</h2>'
  const metaInfoStart = '<div class="meta-info">'
  const generatedP = '<p><strong>Generated on:</strong> ' + new Date().toLocaleString() + '</p>'
  const filtersP = '<p><strong>Filters:</strong> ' + filterSummary + '</p>'
  const metaInfoEnd = '</div>'
  const scriptStart = '<' + 'script' + '>'
  const scriptContent = 'window.onload = function() { window.print(); }'
  const scriptEnd = '<' + '/' + 'script' + '>'
  const scriptTag = scriptStart + scriptContent + scriptEnd
  const bodyEnd = '<' + '/' + 'body' + '>'
  const htmlEnd = '<' + '/' + 'html' + '>'

  const htmlContent = '<!DOCTYPE html><html>' +
    headStart + titleTag + styleStart + styles + styleEnd + headEnd +
    bodyStart + h2Tag + metaInfoStart + generatedP + filtersP + metaInfoEnd +
    tableHTML + scriptTag + bodyEnd + htmlEnd

  printWindow.document.write(htmlContent)
  printWindow.document.close()
}

// Method to calculate available balance
const calculateAvailableBalance = (row) => {
  const totalAllocated = parseFloat(row.total_mother_sanction_amount) || 0;
  const totalExpenditure = parseFloat(row.total_expenditure) || 0;
  return (totalAllocated - totalExpenditure).toFixed(2);
};

// Method to format date
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-IN');
};

// Method to format currency
const formatCurrency = (amount) => {
  return formatAmount(amount || 0, { fractionDigits: 2 })
};

// Method to handle status toggle
const handleStatusToggle = (event, item, index) => {
  event.preventDefault(); // Prevent the default toggle behavior
  console.log('Toggle clicked:', item, 'Status:', item.status);
  
  // Store the original status
  originalStatus.value = item.status;
  
  // Show dialog for both active and inactive records
  selectedItem.value = item;
  selectedIndex.value = index;
  showConfirmDialog.value = true;
  console.log('Dialog should open now');
};

// Method to close confirmation dialog
const closeConfirmDialog = () => {
  showConfirmDialog.value = false;
  selectedItem.value = null;
  selectedIndex.value = null;
  originalStatus.value = null;
};

// Method to confirm status change and redirect
const confirmStatusChange = async () => {
  if (selectedItem.value) {
    try {
      // Determine the action based on current status
      const action = selectedItem.value.status === 'active' ? 'deactivate' : 'activate';
      
      const response = await fetch('/api/mother-sanction/update-status', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          ky_ms_no: selectedItem.value.ky_ms_no,
          action: action,
          state_id: selectedItem.value.state_id,
          sls_name: selectedItem.value.sls_name || '',
        })
      });

      if (response.ok) {
        // Refresh the data to reflect the status change
        await fetchMotherSanctions();
        
        // Only redirect to add page if deactivating (creating new instance)
        if (action === 'deactivate') {
          // Create query parameters for prefilling the form
          const queryParams = new URLSearchParams({
            edit: 'true',
            ky_ms_no: selectedItem.value.ky_ms_no,
            financial_year: selectedItem.value.financial_year,
            state_id: selectedItem.value.state_id || '',
            sls_id: selectedItem.value.sls_id || '',
            ms_sequence_no: parseInt(selectedItem.value.ms_sequence_no) || '',
            sanction_date: selectedItem.value.sanction_date,
            ifd_no: selectedItem.value.ifd_no || '',
            sls_name: selectedItem.value.sls_name || '',
            pd_component: selectedItem.value.pd_component || '',
            remark: selectedItem.value.remark || '',
            // Add budget heads data as JSON
            budget_heads: JSON.stringify(selectedItem.value.budget_heads || [])
          });
          
          // Redirect to the add page with prefilled data
          window.location.href = `/mother-sanction?${queryParams.toString()}`;
        } else {
          // Just close the dialog for activation
          closeConfirmDialog();
        }
      } else {
        console.error(`Failed to ${action} record`);
        alert(`Failed to update status. Please try again.`);
      }
    } catch (error) {
      console.error('Error updating status:', error);
      alert('An error occurred while updating status. Please try again.');
    }
  }
  closeConfirmDialog();
};
</script>

<style scoped>
/* Custom styling for the tables */
.table-responsive {
  margin-top: 20px;
}

.table-head-bg-primary th {
  background-color: #007bff !important;
  color: white !important;
  font-weight: 600;
  text-align: center;
  vertical-align: middle;
}

/* Nested budget head table styling */
.table-sm {
  font-size: 0.875rem;
}

.table-sm th {
  background-color: #f8f9fa !important;
  color: #495057 !important;
  font-weight: 600;
  padding: 6px 8px;
}

.table-sm td {
  padding: 6px 8px;
  vertical-align: middle;
}

/* Budget head table container */
.budget-head-table {
  max-height: 200px;
  overflow-y: auto;
}

/* Row hover effects */
.table tbody tr:hover {
  background-color: rgba(0, 123, 255, 0.05);
}

/* Empty state styling */
.text-muted {
  color: #6c757d !important;
}

/* Currency formatting */
.currency-cell {
  text-align: right;
  font-family: 'Courier New', monospace;
  font-weight: 500;
}

/* Status indicators */
.status-submitted {
  color: #28a745;
  font-weight: 600;
}

.status-draft {
  color: #ffc107;
  font-weight: 600;
}

/* Responsive table adjustments */
@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }
  
  .table-sm {
    font-size: 0.75rem;
  }
  
  .table-sm th,
  .table-sm td {
    padding: 4px 6px;
  }
}

/* Table spacing and borders */
.table-bordered {
  border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
  border: 1px solid #dee2e6;
}

/* First table specific styling */
.table-responsive:first-of-type {
  margin-top: 0;
}

/* Second table specific styling */
.table-responsive:last-of-type {
  margin-top: 30px;
}

/* Budget head nested table improvements */
.budget-head-table .table {
  margin-bottom: 0;
  background-color: white;
}

.budget-head-table .table-light th {
  background-color: #e9ecef !important;
  border-color: #dee2e6;
}

.budget-head-table .table tbody tr:nth-child(even) {
  background-color: #f8f9fa;
}

/* Toggle switch styling */
.form-check-input:checked {
  background-color: #28a745;
  border-color: #28a745;
}

.form-check-input:disabled {
  background-color: #6c757d;
  border-color: #6c757d;
  opacity: 0.5;
}

/* Dialog styling */
.modal.show {
  display: block !important;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1055;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1050;
}

.modal-dialog {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  margin: 0;
  z-index: 1055;
}

/* Status column styling */
.status-column {
  min-width: 120px;
}

/* Export buttons styling */
.export-buttons {
  display: flex;
  gap: 8px;
}

.export-buttons .btn {
  font-weight: 600;
  padding: 8px 16px;
  border-radius: 4px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.export-buttons .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.export-buttons .btn-success {
  background-color: #28a745;
  border-color: #28a745;
}

.export-buttons .btn-info {
  background-color: #17a2b8;
  border-color: #17a2b8;
}

.export-buttons .btn-danger {
  background-color: #dc3545;
  border-color: #dc3545;
}
</style>


