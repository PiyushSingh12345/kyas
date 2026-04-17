<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Budget Phase History</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#"><i class="icon-home"></i></a>
              </li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item">
                <a href="#">Budget Allocation</a>
              </li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item">
                <a href="#">Budget Phase History</a>
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
                  <div class="card-title d-flex justify-content-between align-items-center">
                    <span>Budget Phase History Records</span>
                    <!-- <div class="d-flex gap-2">
                      <button class="btn btn-sm btn-outline-primary" @click="refreshData">
                        <i class="fas fa-sync-alt"></i> Refresh
                      </button>
                      <button class="btn btn-sm btn-outline-secondary" @click="exportData">
                        <i class="fas fa-download"></i> Export
                      </button>
                    </div> -->
                  </div>
                </div>

                <div class="card-body">
                  <!-- Filters -->
                  <div class="row mb-3">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="financialYearFilter">Financial Year</label>
                        <select class="form-select" id="financialYearFilter" v-model="filters.financial_year" @change="applyFilters">
                          <option value="">All Years</option>
                          <option value="2025-26">2025-26</option>
                          <option value="2024-25">2024-25</option>
                          <option value="2023-24">2023-24</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="budgetPhaseFilter">Budget Phase</label>
                        <select class="form-select" id="budgetPhaseFilter" v-model="filters.budget_phase" @change="applyFilters">
                          <option value="">All Phases</option>
                          <option value="BE">BE</option>
                          <option value="RE">RE</option>
                          <option value="FE">FE</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="actionTypeFilter">Action Type</label>
                        <select class="form-select" id="actionTypeFilter" v-model="filters.action_type" @change="applyFilters">
                          <option value="">All Actions</option>
                          <option value="UPDATE">UPDATE</option>
                          <option value="DELETE">DELETE</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="budgetHeadFilter">Budget Head</label>
                        <select class="form-select" id="budgetHeadFilter" v-model="filters.budget_head_id" @change="applyFilters">
                          <option value="">All Budget Heads</option>
                          <option v-for="budgetHead in budgetHeads" :key="budgetHead.id" :value="budgetHead.id">
                            {{ budgetHead.budget }} - {{ budgetHead.description }}
                          </option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- Second Row of Filters -->
                  <div class="row mb-3">
                    <div class="col-md-3">
                      <div class="form-group">
                        <label for="changedByFilter">Changed By</label>
                        <select class="form-select" id="changedByFilter" v-model="filters.changed_by" @change="applyFilters">
                          <option value="">All Users</option>
                          <option v-for="user in users" :key="user.id" :value="user.id">
                            {{ user.name }}
                          </option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- Clear Filters Button -->
                  <div class="row mb-3">
                    <div class="col-12">
                      <button class="btn btn-sm btn-outline-warning" @click="clearFilters">
                        <i class="fas fa-times"></i> Clear All Filters
                      </button>
                    </div>
                  </div>

                  <!-- Data Table -->
                  <div class="table-responsive">
                    <div v-if="isLoading" class="text-center py-4">
                      <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                      </div>
                      <p class="mt-2">Loading history records...</p>
                    </div>

                    <div v-else-if="historyRecords.length === 0" class="text-center py-4">
                      <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>No history records found</strong>
                        <br>No budget phase history records match your current filters.
                      </div>
                    </div>

                    <table v-else class="table table-bordered table-head-bg-primary">
                      <thead>
                        <tr>
                          <th @click="sort('history_timestamp')" class="sortable">
                            History Timestamp
                            <i :class="getSortIcon('history_timestamp')" class="ms-1"></i>
                          </th>
                          <th @click="sort('financial_year')" class="sortable">
                            Financial Year
                            <i :class="getSortIcon('financial_year')" class="ms-1"></i>
                          </th>
                          <th @click="sort('budget_phase')" class="sortable">
                            Budget Phase
                            <i :class="getSortIcon('budget_phase')" class="ms-1"></i>
                          </th>
                          <th>Budget Head</th>
                          <th @click="sort('budget_amount')" class="sortable">
                            Budget Amount <small class="text-capitalize">(₹ In Lakhs)</small>
                            <i :class="getSortIcon('budget_amount')" class="ms-1"></i>
                          </th>
                          <!-- <th @click="sort('status')" class="sortable">
                            Status
                            <i :class="getSortIcon('status')" class="ms-1"></i>
                          </th> -->
                          <!-- <th @click="sort('draft_flag')" class="sortable">
                            Draft Flag
                            <i :class="getSortIcon('draft_flag')" class="ms-1"></i>
                          </th> -->
                          <th @click="sort('action_type')" class="sortable">
                            Action Type
                            <i :class="getSortIcon('action_type')" class="ms-1"></i>
                          </th>
                          <th>Changed By</th>
                          <th @click="sort('created_at')" class="sortable">
                            Created At
                            <i :class="getSortIcon('created_at')" class="ms-1"></i>
                          </th>
                          <!-- <th @click="sort('updated_at')" class="sortable">
                            Updated At
                            <i :class="getSortIcon('updated_at')" class="ms-1"></i>
                          </th> -->
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="record in historyRecords" :key="record.history_id">
                          <td>
                            <span class="badge bg-info">
                              {{ formatDateTime(record.history_timestamp) }}
                            </span>
                          </td>
                          <td>
                            <span class="fw-bold">{{ record.financial_year }}</span>
                          </td>
                          <td>
                            <span class="badge" :class="getPhaseBadgeClass(record.budget_phase)">
                              {{ record.budget_phase }}
                            </span>
                          </td>
                          <td>
                            <div v-if="record.budget_head">
                              <div class="fw-bold">{{ record.budget_head.budget }}</div>
                              <small class="text-muted">{{ record.budget_head.description }}</small>
                            </div>
                            <span v-else class="text-muted">N/A</span>
                          </td>
                          <td class="currency-cell">
                            <span class="fw-bold text-success">
                              ₹ {{ formatNumber(record.budget_amount) }}
                            </span>
                          </td>
                          <!-- <td>
                            <span class="badge" :class="getStatusBadgeClass(record.status)">
                              {{ getStatusText(record.status) }}
                            </span>
                          </td> -->
                          <!-- <td>
                            <span class="badge" :class="getDraftFlagBadgeClass(record.draft_flag)">
                              {{ getDraftFlagText(record.draft_flag) }}
                            </span>
                          </td> -->
                          <td>
                            <span class="badge" :class="getActionTypeBadgeClass(record.action_type)">
                              {{ record.action_type }}
                            </span>
                          </td>
                          <td>
                            <div v-if="record.changed_by_user">
                              <div class="fw-bold">{{ record.changed_by_user.name }}</div>
                              <small class="text-muted">{{ record.changed_by_user.email }}</small>
                            </div>
                            <span v-else class="text-muted">N/A</span>
                          </td>
                          <td>
                            <small>{{ formatDateTime(record.created_at) }}</small>
                          </td>
                          <!-- <td>
                            <small>{{ formatDateTime(record.updated_at) }}</small>
                          </td> -->
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Pagination -->
                  <div v-if="historyRecords.length > 0" class="mt-3">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="d-flex align-items-center">
                        <span class="me-2">Show</span>
                        <select 
                          class="form-select form-select-sm" 
                          style="width: auto;"
                          :value="pagination.per_page"
                          @change="changePerPage(parseInt($event.target.value))"
                        >
                          <option value="5">5</option>
                          <option value="10">10</option>
                          <option value="15">15</option>
                          <option value="25">25</option>
                          <option value="50">50</option>
                        </select>
                        <span class="ms-2">entries</span>
                      </div>
                      
                      <div class="d-flex align-items-center">
                        <span class="me-3">
                          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                        </span>
                        
                        <nav v-if="pagination.last_page > 1">
                          <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                              <button class="page-link" @click="goToPage(1)" :disabled="pagination.current_page === 1">
                                <i class="fas fa-angle-double-left"></i>
                              </button>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                              <button class="page-link" @click="prevPage" :disabled="pagination.current_page === 1">
                                <i class="fas fa-angle-left"></i>
                              </button>
                            </li>
                            
                            <li v-for="link in visiblePages" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                              <button v-if="link.url" class="page-link" @click="goToPage(link.label)" v-html="link.label"></button>
                              <span v-else class="page-link" v-html="link.label"></span>
                            </li>
                            
                            <li class="page-item" :class="{ disabled: !pagination.has_more_pages }">
                              <button class="page-link" @click="nextPage" :disabled="!pagination.has_more_pages">
                                <i class="fas fa-angle-right"></i>
                              </button>
                            </li>
                            <li class="page-item" :class="{ disabled: !pagination.has_more_pages }">
                              <button class="page-link" @click="goToPage(pagination.last_page)" :disabled="!pagination.has_more_pages">
                                <i class="fas fa-angle-double-right"></i>
                              </button>
                            </li>
                          </ul>
                        </nav>
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

