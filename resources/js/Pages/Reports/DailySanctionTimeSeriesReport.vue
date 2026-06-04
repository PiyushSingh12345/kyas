<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container py-4">
        <div class="page-inner allinsideform">
          
          <div class="page-header">
            <h3 class="fw-bold mb-3">MIS Reports & Dashboards</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Daily Sanction Report</a>
              </li>
            </ul>
          </div>

          <!-- Title and Action Buttons -->
          <div class="report-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <h2 class="report-title">Daily Sanction Summary</h2>
              <div class="action-buttons">
                <button class="btn btn-action" @click="exportExcel">
                  <i class="fas fa-download"></i> EXCEL
                </button>
                <button class="btn btn-action" @click="exportCSV">
                  <i class="fas fa-download"></i> CSV
                </button>
                <button class="btn btn-action" @click="toggleFilters">
                  <i class="fas fa-filter"></i> FILTERS
                </button>
              </div>
            </div>
          </div>

          <!-- Information Notes -->
          <div class="info-notes mb-3">
            <small class="text-muted">
              <span class="me-3">Source: KYAS System</span>
              <span class="me-3">Amount in {{ amountInText }}</span>
              <span class="me-3">Data updated as of latest entries</span>
            </small>
          </div>

          <!-- Filters Sidebar -->
          <div v-if="showFilters" class="filters-sidebar" @click.stop>
            <div class="filters-header">
              <h5>FILTERS</h5>
              <button class="btn-close-filter" @click="toggleFilters">
                <i class="fas fa-times"></i>
              </button>
            </div>
            
            <div class="filters-content">
              <div class="filter-group">
                <label class="filter-label">METRIC</label>
                <div class="filter-input-wrapper">
                  <div class="selected-tags">
                    <span v-for="metric in selectedMetrics" :key="metric" class="tag">
                      {{ metric }}
                      <i class="fas fa-times tag-remove" @click="removeMetric(metric)"></i>
                    </span>
                  </div>
                  <select 
                    v-model="tempMetric" 
                    class="form-select form-select-sm"
                    @change="addMetric"
                  >
                    <option value="">Select Metric</option>
                    <option value="Center Share Amount">Center Share Amount</option>
                    <option value="Mother Sanction Amount">Mother Sanction Amount</option>
                    <option value="Available Amount">Available Amount</option>
                  </select>
                </div>
              </div>

              <div class="filter-group">
                <label class="filter-label">Amount In</label>
                <select v-model="amountIn" class="form-select form-select-sm">
                  <option v-for="opt in AMOUNT_IN_OPTIONS" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label">Group By</label>
                <select v-model="groupBy" class="form-select form-select-sm" @change="applyFilters">
                  <option value="state">State</option>
                  <option value="budget_head">Budget Head</option>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label">State</label>
                <select v-model="selectedState" class="form-select form-select-sm">
                  <option value="">All States</option>
                  <option v-for="state in states" :key="state.id" :value="state.id">
                    {{ state.name }}
                  </option>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label">Financial Year</label>
                <select v-model="selectedYear" class="form-select form-select-sm">
                  <option value="">All Years</option>
                  <option v-for="year in financialYears" :key="year" :value="year">
                    {{ year }}
                  </option>
                </select>
              </div>

              <button class="btn btn-primary w-100 mt-3" @click="applyFilters">
                APPLY
              </button>
            </div>
          </div>

          <!-- Main Data Table -->
          <div class="table-wrapper">
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th rowspan="2" class="sticky-col">State</th>
                    <th rowspan="2" class="sticky-col">
                      Budget Head
                      <i class="fas fa-sort-up sort-icon" @click="sortByBudgetHead"></i>
                    </th>
                    <template v-for="metric in displayMetrics" :key="metric">
                      <th 
                        :colspan="years.length"
                        :class="getMetricClass(metric)"
                      >
                        {{ metric }}
                      </th>
                    </template>
                  </tr>
                  <tr>
                    <template v-for="(metric, mIdx) in displayMetrics" :key="'metric-' + mIdx">
                      <th 
                        v-for="year in years" 
                        :key="'year-' + year"
                        class="year-header"
                      >
                        {{ year }}
                      </th>
                    </template>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="groupedData.length > 0">
                    <template v-for="(group, gIdx) in groupedData" :key="'group-' + gIdx">
                      <tr v-for="(item, idx) in group.items" :key="'item-' + gIdx + '-' + idx" :class="{ 'total-row': item.is_total }">
                        <td class="sticky-col" v-if="idx === 0" :rowspan="group.items.length">
                          <strong>{{ group.state }}</strong>
                        </td>
                        <td class="sticky-col" :class="{ 'fw-bold': item.is_total }">
                          {{ item.budget_head }}
                        </td>
                        <template v-for="(metric, mIdx) in displayMetrics" :key="'metric-' + mIdx">
                          <td 
                            v-for="year in years" 
                            :key="'cell-' + mIdx + '-' + year"
                            class="text-end"
                          >
                            {{ formatValue(item.metrics[year]?.[getMetricKey(metric)] || 0) }}
                          </td>
                        </template>
                      </tr>
                    </template>
                  </template>
                  <tr v-else>
                    <td :colspan="2 + displayMetrics.length * years.length" class="text-center py-4">
                      No data available. Please adjust filters.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
      <Footer />
    </div>
  </div>
