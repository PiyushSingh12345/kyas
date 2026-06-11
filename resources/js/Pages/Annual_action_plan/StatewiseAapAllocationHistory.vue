<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Statewise AAP Allocation History</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#"><i class="icon-home"></i></a>
              </li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item">
                <a href="#">Annual Action Plan</a>
              </li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item">
                <a href="#">Statewise AAP Allocation History</a>
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
                    <span>Allocation History Records</span>
                    <!-- <div class="d-flex gap-2">
                      <button 
                        class="btn btn-outline-primary btn-sm" 
                        @click="refreshData"
                        :disabled="isLoading"
                      >
                        <i class="fas fa-sync-alt" :class="{ 'fa-spin': isLoading }"></i> Refresh
                      </button>
                      <button 
                        class="btn btn-outline-success btn-sm" 
                        @click="exportData"
                        :disabled="isLoading"
                      >
                        <i class="fas fa-download"></i> Export
                      </button>
                    </div> -->
                  </div>
                </div>

                <div class="card-body">
                  <!-- Filters -->
                  <div class="row mb-3">
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="financialYear">Financial Year</label>
                        <select class="form-select form-select-sm" id="financialYear" v-model="filters.financial_year" @change="applyFilters">
                          <option value="">All Years</option>
                          <option value="2026-27">2026-27</option>
                          <option value="2025-26">2025-26</option>
                          <option value="2024-25">2024-25</option>
                          <option value="2023-24">2023-24</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="stateFilter">State</label>
                        <select class="form-select form-select-sm" id="stateFilter" v-model="filters.state_id" @change="applyFilters">
                          <option value="">All States</option>
                          <option v-for="state in states" :key="state.state_id" :value="state.state_id">
                            {{ state.state_name }}
                          </option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="pdFilter">Program Division</label>
                        <select class="form-select form-select-sm" id="pdFilter" v-model="filters.pd_id" @change="applyFilters">
                          <option value="">All Divisions</option>
                          <option v-for="pd in programDivisions" :key="pd.division_id" :value="pd.division_id">
                            {{ pd.division_name }}
                          </option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="actionType">Action Type</label>
                        <select class="form-select form-select-sm" id="actionType" v-model="filters.action_type" @change="applyFilters">
                          <option value="">All Actions</option>
                          <option value="UPDATE">Update</option>
                          <option value="DELETE">Delete</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label for="changedBy">Changed By</label>
                        <select class="form-select form-select-sm" id="changedBy" v-model="filters.changed_by" @change="applyFilters">
                          <option value="">All Users</option>
                          <option v-for="user in users" :key="user.id" :value="user.id">
                            {{ user.name }}
                          </option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="d-flex gap-1">
                          <button class="btn btn-outline-secondary btn-sm" @click="clearFilters">
                            <i class="fas fa-times"></i> Clear
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Loading Spinner -->
                  <div v-if="isLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </div>

                  <!-- Data Table -->
                  <div v-else class="table-responsive">
                    <table class="table table-striped table-hover">
                      <thead class="table-head-bg-primary">
                        <tr>
                          <th @click="sort('history_timestamp')" class="sortable">
                            History Timestamp
                            <i :class="getSortIcon('history_timestamp')" class="ms-1"></i>
                          </th>
                          <th @click="sort('financial_year')" class="sortable">
                            Financial Year
                            <i :class="getSortIcon('financial_year')" class="ms-1"></i>
                          </th>
                          <th @click="sort('state_id')" class="sortable">
                            State
                            <i :class="getSortIcon('state_id')" class="ms-1"></i>
                          </th>
                          <th @click="sort('pd_id')" class="sortable">
                            Program Division
                            <i :class="getSortIcon('pd_id')" class="ms-1"></i>
                          </th>
                          <th @click="sort('amount')" class="sortable">
                            Amount
                            <i :class="getSortIcon('amount')" class="ms-1"></i>
                          </th>
                          <th @click="sort('status')" class="sortable">
                            Status
                            <i :class="getSortIcon('status')" class="ms-1"></i>
                          </th>
                          <th>Remark</th>
                          <th @click="sort('action_type')" class="sortable">
                            Action Type
                            <i :class="getSortIcon('action_type')" class="ms-1"></i>
                          </th>
                          <th>Changed By</th>
                          <th @click="sort('created_at')" class="sortable">
                            Created At
                            <i :class="getSortIcon('created_at')" class="ms-1"></i>
                          </th>
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
                            <div v-if="record.state">
                              <div class="fw-bold">{{ record.state.state_name }}</div>
                            </div>
                            <span v-else class="text-muted">N/A</span>
                          </td>
                          <td>
                            <div v-if="record.program_division">
                              <div class="fw-bold">{{ record.program_division.division_name }}</div>
                            </div>
                            <span v-else class="text-muted">N/A</span>
                          </td>
                          <td class="currency-cell">
                            <span class="fw-bold text-success">
                              ₹ {{ formatNumber(record.amount) }}
                            </span>
                          </td>
                          <td>
                            <span class="badge" :class="getStatusBadgeClass(record.status)">
                              {{ getStatusText(record.status) }}
                            </span>
                          </td>
                          <td>
                            <span v-if="record.remark" class="text-muted">{{ record.remark }}</span>
                            <span v-else class="text-muted">-</span>
                          </td>
                          <td>
                            <span class="badge" :class="getActionTypeBadgeClass(record.action_type)">
                              {{ record.action_type }}
                            </span>
                          </td>
                          <td>
                            <span class="text-muted">{{ getUserName(record.changed_by) }}</span>
                          </td>
                          <td>
                            <span class="text-muted">{{ formatDateTime(record.created_at) }}</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <!-- No Data Message -->
                    <div v-if="historyRecords.length === 0" class="text-center py-4">
                      <div class="text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No history records found</p>
                      </div>
                    </div>
                  </div>

                  <!-- Pagination -->
                  <div v-if="pagination.total > 0" class="d-flex justify-content-between align-items-center mt-3">
                    <div class="d-flex align-items-center">
                      <span class="text-muted me-3">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                      </span>
                      <div class="d-flex align-items-center">
                        <label class="form-label me-2 mb-0">Per page:</label>
                        <select class="form-select form-select-sm" style="width: auto;" v-model="pagination.per_page" @change="changePerPage">
                          <option value="15">15</option>
                          <option value="25">25</option>
                          <option value="50">50</option>
                          <option value="100">100</option>
                        </select>
                      </div>
                    </div>
                    
                    <nav aria-label="Page navigation">
                      <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                          <button class="page-link" @click="prevPage" :disabled="pagination.current_page === 1">
                            <i class="fas fa-chevron-left"></i>
                          </button>
                        </li>
                        
                        <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === pagination.current_page }">
                          <button class="page-link" @click="goToPage(page)" :disabled="page === pagination.current_page">
                            {{ page }}
                          </button>
                        </li>
                        
                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                          <button class="page-link" @click="nextPage" :disabled="pagination.current_page === pagination.last_page">
                            <i class="fas fa-chevron-right"></i>
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
      <Footer />
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { usePage, router } from '@inertiajs/vue3'

