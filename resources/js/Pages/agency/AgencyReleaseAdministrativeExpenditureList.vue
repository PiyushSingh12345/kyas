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
                <a href="#">Administrative Expenditure List</a>
              </li>
            </ul>
          </div>
          
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Administrative Expenditure List</div>
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
                    <p class="mt-2 text-muted">Loading Administrative Expenditure data...</p>
                  </div>

                  <!-- Error State -->
                  <div v-else-if="error" class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ error }}
                    <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchAdminExpList">
                      <i class="fas fa-redo me-1"></i>Retry
                    </button>
                  </div>

                  <!-- Data Table -->
                  <div v-else>
                    <div class="table-responsive">
                      <table class="table table-bordered table-head-bg-primary">
                        <thead>
                          <tr>
                            <th>Sanction Number</th>
                            <th>Date</th>
                            <th>Budget Head</th>
                            <th>Purpose of Grant</th>
                            <th>Program Division</th>
                            <th>Amount</th>
                            <th>Agency/Vendor</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Created At</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, index) in adminExpList" :key="item.id" :class="{ 'table-secondary': !item.status }">
                            <td>{{ item.sanction_number }}</td>
                            <td>{{ formatDate(item.date) }}</td>
                            <td>{{ item.budget_head }}</td>
                            <td>{{ item.purpose_of_grant }}</td>
                            <td>{{ item.program_division }}</td>
                            <td class="currency-cell">{{ formatCurrency(item.amount) }}</td>
                            <td>{{ item.agency_vendor }}</td>
                            <td class="text-center status-column">
                              <div class="d-flex justify-content-center align-items-center gap-2">
                                <button 
                                  class="btn btn-sm btn-secondary"
                                  @click="handleClose(item, index)"
                                  title="Close"
                                  :disabled="!item.status"
                                >
                                  Close
                                </button>
                                <button 
                                  class="btn btn-sm btn-primary"
                                  @click="handleRevise(item, index)"
                                  title="Revise"
                                  :disabled="!item.status"
                                >
                                  Revise
                                </button>
                              </div>
                            </td>
                            <td class="text-center">
                              <span :class="item.status ? 'badge bg-success' : 'badge bg-secondary'">
                                {{ item.status ? 'Active' : 'Inactive' }}
                              </span>
                            </td>
                            <td>{{ formatDateTime(item.created_at) }}</td>
                          </tr>
                          
                          <tr v-if="adminExpList.length === 0">
                            <td colspan="10" class="text-center text-muted py-4">
                              <i class="fas fa-info-circle me-2"></i>
                              No Administrative Expenditure data available
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

    <!-- Close Confirmation Dialog -->
    <div v-if="showCloseDialog" class="modal fade show d-block" tabindex="-1" role="dialog" style="z-index: 1055;" @click="closeCloseDialog">
      <div class="modal-backdrop fade show" style="z-index: 1050; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.5);"></div>
      <div class="modal-dialog modal-dialog-centered" role="document" style="z-index: 1055; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;" @click.stop>
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Close Action</h5>
            <button type="button" class="btn-close" @click="closeCloseDialog"></button>
          </div>
          <div class="modal-body">
            <p>Do you really want to proceed?</p>
            <p class="text-muted small mt-2">This will set the record status to inactive.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeCloseDialog">Cancel</button>
            <button type="button" class="btn btn-primary" @click="confirmClose">Proceed</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Revise Confirmation Dialog -->
    <div v-if="showReviseDialog" class="modal fade show d-block" tabindex="-1" role="dialog" style="z-index: 1055;" @click="closeReviseDialog">
      <div class="modal-backdrop fade show" style="z-index: 1050; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.5);"></div>
      <div class="modal-dialog modal-dialog-centered" role="document" style="z-index: 1055; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;" @click.stop>
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Revise Action</h5>
            <button type="button" class="btn-close" @click="closeReviseDialog"></button>
          </div>
          <div class="modal-body">
            <p>Do you really want to proceed?</p>
            <p class="text-muted small mt-2">This will make the older data status inactive and open the Administrative Expenditure form with these data prefilled.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeReviseDialog">Cancel</button>
            <button type="button" class="btn btn-primary" @click="confirmRevise">Proceed</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const adminExpList = ref([])
