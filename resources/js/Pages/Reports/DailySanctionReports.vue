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
                  <a href="#">Daily Sanction Report </a>
                </li>
              </ul>

            </div>
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <div class="card-title">Daily Sanction Report</div>
                     <!-- <Link :href="route('daily-sanction')" class="btn btn-primary me-1" style="margin-left:91%">ADD</Link> -->

                  </div>
                  <div class="card-body">
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
                                  <option value="">All Financial Years</option>
                                  <option value="2025-26">2025-26</option>
                                  <option value="2024-25">2024-25</option>
                                  <option value="2023-24">2023-24</option>
                                  <option value="2022-23">2022-23</option>
                                </select>
                              </div>

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
                              <div class="col-md-12">
                                <label for="searchTerm" class="form-label fw-bold">Search</label>
                                <input 
                                  type="text" 
                                  id="searchTerm" 
                                  class="form-control" 
                                  v-model="searchTerm"
                                  placeholder="Search by SLS, IFD No, Daily Sanction No..."
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
                                  Showing {{ filteredMotherSanctions.length }} of {{ motherSanctions.length }} records
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Export Buttons -->
                    <div class="row mb-3" v-if="filteredMotherSanctions.length > 0">
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
                    
                    <div class="table-responsive mt-1" id="reportTable">
                      <table class="table table-bordered table-head-bg-primary">
                        <thead>
                        <tr>
                          <th>Financial year</th>
                          <th>State</th>
                          <th>SLS</th>
                          <th>Full SLS Name</th>
                          <th>PD</th>
                          <th>Date</th>
                          <th>IFD No</th>
                          <th>Mother Sanction Amount</th>
                          <th>Daily Sanction No</th>
                          <th>Daily Sanction Total Amount</th>
                          <th>Daily Sanction BH wise amount (₹ In Lakhs)</th>
                          <th>Remarks</th>
                        </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, index) in filteredMotherSanctions" :key="item.id">
                            <!-- <td>{{ index + 1 }}</td> -->
                            <td>{{ item.financial_year }}</td>
                            <td>{{ item.state.name }}</td>
                            <td>{{ item.sls_name }}</td>
                            <td>{{ item.full_sls_name || '-' }}</td>
                            <td>{{ item.sls_pd || '-' }}</td>
                            <!-- show date in dd-mm-yyyy format for example 01-04-2024 --> 
                            <td>{{ formatDate(item.ds_date) }} </td>
                            <td>{{ item.ifd_no }}</td>
                            <td class="currency-cell">{{ formatCurrency(item.mother_sanction_total_amount || 0) }}</td>
                            <td>{{ item.daily_sanction_no }}</td>
                            <td class="currency-cell">{{ formatCurrency(item.daily_sanction_total_amount || 0) }}</td>
                            <td>
                              <div class="budget-head-table">
                                <table class="table table-sm mb-0">
                                  <thead>
                                    <tr class="table-light">
                                      <th class="text-center">Budget Head</th>
                                      <th class="text-center">Daily Sanction Amount</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(budget, budgetIndex) in item.budget_heads" :key="budgetIndex">
                                      <td class="text-center">{{ budget.budget_head }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.daily_sanction_amount) }}</td>
                                    </tr>
                                    <tr v-if="!item.budget_heads || item.budget_heads.length === 0">
                                      <td colspan="2" class="text-center text-muted">No budget heads available</td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                            </td>
                            <td>{{ item.remark }}</td>

                          </tr>
                        </tbody>
                      </table>

                    </div>

                    <!-- <div class="table-responsive">
                      <table class="table table-bordered table-head-bg-primary">
                        <thead>
                          <tr>
                            <th>S. No.</th>
                            <th>Financial year</th>
                            <th>State</th>
                            <th>DS Date</th>
                            <th>Mother Sanction</th>
                          
                            <th>IFD No.</th>
                           
                            <th>SLS ID</th>
                            
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, index) in motherSanctions" :key="item.id">
                            <td>{{ index + 1 }}</td>
                            <td>{{ item.financial_year }}</td>
                            <td>{{ item.state.name }}</td>
                            <td>{{ formatDate(item.ds_date) }}</td>
                            <td>{{ item.mother_sanction }}</td>
                            <td>{{ item.ifd_no }}</td>
                            <td>{{ item.sls_name }}</td>

                          </tr>
                        </tbody>

                      </table>
                    </div> -->
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
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const motherSanctions = ref([])
const selectedFinancialYear = ref('')
const selectedState = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const searchTerm = ref('')
const isLoading = ref(false)