<script>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { ref, reactive, onMounted, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export default {
  name: 'BudgetPhaseHistory',
  components: {
    Header,
    Sidebar,
    Footer
  },
  setup() {
    const page = usePage()
    const historyRecords = ref([])
    const users = ref([])
    const budgetHeads = ref([])
    const isLoading = ref(false)
    const message = ref('')
    const messageType = ref('success')
    
    // Pagination state
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
      from: 0,
      to: 0,
      has_more_pages: false,
      prev_page_url: null,
      next_page_url: null,
      links: []
    })
    
    const filters = reactive({
      financial_year: '',
      budget_phase: '',
      action_type: '',
      changed_by: '',
      budget_head_id: ''
    })
    
    const sortField = ref('history_timestamp')
    const sortDirection = ref('desc')

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

    const fetchHistoryData = async (pageNumber = 1) => {
      isLoading.value = true
      try {
        const params = new URLSearchParams()
        
        // Add filters
        Object.keys(filters).forEach(key => {
          if (filters[key]) {
            params.append(key, filters[key])
          }
        })
        
        // Add sorting
        params.append('sort_field', sortField.value)
        params.append('sort_direction', sortDirection.value)
        
        // Add pagination
        params.append('page', pageNumber)
        params.append('per_page', pagination.value.per_page)

        const response = await fetch(`/api/budget-phase-history?${params.toString()}`)
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        historyRecords.value = data.data || []
        
        // Update pagination info
        if (data.pagination) {
          pagination.value = data.pagination
        }
      } catch (error) {
        console.error('Error fetching history data:', error)
        showMessage('Error fetching history data. Please try again.', 'danger')
      } finally {
        isLoading.value = false
      }
    }

    const fetchUsers = async () => {
      try {
        const response = await fetch('/api/users')
        if (response.ok) {
          const data = await response.json()
          users.value = data.data || []
        }
      } catch (error) {
        console.error('Error fetching users:', error)
      }
    }

    const fetchBudgetHeads = async () => {
      try {
        const response = await fetch('/api/budget-heads-with-history')
        if (response.ok) {
          const data = await response.json()
          budgetHeads.value = data.data || []
        }
      } catch (error) {
        console.error('Error fetching budget heads:', error)
      }
    }

    const applyFilters = () => {
      fetchHistoryData()
    }

    const clearFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      fetchHistoryData()
    }

    const sort = (field) => {
      if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
      } else {
        sortField.value = field
        sortDirection.value = 'asc'
      }
      fetchHistoryData()
    }

    const getSortIcon = (field) => {
      if (sortField.value !== field) {
        return 'fas fa-sort text-muted'
      }
      return sortDirection.value === 'asc' ? 'fas fa-sort-up text-primary' : 'fas fa-sort-down text-primary'
    }

    const refreshData = () => {
      fetchHistoryData(pagination.value.current_page)
      showMessage('Data refreshed successfully!', 'success')
    }

    const loadMoreData = () => {
      // For now, just refresh the data
      // In a real implementation, you might want to implement pagination
      fetchHistoryData(pagination.value.current_page)
    }

    // Pagination functions
    const goToPage = (pageNumber) => {
      if (pageNumber >= 1 && pageNumber <= pagination.value.last_page) {
        fetchHistoryData(pageNumber)
      }
    }

    const nextPage = () => {
      if (pagination.value.has_more_pages) {
        goToPage(pagination.value.current_page + 1)
      }
    }

    const prevPage = () => {
      if (pagination.value.current_page > 1) {
        goToPage(pagination.value.current_page - 1)
      }
    }

    const changePerPage = (newPerPage) => {
      pagination.value.per_page = newPerPage
      fetchHistoryData(1) // Reset to first page
    }

    // Computed property for visible pages (matching existing project pattern)
    const visiblePages = computed(() => {
      const pages = []
      const total = pagination.value.last_page
      const current = pagination.value.current_page
      
      // Always show first page
      pages.push({ label: '1', active: current === 1, url: current !== 1 })
      
      // Show pages around current page
      const start = Math.max(2, current - 1)
      const end = Math.min(total - 1, current + 1)
      
      if (start > 2) {
        pages.push({ label: '...', active: false, url: false })
      }
      
      for (let i = start; i <= end; i++) {
        if (i > 1 && i < total) {
          pages.push({ label: i.toString(), active: current === i, url: current !== i })
        }
      }
      
      if (end < total - 1) {
        pages.push({ label: '...', active: false, url: false })
      }
      
      // Always show last page if there are more than 1 page
      if (total > 1) {
        pages.push({ label: total.toString(), active: current === total, url: current !== total })
      }
      
      return pages
    })

    const exportData = () => {
      // Create CSV content
      const headers = [
        'History Timestamp', 'Financial Year', 'Budget Phase', 'Budget Head', 
        'Budget Amount', 'Status', 'Draft Flag', 'Action Type', 'Changed By', 
        'Created At', 'Updated At'
      ]

      const sanitizeCsvCell = (value) => {
        // CSV/Formula injection mitigation for spreadsheet programs (Excel, LibreOffice).
        // If a cell starts with: = + - @ (after leading whitespace), prefix with apostrophe.
        const cellString = value == null ? '' : String(value)
        const trimmedStart = cellString.replace(/^\s+/, '')
        if (trimmedStart && ['=', '+', '-', '@'].includes(trimmedStart[0])) {
          return "'" + cellString
        }
        return cellString
      }
      
      const csvContent = [
        headers.join(','),
        ...historyRecords.value.map(record => [
          sanitizeCsvCell(formatDateTime(record.history_timestamp)),
          sanitizeCsvCell(record.financial_year),
          sanitizeCsvCell(record.budget_phase),
          record.budget_head ? `"${sanitizeCsvCell(record.budget_head.budget)}"` : 'N/A',
          sanitizeCsvCell(record.budget_amount),
          sanitizeCsvCell(getStatusText(record.status)),
          sanitizeCsvCell(getDraftFlagText(record.draft_flag)),
          sanitizeCsvCell(record.action_type),
          record.changed_by_user ? `"${sanitizeCsvCell(record.changed_by_user.name)}"` : 'N/A',
          sanitizeCsvCell(formatDateTime(record.created_at)),
          sanitizeCsvCell(formatDateTime(record.updated_at))
        ].join(','))
      ].join('\n')

      // Download CSV
      const blob = new Blob([csvContent], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `budget_phase_history_${new Date().toISOString().split('T')[0]}.csv`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      window.URL.revokeObjectURL(url)
      
      showMessage('Data exported successfully!', 'success')
    }

    // Utility functions
    const formatDateTime = (dateString) => {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleString('en-IN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      })
    }

    const formatNumber = (number) => {
      if (!number) return '0'
      return parseFloat(number).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
    }

    const getPhaseBadgeClass = (phase) => {
      switch (phase) {
        case 'BE': return 'bg-primary'
        case 'RE': return 'bg-warning'
        case 'FE': return 'bg-success'
        default: return 'bg-secondary'
      }
    }

    const getStatusBadgeClass = (status) => {
      return status === 1 ? 'bg-success' : 'bg-danger'
    }

    const getStatusText = (status) => {
      return status === 1 ? 'Active' : 'Inactive'
    }

    const getDraftFlagBadgeClass = (draftFlag) => {
      return draftFlag === 1 ? 'bg-warning' : 'bg-info'
    }

    const getDraftFlagText = (draftFlag) => {
      return draftFlag === 1 ? 'Final' : 'Draft'
    }

    const getActionTypeBadgeClass = (actionType) => {
      switch (actionType) {
        case 'UPDATE': return 'bg-primary'
        case 'DELETE': return 'bg-danger'
        default: return 'bg-secondary'
      }
    }

    // Initialize data on component mount
    onMounted(() => {
      fetchHistoryData()
      fetchUsers()
      fetchBudgetHeads()
    })

    return {
      historyRecords,
      users,
      budgetHeads,
      isLoading,
      message,
      messageType,
      pagination,
      filters,
      sortField,
      sortDirection,
      clearMessage,
      applyFilters,
      clearFilters,
      sort,
      getSortIcon,
      refreshData,
      loadMoreData,
      exportData,
      formatDateTime,
      formatNumber,
      getPhaseBadgeClass,
      getStatusBadgeClass,
      getStatusText,
      getDraftFlagBadgeClass,
      getDraftFlagText,
      getActionTypeBadgeClass,
      goToPage,
      nextPage,
      prevPage,
      changePerPage,
      visiblePages
    }
  }
}
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

/* Sortable column styling */
.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.table th.sortable {
  position: relative;
}

.table th.sortable i {
  font-size: 0.8em;
}

/* Badge styling */
.badge {
  font-size: 0.75em;
}

/* Table responsive */
.table-responsive {
  max-height: 70vh;
  overflow-y: auto;
}

/* Form styling */
.form-group label {
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.card-title {
  font-weight: 600;
}

.alert {
  border-radius: 0.5rem;
}

.btn {
  border-radius: 0.375rem;
}

/* Table improvements */
.table th {
  vertical-align: middle;
  white-space: nowrap;
}

.table td {
  vertical-align: middle;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .table-responsive {
    font-size: 0.875rem;
  }
}

</style>