const isLoading = ref(false)
const error = ref(null)
const showCloseDialog = ref(false)
const closeItem = ref(null)
const closeIndex = ref(null)
const showReviseDialog = ref(false)
const reviseItem = ref(null)
const reviseIndex = ref(null)

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
  await fetchAdminExpList()
})

const fetchAdminExpList = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const res = await fetch('/api/agency-release-administrative-expenditure-list');
    if (res.ok) {
      const data = await res.json();
      adminExpList.value = data;
    } else {
      console.error('Failed to fetch Administrative Expenditure data');
      error.value = 'Failed to fetch Administrative Expenditure data from server';
    }
  } catch (err) {
    console.error('Error fetching Administrative Expenditure data:', err);
    error.value = 'Network error occurred while fetching Administrative Expenditure data';
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
    minute: '2-digit'
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

// Method to handle close action - show dialog
const handleClose = (item, index) => {
  closeItem.value = item;
  closeIndex.value = index;
  showCloseDialog.value = true;
};

// Method to close the close dialog
const closeCloseDialog = () => {
  showCloseDialog.value = false;
  closeItem.value = null;
  closeIndex.value = null;
};

// Method to confirm close action
const confirmClose = async () => {
  if (!closeItem.value) {
    closeCloseDialog();
    return;
  }

  try {
    const response = await fetch('/api/agency-release/update-status', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        id: closeItem.value.id,
        type: 'administrative-expenditure',
        action: 'close'
      })
    });

    if (response.ok) {
      await fetchAdminExpList();
      showFlashMessage('success', 'Record closed successfully. Status set to inactive.', 'fas fa-check-circle');
    } else {
      const errorData = await response.json().catch(() => ({}));
      showFlashMessage('danger', errorData.message || 'Failed to close record. Please try again.', 'fas fa-exclamation-triangle');
    }
  } catch (error) {
    console.error('Error closing record:', error);
    showFlashMessage('danger', 'An error occurred while closing the record. Please try again.', 'fas fa-exclamation-triangle');
  } finally {
    closeCloseDialog();
  }
};

// Method to handle revise action - show dialog
const handleRevise = (item, index) => {
  reviseItem.value = item;
  reviseIndex.value = index;
  showReviseDialog.value = true;
};

// Method to close the revise dialog
const closeReviseDialog = () => {
  showReviseDialog.value = false;
  reviseItem.value = null;
  reviseIndex.value = null;
};

// Method to confirm revise action
const confirmRevise = async () => {
  if (!reviseItem.value) {
    closeReviseDialog();
    return;
  }

  try {
    const response = await fetch('/api/agency-release/update-status', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        id: reviseItem.value.id,
        type: 'administrative-expenditure',
        action: 'revise'
      })
    });

    if (response.ok) {
      const result = await response.json();
      
      // Create query parameters for prefilling the form
      const queryParams = new URLSearchParams({
        revise: 'true',
        sanctionNumber: result.data.sanction_number || '',
        date: result.data.date || '',
        budgetHead: result.data.budget_head || '',
        purposeOfGrant: result.data.purpose_of_grant || '',
        programDivision: result.data.program_division_id || '',
        amount: result.data.amount || '',
        agencyVendor: result.data.agency_vendor || ''
      });
      
      // Redirect to the Administrative Expenditure form with prefilled data
      window.location.href = `/agency-release-administrative-expenditure?${queryParams.toString()}`;
    } else {
      const errorData = await response.json().catch(() => ({}));
      showFlashMessage('danger', errorData.message || 'Failed to revise record. Please try again.', 'fas fa-exclamation-triangle');
      closeReviseDialog();
    }
  } catch (error) {
    console.error('Error revising record:', error);
    showFlashMessage('danger', 'An error occurred while revising the record. Please try again.', 'fas fa-exclamation-triangle');
    closeReviseDialog();
  }
};
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

/* Status column styling */
.status-column {
  min-width: 200px;
}

.gap-2 {
  gap: 0.5rem;
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
</style>