// Function to format date to dd-mm-yyyy format
const formatDate = (dateString) => {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString; // Return original if invalid date
    
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    return `${day}-${month}-${year}`;
  } catch (error) {
    console.error('Error formatting date:', error);
    return dateString; // Return original if error occurs
  }
}

// Function to format currency
const formatCurrency = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

// Computed property for unique states
const uniqueStates = computed(() => {
  const states = new Set()
  motherSanctions.value.forEach(item => {
    if (item.state && item.state.name) {
      states.add(item.state.name)
    }
  })
  return Array.from(states).sort()
})

// Function to fetch data from API
const fetchDailySanctions = async (financialYear = '') => {
  isLoading.value = true
  try {
    let url = '/api/daily-sanctions-list'
    if (financialYear) {
      url += `?financial_year=${financialYear}`
    }
    
    const res = await fetch(url)
    if (res.ok) {
      const data = await res.json()
      allMotherSanctions.value = data
      motherSanctions.value = data
    } else {
      console.error('Failed to fetch data')
    }
  } catch (error) {
    console.error('Error fetching data:', error)
  } finally {
    isLoading.value = false
  }
}

// Function to handle financial year change
const onFinancialYearChange = async () => {
  // Clear other filters when financial year changes
  selectedState.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  searchTerm.value = ''
  
  // Fetch data for the selected financial year
  await fetchDailySanctions(selectedFinancialYear.value || '')
}

// Computed property for filtered data
const filteredMotherSanctions = computed(() => {
  let filtered = motherSanctions.value

  // Note: Financial year filtering is now done at API level when data is fetched

  // Filter by state
  if (selectedState.value) {
    filtered = filtered.filter(item => item.state && item.state.name === selectedState.value)
  }

  // Filter by date range
  if (dateFrom.value) {
    filtered = filtered.filter(item => {
      if (!item.ds_date) return false
      const itemDate = new Date(item.ds_date)
      return itemDate >= new Date(dateFrom.value)
    })
  }

  if (dateTo.value) {
    filtered = filtered.filter(item => {
      if (!item.ds_date) return false
      const itemDate = new Date(item.ds_date)
      const toDate = new Date(dateTo.value)
      toDate.setHours(23, 59, 59, 999) // Include the entire end date
      return itemDate <= toDate
    })
  }

  // Filter by search term
  if (searchTerm.value && searchTerm.value.trim() !== '') {
    const searchLower = searchTerm.value.toLowerCase().trim()
    filtered = filtered.filter(item => {
      const slsName = String(item.sls_name || '').toLowerCase()
      const ifdNo = String(item.ifd_no || '').toLowerCase()
      const dailySanctionNo = String(item.daily_sanction_no || '').toLowerCase()
      return slsName.includes(searchLower) || ifdNo.includes(searchLower) || dailySanctionNo.includes(searchLower)
    })
  }

  return filtered
})

// Function to clear filters
const clearFilters = async () => {
  selectedFinancialYear.value = ''
  selectedState.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  searchTerm.value = ''
  // Fetch all data when filters are cleared
  await fetchDailySanctions('')
}

