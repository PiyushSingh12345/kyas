<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
        <div class="container">
          <div class="page-inner allinsideform">
            <div class="page-header">
              <h3 class="fw-bold mb-3">Master Data </h3>
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
                  <a href="#">Mother Sanction History </a>
                </li>
              </ul>

            </div>
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <div class="card-title">Mother Sanction History</div>
                  </div>
                  <div class="card-body">
                    <!-- Flash Message -->
                    <div v-if="flashMessage.show" :class="`alert alert-${flashMessage.type} alert-dismissible fade show`" role="alert">
                      <i :class="flashMessage.icon"></i>
                      {{ flashMessage.message }}
                      <button type="button" class="btn-close" @click="hideFlashMessage" aria-label="Close"></button>
                    </div>

                    <!-- Loading State -->
                    <div v-if="isLoading" class="text-center py-4">
                      <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                      </div>
                      <p class="mt-2 text-muted">Loading mother sanction history...</p>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="alert alert-danger" role="alert">
                      <i class="fas fa-exclamation-triangle me-2"></i>
                      {{ error }}
                      <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchHistory">
                        <i class="fas fa-redo me-1"></i>Retry
                      </button>
                    </div>

                    <!-- Data Tables -->
                    <div v-else>
                      <div class="table-responsive">
                        <table class="table table-bordered table-head-bg-primary">
                          <thead>
                            <tr>
                              <th>Fy</th>
                              <th>State</th>
                              <th>MS NO</th>
                              <th>Date</th>
                              <th>SLS Details</th>
                              <th>Budget Head</th>
                              <th>Action Type</th>
                              <th>Changed By</th>
                              <th>History Timestamp</th>
                              <th>Change Description</th>
                            </tr>
                          </thead>
                          
                          <tbody>
                          <tr v-for="(item, index) in historyData" :key="`${item.id}-${index}`">
                            <td>{{ item.financial_year }}</td>
                            <td>{{ item.state?.name || '' }}</td>
                            <td>{{ item.ky_ms_no }}</td>
                            <td>{{ formatDate(item.sanction_date) }}</td>
                            <td>{{ item.sls_name }}</td>

                            <td>
                              <div class="budget-head-table">
                                <table class="table table-sm mb-0">
                                  <thead>
                                    <tr class="table-light">
                                      <th class="text-center">Budget Head</th>
                                      <th class="text-center">Category</th>
                                      <th class="text-center">Old MS Amount</th>
                                      <th class="text-center">New MS Amount</th>
                                      <th class="text-center">Old Available Fund</th>
                                      <th class="text-center">New Available Fund</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(budget, budgetIndex) in item.budget_heads" :key="budgetIndex">
                                      <td class="text-center">{{ budget.budget_head }}</td>
                                      <td class="text-center">{{ budget.category }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.old_mother_sanction_amount) }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.new_mother_sanction_amount) }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.old_available_fund) }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.new_available_fund) }}</td>
                                    </tr>
                                    <tr v-if="!item.budget_heads || item.budget_heads.length === 0">
                                      <td colspan="6" class="text-center text-muted">No budget heads available</td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                            </td>

                            <td class="text-center">
                              <span 
                                :class="{
                                  'badge bg-primary': item.action_type === 'CREATE',
                                  'badge bg-info': item.action_type === 'UPDATE',
                                  'badge bg-warning': item.action_type === 'REVISE',
                                  'badge bg-danger': item.action_type === 'CLOSE',
                                  'badge bg-secondary': item.action_type === 'DEACTIVATE',
                                  'badge bg-success': item.action_type === 'ACTIVATE'
                                }"
                              >
                                {{ item.action_type }}
                              </span>
                            </td>
                            <td>{{ item.changed_by || 'System' }}</td>
                            <td>{{ formatDateTime(item.history_timestamp) }}</td>
                            <td class="text-muted small">{{ item.change_description || '-' }}</td>
                          </tr>
                          
                          <tr v-if="historyData.length === 0">
                            <td colspan="10" class="text-center text-muted py-4">
                              <i class="fas fa-info-circle me-2"></i>
                              No mother sanction history available
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
        <Footer />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'

import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const historyData = ref([])
const isLoading = ref(false)
const error = ref(null)

// Flash message state
const flashMessage = ref({
  show: false,
  type: 'success',
  message: '',
  icon: ''
})

const showFlashMessage = (type, message, icon) => {
  flashMessage.value = {
    show: true,
    type,
    message,
    icon
  };
  
  setTimeout(() => {
    hideFlashMessage();
  }, 5000);
};

const hideFlashMessage = () => {
  flashMessage.value.show = false;
};

onMounted(async () => {
  await fetchHistory()
})

const fetchHistory = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const res = await fetch('/api/mother-sanction-history');
    if (res.ok) {
      const data = await res.json();
      historyData.value = data;
    } else {
      console.error('Failed to fetch history data');
      error.value = 'Failed to fetch history data from server';
    }
  } catch (err) {
    console.error('Error fetching history data:', err);
    error.value = 'Network error occurred while fetching history data';
  } finally {
    isLoading.value = false
  }
};

// Method to format date
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-IN');
};

// Method to format date and time
const formatDateTime = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('en-IN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
};

// Method to format currency
const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return '0.00';
  return parseFloat(amount).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
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

/* Table spacing and borders */
.table-bordered {
  border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
  border: 1px solid #dee2e6;
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

/* Badge styling */
.badge {
  padding: 0.35em 0.65em;
  font-size: 0.875em;
  font-weight: 600;
}

/* Flash message styling */
.alert {
  border-radius: 8px;
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
  font-weight: 500;
}

.alert-success {
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  color: #155724;
  border-left: 4px solid #28a745;
}

.alert-danger {
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
  color: #721c24;
  border-left: 4px solid #dc3545;
}

.alert i {
  margin-right: 8px;
  font-size: 1.1em;
}

.btn-close {
  opacity: 0.7;
  transition: opacity 0.2s;
}

.btn-close:hover {
  opacity: 1;
}
</style>

