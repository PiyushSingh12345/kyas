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
                <a href="#">TSA List</a>
              </li>
            </ul>
          </div>
          
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">TSA List</div>
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
                    <p class="mt-2 text-muted">Loading TSA data...</p>
                  </div>

                  <!-- Error State -->
                  <div v-else-if="error" class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ error }}
                    <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchTSAList">
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
                            <th>Amount/Release</th>
                            <th>Expenditure</th>
                            <th>Central Implementing Agency</th>
                            <th>Remark</th>
                            <th>Action</th>
                            <th>Created At</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, index) in tsaList" :key="item.id" :class="{ 'table-secondary': !item.status }">
                            <td>{{ item.sanction_number }}</td>
                            <td>{{ formatDate(item.date) }}</td>
                            <td>{{ item.budget_head }}</td>
                            <td>{{ item.purpose_of_grant }}</td>
                            <td>{{ item.program_division }}</td>
                            <td class="currency-cell">{{ formatCurrency(item.amount) }}</td>
                            <td class="currency-cell">{{ formatCurrency(item.expenditure) }}</td>
                            <td>{{ item.central_implementing_agency }}</td>
                            <td>{{ item.remark }}</td>
                            <td class="text-center">
                              <div class="d-flex justify-content-center gap-1">
                                <button
                                  class="btn btn-sm btn-primary"
                                  @click="handleEdit(item)"
                                  title="Edit"
                                >
                                  <i class="fas fa-edit"></i>
                                </button>
                                <button
                                  class="btn btn-sm btn-danger"
                                  @click="handleDelete(item)"
                                  title="Delete"
                                >
                                  <i class="fas fa-trash"></i>
                                </button>
                              </div>
                            </td>
                            <td>{{ formatDateTime(item.created_at) }}</td>
                          </tr>
                          
                          <tr v-if="tsaList.length === 0">
                            <td colspan="11" class="text-center text-muted py-4">
                              <i class="fas fa-info-circle me-2"></i>
                              No TSA data available
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
            <p class="text-muted small mt-2">This will make the older data status inactive and open the TSA form with these data prefilled.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeReviseDialog">Cancel</button>
            <button type="button" class="btn btn-primary" @click="confirmRevise">Proceed</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Edit Modal -->
    <div v-if="showEditDialog" class="modal fade show d-block" tabindex="-1" role="dialog" style="z-index: 1055;" @click="closeEditDialog">
      <div class="modal-backdrop fade show" style="z-index: 1050; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.5);"></div>
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document" style="z-index: 1055; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0; max-height: 90vh;" @click.stop>
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit TSA Record</h5>
            <button type="button" class="btn-close" @click="closeEditDialog"></button>
          </div>
          <form @submit.prevent="submitEdit">
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="editSanctionNumber">Sanction Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="editSanctionNumber" v-model="editForm.sanctionNumber" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="editDate">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="editDate" v-model="editForm.date" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="editBudgetHead">Budget Head <span class="text-danger">*</span></label>
                    <select class="form-select" id="editBudgetHead" v-model="editForm.budgetHead" required>
                      <option value="">--- Select Budget Head ---</option>
                      <option v-for="head in budgetHeads" :key="head.id" :value="head.budget">
                        {{ head.budget }} - {{ head.description }}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="editProgramDivision">Program Division <span class="text-danger">*</span></label>
                    <select class="form-select" id="editProgramDivision" v-model="editForm.programDivision" required>
                      <option value="">--- Select Program Division ---</option>
                      <option v-for="division in programDivisions" :key="division.division_id" :value="division.division_id">
                        {{ division.division_name }}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="editPurposeOfGrant">Purpose Of Grant <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="editPurposeOfGrant" v-model="editForm.purposeOfGrant" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="editBalancedFund">Balanced Fund Amount</label>
                    <input type="number" class="form-control" id="editBalancedFund" :value="editBalancedFundAmount" disabled>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="editAmount">Amount <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" :class="{ 'is-invalid': editAmountExceedsBalance }" id="editAmount" v-model="editForm.amount" step="0.01" min="0" required>
                    <div v-if="editAmountExceedsBalance" class="invalid-feedback">
                      Amount cannot exceed Balanced Fund Amount
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="editExpenditure">Expenditure</label>
                    <input
                      type="number"
                      class="form-control"
                      :class="{ 'is-invalid': editExpenditureExceedsAmount }"
                      id="editExpenditure"
                      v-model="editForm.expenditure"
                      step="0.01"
                      min="0"
                      :max="editForm.amount !== '' && !isNaN(parseFloat(editForm.amount)) ? parseFloat(editForm.amount) : undefined"
                    >
                    <div v-if="editExpenditureExceedsAmount" class="invalid-feedback">
                      Expenditure cannot exceed Amount (₹{{ parseFloat(editForm.amount).toFixed(2) }} lakhs)
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="editCentralAgency">Central Implementing Agency <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="editCentralAgency" v-model="editForm.centralImplementingAgency" required>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group mb-3">
                    <label for="editRemark">Remark</label>
                    <textarea
                      class="form-control"
                      id="editRemark"
                      v-model="editForm.remark"
                      placeholder="Enter Remark"
                      rows="3"
                    ></textarea>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label class="d-block mb-2">NER</label>
                    <div class="form-check">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        id="editIsNer"
                        v-model="editForm.isNer"
                      >
                      <label class="form-check-label" for="editIsNer">
                        North Eastern Region (NER)
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeEditDialog">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="isSavingEdit || editAmountExceedsBalance || editExpenditureExceedsAmount">
                <span v-if="isSavingEdit" class="spinner-border spinner-border-sm me-1" role="status"></span>
                Update
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div v-if="showDeleteDialog" class="modal fade show d-block" tabindex="-1" role="dialog" style="z-index: 1055;" @click="closeDeleteDialog">
      <div class="modal-backdrop fade show" style="z-index: 1050; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.5);"></div>
      <div class="modal-dialog modal-dialog-centered" role="document" style="z-index: 1055; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;" @click.stop>
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="btn-close" @click="closeDeleteDialog"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this TSA record?</p>
            <p v-if="deleteItem" class="text-muted small mt-2">
              Sanction Number: <strong>{{ deleteItem.sanction_number }}</strong>
            </p>
            <p class="text-muted small">This record will be removed from the list. The deleted data can be recovered from the database if needed.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeDeleteDialog">Cancel</button>
            <button type="button" class="btn btn-danger" @click="confirmDelete" :disabled="isDeleting">
              <span v-if="isDeleting" class="spinner-border spinner-border-sm me-1" role="status"></span>
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { useFixedHorizontalScroll } from '../../Composables/useFixedHorizontalScroll'