// Function to prepare table data for export
const prepareTableData = () => {
  const data = []
  
  // Add header row
  data.push([
    'Financial Year',
    'State',
    'SLS',
    'Full SLS Name',
    'PD',
    'Date',
    'IFD No',
    'Mother Sanction Amount',
    'Daily Sanction No',
    'Daily Sanction Total Amount',
    'Remarks'
  ])
  
  // Add data rows
  filteredMotherSanctions.value.forEach(item => {
    data.push([
      item.financial_year || '',
      item.state?.name || '',
      item.sls_name || '',
      item.full_sls_name || '-',
      item.sls_pd || '-',
      formatDate(item.ds_date),
      item.ifd_no || '',
      formatCurrency(item.mother_sanction_total_amount || 0),
      item.daily_sanction_no || '',
      formatCurrency(item.daily_sanction_total_amount || 0),
      item.remark || ''
    ])
  })
  
  return data
}

// Function to export to Excel
const exportToExcel = () => {
  const data = prepareTableData()
  let csvContent = ''
  
  data.forEach(row => {
    const csvRow = row.map(cell => {
      const cellValue = String(cell || '')
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
  link.setAttribute('download', `Daily_Sanction_Report_${new Date().getTime()}.xlsx`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

// Function to export to CSV
const exportToCSV = () => {
  const data = prepareTableData()
  let csvContent = ''
  
  data.forEach(row => {
    const csvRow = row.map(cell => {
      const cellValue = String(cell || '')
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
  link.setAttribute('download', `Daily_Sanction_Report_${new Date().getTime()}.csv`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

// Function to export to PDF
const exportToPDF = () => {
  const printWindow = window.open('', '_blank')
  const tableElement = document.getElementById('reportTable')
  
  if (!tableElement) {
    alert('Table not found')
    return
  }
  
  const tableHTML = tableElement.outerHTML
  
  const headStart = '<head>'
  const titleTag = '<title>Daily Sanction Report</title>'
  const styleStart = '<style>'
  const styles = 'body { font-family: Arial, sans-serif; margin: 20px; }' +
    'h2 { text-align: center; color: #333; }' +
    '.meta-info { text-align: center; margin-bottom: 20px; color: #666; }' +
    'table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 8px; }' +
    'table th, table td { border: 1px solid #ddd; padding: 4px; text-align: left; }' +
    'table th { background-color: #007bff; color: white; font-weight: bold; }' +
    '@media print { @page { size: landscape; margin: 1cm; } body { margin: 0; } }'
  const styleEnd = '</style>'
  const headEnd = '</head>'
  
  const bodyStart = '<body>'
  const h2Tag = '<h2>Daily Sanction Report</h2>'
  const metaInfoStart = '<div class="meta-info">'
  const generatedP = '<p><strong>Generated on:</strong> ' + new Date().toLocaleString() + '</p>'
  const metaInfoEnd = '</div>'
  const scriptStart = '<' + 'script' + '>'
  const scriptContent = 'window.onload = function() { window.print(); }'
  const scriptEnd = '<' + '/' + 'script' + '>'
  const scriptTag = scriptStart + scriptContent + scriptEnd
  const bodyEnd = '<' + '/' + 'body' + '>'
  const htmlEnd = '<' + '/' + 'html' + '>'
  
  const htmlContent = '<!DOCTYPE html><html>' +
    headStart + titleTag + styleStart + styles + styleEnd + headEnd +
    bodyStart + h2Tag + metaInfoStart + generatedP + metaInfoEnd +
    tableHTML + scriptTag + bodyEnd + htmlEnd
  
  printWindow.document.write(htmlContent)
  printWindow.document.close()
}

onMounted(async () => {
  await fetchDailySanctions('')
});
</script>

<style scoped>
/* Currency formatting */
.currency-cell {
  text-align: right;
  font-family: 'Courier New', monospace;
  font-weight: 500;
}

/* Table styling */
.table-head-bg-primary th {
  background-color: #007bff !important;
  color: white !important;
  font-weight: 600;
  text-align: center;
  vertical-align: middle;
}

.table-bordered {
  border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
  border: 1px solid #dee2e6;
}

/* Row hover effects */
.table tbody tr:hover {
  background-color: rgba(0, 123, 255, 0.05);
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

