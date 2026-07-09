<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Agency Release</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">LOA History</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">LOA History</div>
                </div>
                <div class="card-body">
                  <!-- Status filter -->
                  <div class="row mb-3">
                    <div class="col-md-3">
                      <label for="statusFilter" class="form-label">Status</label>
                      <select id="statusFilter" class="form-select" v-model="statusFilter" @change="applyFilters">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                      </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                      <button class="btn btn-sm btn-outline-secondary" @click="fetchLOAList">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                      </button>
                    </div>
                  </div>

                  <!-- Loading State -->
                  <div v-if="isLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading LOA history...</p>
                  </div>

                  <!-- Error State -->
                  <div v-else-if="error" class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ error }}
                    <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchLOAList">
                      <i class="fas fa-redo me-1"></i>Retry
                    </button>
                  </div>

                  <!-- Data Table -->
                  <div v-else>
                    <div ref="reportTableScrollWrapper" class="report-table-scroll-wrapper" @scroll="onTableWrapperScroll">
                    <div class="table-responsive">
                      <table class="table table-bordered table-head-bg-primary">
                        <thead>
                          <tr>
                            <th>Sanction Number</th>
                            <th>Date</th>
                            <th>Budget Head</th>
                            <th>Purpose of Grant</th>
                            <th>Program Division</th>
                            <th>Amount/Release/Expenditure</th>
                            <th>UT</th>
                            <th>Action Type</th>
                            <th>Changed By</th>
                            <th>History Timestamp</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr
                            v-for="item in filteredList"
                            :key="item.id"
                            :class="{ 'table-secondary': !item.status }"
                          >
                            <td>{{ item.sanction_number }}</td>
                            <td>{{ formatDate(item.date) }}</td>
                            <td>{{ item.budget_head }}</td>
                            <td>{{ item.purpose_of_grant }}</td>
                            <td>{{ item.program_division }}</td>
                            <td class="currency-cell">{{ formatCurrency(item.amount) }}</td>
                            <td>{{ item.ut }}</td>
                            <td class="text-center">
                              <span
                                :class="{
                                  'badge bg-primary': item.action_type === 'CREATE',
                                  'badge bg-info': item.action_type === 'UPDATE',
                                  'badge bg-warning': item.action_type === 'REVISE',
                                  'badge bg-danger': item.action_type === 'CLOSE',
                                }"
                              >
                                {{ item.action_type }}
                              </span>
                            </td>
                            <td>{{ item.changed_by || 'System' }}</td>
                            <td>{{ formatDateTime(item.history_timestamp) }}</td>
                          </tr>

                          <tr v-if="filteredList.length === 0">
                            <td colspan="10" class="text-center text-muted py-4">
                              <i class="fas fa-info-circle me-2"></i>
                              No LOA history records found
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
import { ref, computed, onMounted } from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { useFixedHorizontalScroll } from '../../Composables/useFixedHorizontalScroll'

const loaList = ref([])
const isLoading = ref(false)
const error = ref(null)
const statusFilter = ref('')

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

const filteredList = computed(() => {
  if (!statusFilter.value) {
    return loaList.value
  }
  const statusNum = parseInt(statusFilter.value, 10)
  return loaList.value.filter((item) => item.status === statusNum)
})

const applyFilters = () => {}

onMounted(async () => {
  await fetchLOAList()
  refreshFixedHorizontalScroll()
})

const fetchLOAList = async () => {
  isLoading.value = true
  error.value = null

  try {
    const res = await fetch('/api/agency-release-loa-history')
    if (res.ok) {
      const data = await res.json()
      loaList.value = data
    } else {
      console.error('Failed to fetch LOA data')
      error.value = 'Failed to fetch LOA history from server'
    }
  } catch (err) {
    console.error('Error fetching LOA data:', err)
    error.value = 'Network error occurred while fetching LOA history'
  } finally {
    isLoading.value = false
    refreshFixedHorizontalScroll()
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-IN')
}

const formatDateTime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleString('en-IN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return '0.00'
  return parseFloat(amount).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}
</script>

<style scoped>
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

.table tbody tr:hover {
  background-color: rgba(0, 123, 255, 0.05);
}

.text-muted {
  color: #6c757d !important;
}

.currency-cell {
  text-align: right;
  font-family: 'Courier New', monospace;
  font-weight: 500;
}

.table-bordered {
  border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
  border: 1px solid #dee2e6;
}

.badge {
  padding: 0.35em 0.65em;
  font-size: 0.875em;
  font-weight: 600;
}
</style>
