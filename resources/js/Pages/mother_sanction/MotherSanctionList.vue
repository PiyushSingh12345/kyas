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
                  <a href="#">Mother Sanction List </a>
                </li>
              </ul>

            </div>
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <div class="card-title">Mother Sanction List</div>
                     <!-- <Link :href="route('mother-sanction')" class="btn btn-primary me-1" style="margin-left:91%">ADD</Link> -->

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


                    <!-- Data Tables -->
                    <div>
                      <div class="table-responsive">
                        <table class="table table-bordered table-head-bg-primary">
                          <thead>
                            <tr>
                              <th>Fy</th>
                              <th>State</th>
                              <th>MS NO</th>
                              <th>Date</th>
                              <th>SLS Details</th>
                              <th>SLS Code</th>
                              <th>Total Annual Allocation</th>
                              <th>Total MS</th>
                              <th>Effective Total MS</th>
                              <th>Budget Head</th>
                              <th>Action</th>
                              <th>Status</th>
                            </tr>
                          </thead>
                          
                          <tbody>
                          <tr v-for="(item, index) in secondTableData" :key="`${item.state_id}-${item.sl_scode}`" :class="{ 'table-secondary': item.status === 'inactive' }">
                            <td>{{ item.financial_year }}</td>
                            <td>{{ item.state }}</td>
                            <td>{{ item.ky_ms_no }}</td>
                            <td>{{ formatDate(item.sanction_date) }}</td>
                            <td>{{ item.sls_name }}</td>
                            <td>{{ item.sl_scode }}</td>
                            <td class="currency-cell">{{ formatCurrency(item.annual_allocation) }}</td>
                            <td class="currency-cell">{{ formatCurrency(calculateTotalMs(item)) }}</td>
                            <td class="currency-cell">{{ formatCurrency(item.effective_total_ms) }}</td>

                            <td>
                              <div class="budget-head-table">
                                <table class="table table-sm mb-0">
                                  <!-- <table class="table table-sm table-bordered mb-0"> -->
                                    <thead>
                                    <tr class="table-light">
                                      <th class="text-center">Budget Head</th>
                                      <th class="text-center">Category</th>
                                      <th class="text-center">Annual Allocation</th>
                                      <th class="text-center">MS Amount</th>
                                      <th class="text-center">Effective MS Amount</th>
                                      <th class="text-center">Expenditure</th>
                                      <th class="text-center">Available Fund</th>
                                      <th class="text-center">Carry Forward Amount</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(budget, budgetIndex) in item.budget_heads" :key="budgetIndex">
                                      <td class="text-center">{{ budget.budget_head }}</td>
                                      <td class="text-center">{{ budget.category }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.annual_allocation_individual) }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(calculateTotalMsAmount(budget, item)) }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.mother_sanction_amount) }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.expenditure) }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(calculateAvailableFund(budget, item)) }}</td>
                                      <td class="text-center currency-cell">{{ formatCarryForwardAmount(budget.carry_forward_amount || 0) }}</td>
                                    </tr>
                                    <tr v-if="!item.budget_heads || item.budget_heads.length === 0">
                                      <td colspan="8" class="text-center text-muted">No budget heads available</td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                            </td>

                            <td class="text-center status-column">
                              <div class="d-flex justify-content-center align-items-center gap-2">
                                <button 
                                  class="btn btn-sm btn-secondary"
                                  @click="handleClose(item, index)"
                                  title="Close"
                                  :disabled="item.status === 'close'"
                                >
                                  Close
                                </button>
                                <button 
                                  class="btn btn-sm btn-primary"
                                  @click="handleRevise(item, index)"
                                  title="Revise"
                                  :disabled="item.status === 'close'"
                                >
                                  Revise
                                </button>
                              </div>
                            </td>

                            <td class="text-center status-column">
                              <span 
                                :class="{
                                  'badge bg-success': item.status === 'active',
                                  'badge bg-secondary': item.status === 'inactive',
                                  'badge bg-danger': item.status === 'close'
                                }"
                              >
                                {{ item.status === 'active' ? 'Active' : item.status === 'inactive' ? 'Inactive' : 'Close' }}
                              </span>
                            </td>
                          </tr>
                          
                          <tr v-if="secondTableData.length === 0">
                            <td colspan="12" class="text-center text-muted py-4">
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
        <Footer />
    </div>
    
    <!-- Confirmation Dialog for Status Toggle -->
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
            <p class="text-muted small mt-2">This will add back the available amount to the budget phase amount for BE corresponding to the budget head respectively. MS amount will be equal to Expenditure amount, available amount will become zero, and status will be set to close.</p>
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
            <p class="text-muted small mt-2">This will make the older data status inactive and open the mother sanction page with these data prefilled. The available amount value will be filled in the carry forward field and MS amount in the MS amount field. On submit, MS amount will equal (MS amount field value + Carry Forward field value).</p>
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
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const motherSanctions = ref([])
const isLoading = ref(false)
const error = ref(null)
const showConfirmDialog = ref(false)
const selectedItem = ref(null)
const selectedIndex = ref(null)
const originalStatus = ref(null)
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
  await fetchMotherSanctions()
})