export default {
  name: 'StatewiseAapAllocationHistory',
  components: {
    Header,
    Sidebar,
    Footer
  },
  setup() {
    const page = usePage()
    const historyRecords = ref([])
    const users = ref([])
    const states = ref([])
    const programDivisions = ref([])
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
      state_id: '',
      pd_id: '',
      action_type: '',
      changed_by: ''
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
        const params = new URLSearchParams({
          page: pageNumber,
          per_page: pagination.value.per_page,
          sort_field: sortField.value,
          sort_direction: sortDirection.value,
          ...filters
        })

        const response = await fetch(`/api/statewise-aap-allocation-history?${params}`)
        const data = await response.json()

        if (data.success) {
          historyRecords.value = data.data
          pagination.value = data.pagination
        } else {
          showMessage('Failed to fetch history data', 'danger')
        }
      } catch (error) {
        console.error('Error fetching history data:', error)
        showMessage('Error fetching history data', 'danger')
      } finally {
        isLoading.value = false
      }
    }

    const fetchUsers = async () => {
      try {
        const response = await fetch('/api/users')
        const data = await response.json()
        if (data.success) {
          users.value = data.data
        }
      } catch (error) {
        console.error('Error fetching users:', error)
      }
    }

    const fetchStates = async () => {
      try {
        const response = await fetch('/api/aap-states')
        const data = await response.json()
        if (Array.isArray(data)) {
          states.value = data
          console.log('States loaded:', data)
        } else if (data.success) {
          states.value = data.data
          console.log('States loaded:', data.data)
        }
      } catch (error) {
        console.error('Error fetching states:', error)
      }
    }

    const fetchProgramDivisions = async () => {
      try {
        const response = await fetch('/api/aap-program-divisions')
        const data = await response.json()
        if (Array.isArray(data)) {
          programDivisions.value = data
        } else if (data.success) {
          programDivisions.value = data.data
        }
      } catch (error) {
        console.error('Error fetching program divisions:', error)
      }
    }

    const applyFilters = () => {
      console.log('Applying filters:', filters)
      pagination.value.current_page = 1
      fetchHistoryData()
    }

    const clearFilters = () => {
      filters.financial_year = ''
      filters.state_id = ''
      filters.pd_id = ''
      filters.action_type = ''
      filters.changed_by = ''
      applyFilters()
    }

    const sort = (field) => {
      if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
      } else {
        sortField.value = field
        sortDirection.value = 'desc'
      }
      fetchHistoryData(pagination.value.current_page)
    }

    const getSortIcon = (field) => {
      if (sortField.value !== field) return 'fas fa-sort'
      return sortDirection.value === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'
    }

    const refreshData = () => {
      fetchHistoryData(pagination.value.current_page)
    }

    const exportData = () => {
      const params = new URLSearchParams({
        ...filters,
        sort_field: sortField.value,
        sort_direction: sortDirection.value,
        export: 'true'
      })
      
      window.open(`/api/statewise-aap-allocation-history/export?${params}`, '_blank')
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        pagination.value.current_page = page
        fetchHistoryData(page)
      }
    }

    const nextPage = () => {
      if (pagination.value.current_page < pagination.value.last_page) {
        goToPage(pagination.value.current_page + 1)
      }
    }

    const prevPage = () => {
      if (pagination.value.current_page > 1) {
        goToPage(pagination.value.current_page - 1)
      }
    }

    const changePerPage = () => {
      pagination.value.current_page = 1
      fetchHistoryData()
    }

    const visiblePages = computed(() => {
      const current = pagination.value.current_page
      const last = pagination.value.last_page
      const pages = []
      
      let start = Math.max(1, current - 2)
      let end = Math.min(last, current + 2)
      
      if (end - start < 4) {
        if (start === 1) {
          end = Math.min(last, start + 4)
        } else {
          start = Math.max(1, end - 4)
        }
      }
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })

    const formatDateTime = (dateTime) => {
      if (!dateTime) return '-'
      return new Date(dateTime).toLocaleString('en-IN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      })
    }

    const formatNumber = (number) => {
      if (!number) return '0.00'
      return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(number)
    }

    const getStatusBadgeClass = (status) => {
      return status === 1 ? 'bg-success' : 'bg-danger'
    }

    const getStatusText = (status) => {
      return status === 1 ? 'Active' : 'Inactive'
    }

    const getActionTypeBadgeClass = (actionType) => {
      switch (actionType) {
        case 'UPDATE': return 'bg-primary'
        case 'DELETE': return 'bg-danger'
        default: return 'bg-secondary'
      }
    }

    const getUserName = (userId) => {
      if (!userId || userId === 'system') return 'System'
      const user = users.value.find(u => u.id == userId)
      return user ? user.name : `User ${userId}`
    }

    // Initialize data on component mount
    onMounted(() => {
      fetchHistoryData()
      fetchUsers()
      fetchStates()
      fetchProgramDivisions()
    })

    return {
      historyRecords,
      users,
      states,
      programDivisions,
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
      exportData,
      formatDateTime,
      formatNumber,
      getStatusBadgeClass,
      getStatusText,
      getActionTypeBadgeClass,
      getUserName,
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

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
}

/* Badge styling */
.badge {
  font-size: 0.75em;
}

/* Pagination styling */
.pagination .page-link {
  color: #007bff;
  border-color: #dee2e6;
}

.pagination .page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

.pagination .page-item.disabled .page-link {
  color: #6c757d;
  background-color: #fff;
  border-color: #dee2e6;
}
</style>