</template>

<style scoped>
.wrapper {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.page-inner {
  max-width: 100%;
  margin: 0 auto;
  padding: 0 15px;
}

.report-header {
  border-bottom: 2px solid #ffd700;
  padding-bottom: 15px;
}

.report-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a4480;
  margin: 0;
}

.action-buttons {
  display: flex;
  gap: 10px;
}

.btn-action {
  background-color: #6c757d;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-action:hover {
  background-color: #5a6268;
}

.btn-action i {
  margin-right: 5px;
}

.info-notes {
  font-size: 0.875rem;
  padding: 10px 0;
}

/* Filters Sidebar */
.filters-sidebar {
  position: fixed;
  right: 0;
  top: 0;
  width: 300px;
  height: 100vh;
  background-color: #e3f2fd;
  box-shadow: -2px 0 8px rgba(0,0,0,0.1);
  z-index: 1050;
  overflow-y: auto;
  padding: 20px;
}

/* Page inner remains unchanged - sidebar overlays */

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #90caf9;
}

.filters-header h5 {
  margin: 0;
  color: #1976d2;
  font-weight: 600;
}

.btn-close-filter {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: #666;
}

.filter-group {
  margin-bottom: 20px;
}

.filter-label {
  display: block;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
  font-size: 0.875rem;
}

.filter-input-wrapper {
  position: relative;
}

.selected-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin-bottom: 5px;
}

.tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background-color: #2196f3;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
}

.tag-remove {
  cursor: pointer;
  font-size: 0.7rem;
}

.form-select-sm {
  padding: 6px 12px;
  font-size: 0.875rem;
}

