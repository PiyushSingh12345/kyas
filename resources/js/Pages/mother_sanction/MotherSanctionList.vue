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
                              <th>SL  Scode</th>
                              <th>Annual Allocation</th>
                              <th>MS Total Amount</th>
                              <th>Budget Head</th>
                              <th>Expenditures made against MS</th>
                              <th>Available Balance for Exp</th>
                            </tr>
                          </thead>
                          
                          <tbody>
                          <tr v-for="(item, index) in secondTableData" :key="item.ky_ms_no">
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
                                      <th class="text-center">Available Fund</th>
                                      <th class="text-center">MS Amount</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(budget, budgetIndex) in item.budget_heads" :key="budgetIndex">
                                      <td class="text-center">{{ budget.budget_head }}</td>
                                      <td class="text-center">{{ budget.category }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.available_fund) }}</td>
                                      <td class="text-center currency-cell">{{ formatCurrency(budget.mother_sanction_amount) }}</td>
                                    </tr>
                                    <tr v-if="!item.budget_heads || item.budget_heads.length === 0">
                                      <td colspan="4" class="text-center text-muted">No budget heads available</td>
                                    </tr>
                                  </tbody>
                                </table>
                              </div>
                            </td>

                            <td class="currency-cell">{{ formatCurrency(item.total_expenditure) }}</td>
                            <td class="currency-cell">{{ formatCurrency(calculateAvailableBalance(item)) }}</td>
                          </tr>
                          
                          <tr v-if="secondTableData.length === 0">
                            <td colspan="11" class="text-center text-muted py-4">
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
  
  // The backend now provides data already grouped with budget_heads
  // We just need to transform it for the second table format
  return motherSanctions.value.map(item => ({
    ky_ms_no: item.ky_ms_no,
    financial_year: item.financial_year,
    sanction_date: item.sanction_date,
    state: item.state?.name || '',
    sls_name: item.sls_name,
    total_mother_sanction_amount: item.total_mother_sanction_amount,
    budget_heads: item.budget_heads || [],
    total_expenditure: 0, // This would come from daily sanctions if available
    annual_allocation: item.total_available_fund || 0, // Use total available fund from backend
    sl_scode: item.sls_code || item.sls_name?.substring(0, 2) || '', // Use sls_code from DB, fallback to substring
    status: item.status,
  }));
});

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
  if (!amount) return '0.00';
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
</style>