const tsaList = ref([])
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
const showCloseDialog = ref(false)
const closeItem = ref(null)
const closeIndex = ref(null)
const showReviseDialog = ref(false)
const reviseItem = ref(null)
const reviseIndex = ref(null)
const showDeleteDialog = ref(false)
const deleteItem = ref(null)
const isDeleting = ref(false)
const showEditDialog = ref(false)
const isSavingEdit = ref(false)
const budgetHeads = ref([])
const programDivisions = ref([])
const editBalancedFundAmount = ref(0)
const editForm = ref({
  id: null,
  sanctionNumber: '',
  date: '',
  budgetHead: '',
  purposeOfGrant: '',
  programDivision: '',
  amount: '',
  expenditure: '',
  centralImplementingAgency: '',
  isNer: false,
  remark: ''
})

const editAmountExceedsBalance = computed(() => {
  const amount = parseFloat(editForm.value.amount)
  return !isNaN(amount) && amount > 0 && editBalancedFundAmount.value > 0 && amount > editBalancedFundAmount.value
})

const editExpenditureExceedsAmount = computed(() => {
  const expenditure = parseFloat(editForm.value.expenditure)
  const amount = parseFloat(editForm.value.amount)
  return !isNaN(expenditure) && expenditure > 0 && !isNaN(amount) && amount > 0 && expenditure > amount
})

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
  await fetchTSAList()
  await Promise.all([fetchBudgetHeads(), fetchProgramDivisions()])
  refreshFixedHorizontalScroll()
})

watch(() => [editForm.value.budgetHead, editForm.value.programDivision], () => {
  if (showEditDialog.value) {
    fetchEditBalancedFundAmount()
  }
})

const fetchTSAList = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const res = await fetch('/api/agency-release-tsa-list');
    if (res.ok) {
      const data = await res.json();
      tsaList.value = data;
    } else {
      console.error('Failed to fetch TSA data');
      error.value = 'Failed to fetch TSA data from server';
    }
  } catch (err) {
    console.error('Error fetching TSA data:', err);
    error.value = 'Network error occurred while fetching TSA data';
  } finally {
    isLoading.value = false
    refreshFixedHorizontalScroll()
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
        type: 'tsa',
        action: 'close'
      })
    });

    if (response.ok) {
      await fetchTSAList();
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
        type: 'tsa',
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
        expenditure: result.data.expenditure || '',
        centralImplementingAgency: result.data.central_implementing_agency || '',
        isNer: result.data.is_ner ? 'true' : 'false',
        remark: result.data.remark || ''
      });
      
      // Redirect to the TSA form with prefilled data
      window.location.href = `/agency-release-tsa?${queryParams.toString()}`;
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

const fetchBudgetHeads = async () => {
  try {
    const response = await fetch('/api/budget-heads-by-major-head?major_head=2435')
    if (response.ok) {
      budgetHeads.value = await response.json()
    }
  } catch (err) {
    console.error('Error fetching budget heads:', err)
  }
}