/* Table Styles */
.table-wrapper {
  background: white;
  border-radius: 4px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table thead {
  background-color: #e9ecef;
}

.data-table th {
  padding: 12px 8px;
  text-align: center;
  font-weight: 600;
  border: 1px solid #dee2e6;
  vertical-align: middle;
}

.data-table th.metric-area {
  background-color: #fff3cd;
}

.data-table th.metric-production {
  background-color: #d1e7dd;
}

.data-table th.metric-yield {
  background-color: #f8d7da;
}

.year-header {
  background-color: #f8f9fa;
  font-weight: 500;
}

.total-row {
  background-color: #e7f3ff;
  font-weight: 600;
}

.total-row td {
  font-weight: 600;
}

.data-table td {
  padding: 10px 8px;
  border: 1px solid #dee2e6;
  text-align: center;
}

.data-table td.sticky-col {
  background-color: #e3f2fd;
  font-weight: 500;
  position: sticky;
  left: 0;
  z-index: 10;
}

.data-table th.sticky-col {
  background-color: #e3f2fd;
  position: sticky;
  left: 0;
  z-index: 10;
}

.data-table tbody tr:nth-child(even) {
  background-color: #f8f9fa;
}

.data-table tbody tr:hover {
  background-color: #e9f5ff;
}

.total-label {
  color: #1a4480;
}

.sort-icon {
  margin-left: 5px;
  cursor: pointer;
  color: #666;
}

.text-end {
  text-align: right;
}

.text-center {
  text-align: center;
}

@media (max-width: 768px) {
  .report-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .action-buttons {
    margin-top: 15px;
    flex-wrap: wrap;
  }
}
</style>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { AMOUNT_IN_OPTIONS } from '../../utils/amountFormat'
import { useAmountIn } from '../../Composables/useAmountIn'

const showFilters = ref(false)
const selectedMetrics = ref(['Center Share Amount', 'Mother Sanction Amount', 'Available Amount'])
const tempMetric = ref('')
const { amountIn, amountInText, formatAmount } = useAmountIn('Lakh')
const groupBy = ref('state')
const selectedState = ref('')
const selectedYear = ref('')
const states = ref([])
const financialYears = ref([])
const reportData = ref([])
const years = ref([])
const sortDirection = ref('asc')

const displayMetrics = computed(() => selectedMetrics.value)

const groupedData = computed(() => {
  if (!reportData.value.length || !years.value.length) return []
  return reportData.value
})

function formatValue(value) {
  return formatAmount(value)
}

function getMetricClass(metric) {
  if (metric === 'Center Share Amount') return 'metric-area'
  if (metric === 'Mother Sanction Amount') return 'metric-production'
  if (metric === 'Available Amount') return 'metric-yield'
  return ''
}

function getMetricKey(metric) {
  const map = {
    'Center Share Amount': 'center_share_amount',
    'Mother Sanction Amount': 'mother_sanction_amount',
    'Available Amount': 'available_amount'
  }
  return map[metric] || ''
}

function getRowClass(item) {
  return item.is_total ? 'total-row' : ''
}

function toggleFilters() {
  showFilters.value = !showFilters.value
}

function addMetric() {
  if (tempMetric.value && !selectedMetrics.value.includes(tempMetric.value)) {
    selectedMetrics.value.push(tempMetric.value)
    tempMetric.value = ''
  }
}

function removeMetric(metric) {
  const index = selectedMetrics.value.indexOf(metric)
  if (index > -1) {
    selectedMetrics.value.splice(index, 1)
  }
}

function sortByBudgetHead() {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  // Implement sorting logic
}

async function applyFilters() {
  try {
    const params = new URLSearchParams()
    if (selectedState.value) params.append('state_id', selectedState.value)
    if (selectedYear.value) params.append('financial_year', selectedYear.value)
    params.append('group_by', groupBy.value)
    
    const response = await fetch(`/api/daily-sanction-time-series?${params}`)
    if (response.ok) {
      const data = await response.json()
      reportData.value = data.data || []
      years.value = data.years || []
    }
  } catch (error) {
    console.error('Error fetching report data:', error)
  }
}

function exportExcel() {
  // Implement Excel export
  alert('Excel export functionality will be implemented')
}

function exportCSV() {
  // Implement CSV export
  alert('CSV export functionality will be implemented')
}

async function loadInitialData() {
  try {
    const [statesRes, yearsRes] = await Promise.all([
      fetch('/api/states'),
      fetch('/api/daily-sanction-time-series')
    ])
    
    if (statesRes.ok) {
      states.value = await statesRes.json()
    }
    
    if (yearsRes.ok) {
      const data = await yearsRes.json()
      reportData.value = data.data || []
      years.value = data.years || []
      financialYears.value = data.years || []
    }
  } catch (error) {
    console.error('Error loading initial data:', error)
  }
}

onMounted(() => {
  loadInitialData()
})
</script>