const fetchMotherSanctions = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    const res = await fetch('/api/mother-sanctions-list');
    if (res.ok) {
      const data = await res.json();
      motherSanctions.value = data;
      
      // Debug: Log the first item to verify sls_code is received
      if (data.length > 0) {
        console.log('First mother sanction item:', data[0]);
        console.log('SLS Code received:', data[0].sls_code);
        console.log('Budget heads received:', data[0].budget_heads);
        console.log('Data structure:', JSON.stringify(data[0], null, 2));
      }
    } else {
      console.error('Failed to fetch data');
      error.value = 'Failed to fetch data from server';
    }
  } catch (err) {
    console.error('Error fetching data:', err);
    error.value = 'Network error occurred while fetching data';
  } finally {
    isLoading.value = false
  }
};

// Computed property to transform data for the second table
const secondTableData = computed(() => {
  if (!motherSanctions.value.length) return [];
  
  // The backend now provides data already grouped with budget_heads by state + sls_code
  // We just need to transform it for the second table format
  return motherSanctions.value.map(item => ({
    ky_ms_no: item.ky_ms_no, // This now contains comma-separated list of all ky_ms_no values
    ky_ms_no_list: item.ky_ms_no_list || [], // Array of all ky_ms_no values
    financial_year: item.financial_year,
    sanction_date: item.sanction_date,
    state: item.state?.name || '',
    state_id: item.state?.id || item.state_id || '',
    sls_name: item.sls_name,
    sls_id: item.sls_id || '',
    ms_sequence_no: item.ms_sequence_no || '',
    total_mother_sanction_amount: item.effective_total_ms ?? item.total_mother_sanction_amount,
    effective_total_ms: item.effective_total_ms ?? item.total_mother_sanction_amount,
    is_revised: Boolean(item.is_revised),
    budget_heads: item.budget_heads || [],
    total_expenditure: 0,
    annual_allocation: item.annual_allocation || 0,
    sl_scode: item.sls_code || item.sls_name?.substring(0, 2) || '', // Use sls_code from DB, fallback to substring
    status: item.status || 'active', // Default to active if not specified - can be 'active', 'inactive', or 'close'
    ifd_no: item.ifd_no || '',
    pd_component: item.pd_component || '',
    remark: item.remark || '',
  }));
});

// Method to calculate available balance
const calculateAvailableBalance = (row) => {
  const totalAllocated = parseFloat(row.total_mother_sanction_amount) || 0;
  const totalExpenditure = parseFloat(row.total_expenditure) || 0;
  return (totalAllocated - totalExpenditure).toFixed(2);
};

const calculateTotalMsAmount = (budget, item) => {
  const effectiveMs = parseFloat(budget.mother_sanction_amount) || 0;
  const expenditure = parseFloat(budget.expenditure) || 0;

  if (!item?.is_revised) {
    return effectiveMs;
  }

  return effectiveMs + expenditure;
};

// Available Fund = MS Amount - Expenditure
const calculateAvailableFund = (budget, item) => {
  const msAmount = calculateTotalMsAmount(budget, item);
  const expenditure = parseFloat(budget.expenditure) || 0;
  return msAmount - expenditure;
};

const calculateRowTotalExpenditure = (item) => {
  if (!item?.budget_heads?.length) {
    return 0;
  }

  return item.budget_heads.reduce((sum, budget) => {
    return sum + (parseFloat(budget.expenditure) || 0);
  }, 0);
};

const calculateTotalMs = (item) => {
  const effectiveTotal = parseFloat(item.effective_total_ms ?? item.total_mother_sanction_amount) || 0;

  if (!item?.is_revised) {
    return effectiveTotal;
  }

  return effectiveTotal + calculateRowTotalExpenditure(item);
};

// Method to format date
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-IN');
};

// Method to format currency
const formatCurrency = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