const fetchProgramDivisions = async () => {
  try {
    const response = await fetch('/api/aap-program-divisions')
    if (response.ok) {
      programDivisions.value = await response.json()
    }
  } catch (err) {
    console.error('Error fetching program divisions:', err)
  }
}

const fetchEditBalancedFundAmount = async () => {
  const budgetHead = editForm.value.budgetHead
  const programDivisionId = editForm.value.programDivision

  if (!budgetHead || !programDivisionId) {
    editBalancedFundAmount.value = 0
    return
  }

  try {
    let url = `/api/balanced-fund-amount?budget_head=${encodeURIComponent(budgetHead)}&program_division_id=${encodeURIComponent(programDivisionId)}`
    if (editForm.value.id) {
      url += `&exclude_type=tsa&exclude_id=${editForm.value.id}`
    }
    const response = await fetch(url)
    if (response.ok) {
      const data = await response.json()
      editBalancedFundAmount.value = parseFloat(data.allocated_amount || 0) - parseFloat(data.total_releases || 0)
    } else {
      editBalancedFundAmount.value = 0
    }
  } catch (err) {
    console.error('Error fetching balanced fund amount:', err)
    editBalancedFundAmount.value = 0
  }
}

const handleEdit = async (item) => {
  editForm.value = {
    id: item.id,
    sanctionNumber: item.sanction_number || '',
    date: item.date || '',
    budgetHead: item.budget_head || '',
    purposeOfGrant: item.purpose_of_grant || '',
    programDivision: item.program_division_id || '',
    amount: item.amount ?? '',
    expenditure: item.expenditure ?? '',
    centralImplementingAgency: item.central_implementing_agency || '',
    isNer: Boolean(item.is_ner),
    remark: item.remark || ''
  }
  showEditDialog.value = true
  await fetchEditBalancedFundAmount()
}

const closeEditDialog = () => {
  showEditDialog.value = false
  isSavingEdit.value = false
  editBalancedFundAmount.value = 0
}

const submitEdit = async () => {
  if (!editForm.value.id) return
  if (editAmountExceedsBalance.value) {
    showFlashMessage('danger', 'Amount cannot exceed Balanced Fund Amount.', 'fas fa-exclamation-triangle')
    return
  }

  if (editExpenditureExceedsAmount.value) {
    showFlashMessage('danger', `Expenditure cannot exceed Amount of ₹${parseFloat(editForm.value.amount).toFixed(2)} lakhs`, 'fas fa-exclamation-triangle')
    return
  }

  isSavingEdit.value = true

  try {
    const response = await fetch(`/api/agency-release-tsa/${editForm.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        sanctionNumber: editForm.value.sanctionNumber,
        date: editForm.value.date,
        budgetHead: editForm.value.budgetHead,
        purposeOfGrant: editForm.value.purposeOfGrant,
        programDivision: parseInt(editForm.value.programDivision, 10),
        amount: parseFloat(editForm.value.amount),
        expenditure: editForm.value.expenditure !== '' && editForm.value.expenditure != null
          ? parseFloat(editForm.value.expenditure)
          : null,
        centralImplementingAgency: editForm.value.centralImplementingAgency,
        isNer: Boolean(editForm.value.isNer),
        remark: editForm.value.remark
      })
    })

    if (response.ok) {
      await fetchTSAList()
      showFlashMessage('success', 'TSA record updated successfully.', 'fas fa-check-circle')
      closeEditDialog()
    } else {
      const errorData = await response.json().catch(() => ({}))
      showFlashMessage('danger', errorData.message || 'Failed to update record. Please try again.', 'fas fa-exclamation-triangle')
    }
  } catch (err) {
    console.error('Error updating record:', err)
    showFlashMessage('danger', 'An error occurred while updating the record. Please try again.', 'fas fa-exclamation-triangle')
  } finally {
    isSavingEdit.value = false
  }
}

const handleDelete = (item) => {
  deleteItem.value = item;
  showDeleteDialog.value = true;
};

const closeDeleteDialog = () => {
  showDeleteDialog.value = false;
  deleteItem.value = null;
  isDeleting.value = false;
};

const confirmDelete = async () => {
  if (!deleteItem.value) {
    closeDeleteDialog();
    return;
  }

  isDeleting.value = true;

  try {
    const response = await fetch('/api/agency-release/delete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        id: deleteItem.value.id,
        type: 'tsa'
      })
    });

    if (response.ok) {
      await fetchTSAList();
      showFlashMessage('success', 'Record deleted successfully.', 'fas fa-check-circle');
    } else {
      const errorData = await response.json().catch(() => ({}));
      showFlashMessage('danger', errorData.message || 'Failed to delete record. Please try again.', 'fas fa-exclamation-triangle');
    }
  } catch (error) {
    console.error('Error deleting record:', error);
    showFlashMessage('danger', 'An error occurred while deleting the record. Please try again.', 'fas fa-exclamation-triangle');
  } finally {
    closeDeleteDialog();
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