// Method to format carry forward amount with 5 decimal places
const formatCarryForwardAmount = (amount) => {
  if (!amount) return '0.00000';
  return parseFloat(amount).toLocaleString('en-IN', {
    minimumFractionDigits: 5,
    maximumFractionDigits: 5
  });
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
      
      // Get all ky_ms_no values for this group
      const kyMsNos = selectedItem.value.ky_ms_no_list && selectedItem.value.ky_ms_no_list.length > 0
        ? selectedItem.value.ky_ms_no_list
        : (selectedItem.value.ky_ms_no ? [selectedItem.value.ky_ms_no] : []);
      
      if (kyMsNos.length === 0) {
        alert('No mother sanction numbers found.');
        closeConfirmDialog();
        return;
      }
      
      const response = await fetch('/api/mother-sanction/update-status', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          ky_ms_no: kyMsNos, // Send array of ky_ms_no values
          action: action
        })
      });

      if (response.ok) {
        // Refresh the data to reflect the status change
        await fetchMotherSanctions();
        
        // Only redirect to add page if deactivating (creating new instance)
        if (action === 'deactivate') {
          // Use the first ky_ms_no for the redirect (or the single one if only one exists)
          const firstKyMsNo = kyMsNos.length > 0 ? kyMsNos[0] : selectedItem.value.ky_ms_no;
          
          // Create query parameters for prefilling the form
          const queryParams = new URLSearchParams({
            edit: 'true',
            ky_ms_no: firstKyMsNo,
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

  const kyMsNosToClose = closeItem.value.ky_ms_no_list && closeItem.value.ky_ms_no_list.length > 0 
    ? closeItem.value.ky_ms_no_list 
    : (closeItem.value.ky_ms_no ? [closeItem.value.ky_ms_no] : []);
  
  if (kyMsNosToClose.length === 0) {
    showFlashMessage('danger', 'No mother sanction numbers found to close.', 'fas fa-exclamation-triangle');
    closeCloseDialog();
    return;
  }

  // Prepare budget heads data with old values for backend processing
  const budgetHeadsData = closeItem.value.budget_heads.map(budget => {
    const oldExpenditure = parseFloat(budget.expenditure) || 0
    const oldAvailableFund = calculateAvailableFund(budget, closeItem.value) // MS Amount - Expenditure
    const oldMsAmount = parseFloat(budget.mother_sanction_amount) || 0
    
    return {
      budget_head: budget.budget_head,
      category: budget.category,
      old_expenditure: oldExpenditure,
      old_available_fund: oldAvailableFund,
      old_ms_amount: oldMsAmount
    }
  })

  try {
    const response = await fetch('/api/mother-sanction/update-status', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        ky_ms_no: kyMsNosToClose,
        action: 'close',
        financial_year: closeItem.value.financial_year,
        budget_heads: budgetHeadsData // Send budget heads data with old values
      })
    });

    if (response.ok) {
      // Refresh the data to reflect the status & amount changes
      await fetchMotherSanctions();
      showFlashMessage(
        'success',
        'Record closed successfully. MS Amount is now equal to Expenditure, Available Fund has been added back to BE budget phase and set to zero, and status is set to close.',
        'fas fa-check-circle'
      );
    } else {
      const errorData = await response.json().catch(() => ({}));
      console.error('Failed to close record:', errorData);
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

  const kyMsNosToRevise = reviseItem.value.ky_ms_no_list && reviseItem.value.ky_ms_no_list.length > 0 
    ? reviseItem.value.ky_ms_no_list 
    : (reviseItem.value.ky_ms_no ? [reviseItem.value.ky_ms_no] : []);
  
  if (kyMsNosToRevise.length === 0) {
    showFlashMessage('danger', 'No mother sanction numbers found to revise.', 'fas fa-exclamation-triangle');
    closeReviseDialog();
    return;
  }

  try {
    // First, set old data status to inactive
    const response = await fetch('/api/mother-sanction/update-status', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        ky_ms_no: kyMsNosToRevise,
        action: 'revise'
      })
    });

    if (response.ok) {
      // Get the first ky_ms_no for redirect
      const firstKyMsNo = kyMsNosToRevise.length > 0 ? kyMsNosToRevise[0] : reviseItem.value.ky_ms_no;
      
      // Prepare budget heads data with available amount in carry forward and MS amount in MS amount field
      const budgetHeadsForForm = reviseItem.value.budget_heads.map(budget => ({
        budget_head: budget.budget_head,
        category: budget.category,
        available_amount: budget.available_fund || calculateAvailableFund(budget, reviseItem.value),
        sanction_amount: '',
        carry_forward: calculateAvailableFund(budget, reviseItem.value) || '0.00000'
      }));
      
      // Create query parameters for prefilling the form
      const queryParams = new URLSearchParams({
        revise: 'true',
        ky_ms_no: firstKyMsNo,
        financial_year: reviseItem.value.financial_year,
        state_id: reviseItem.value.state_id || '',
        sls_id: reviseItem.value.sls_id || '',
        ms_sequence_no: reviseItem.value.ms_sequence_no || '',
        sanction_date: reviseItem.value.sanction_date || '',
        ifd_no: reviseItem.value.ifd_no || '',
        sls_name: reviseItem.value.sls_name || '',
        pd_component: reviseItem.value.pd_component || '',
        remark: reviseItem.value.remark || '',
        budget_heads: JSON.stringify(budgetHeadsForForm)
      });
      
      // Redirect to the mother sanction page with prefilled data
      window.location.href = `/mother-sanction?${queryParams.toString()}`;
    } else {
      const errorData = await response.json().catch(() => ({}));
      console.error('Failed to revise record:', errorData);
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
  min-width: 200px;
}

.gap-2 {
  gap: 0.5rem;
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


