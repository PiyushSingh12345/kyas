<template>
  <div class="wrapper">
    <Sidebar />

    <div class="main-panel">
      <Header />

      <div class="container">
        <div class="page-inner allinsideform">
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Re-Appropriation Module</div>
                </div>
                <div class="card-body">
                  <div class="row">
                    <!-- Financial Year -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>F.Y</label>
                        <select class="form-select" v-model="selectedYear">
                          <option value="" disabled>Select Financial Year</option>
                          <option v-for="year in financialYears" :key="year" :value="year">{{ year }}</option>
                        </select>
                      </div>
                    </div>

                    <!-- Budget Phase -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>Budget Phase</label>
                        <select class="form-select" v-model="selectedPhase">
                          <option value="" disabled>Budget Phase</option>
                          <option v-for="phase in budgetPhases" :key="phase" :value="phase">{{ phase }}</option>
                        </select>
                      </div>
                    </div>

                    <!-- RO Date -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>RO Date</label>
                        <input type="date" class="form-control" v-model="roDate" />
                      </div>
                    </div>

                    <!-- Type -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>Type</label>
                        <input type="text" class="form-control" v-model="type" />
                      </div>
                    </div>

                    <!-- Section -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>Section</label>
                        <input type="text" class="form-control" v-model="section" />
                      </div>
                    </div>

                    <!-- Program Division -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>Program Division</label>
                        <select class="form-select" v-model="selectedDivision">
                          <option value="" disabled>Program Division</option>
                          <option
                            v-for="division in programDivisions"
                            :key="division.division_id"
                            :value="division.division_id"
                          >
                            {{ division.division_name }}
                          </option>
                        </select>
                      </div>
                    </div>

                    <!-- From Budget Head -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>From Budget Head</label>
                        <select class="form-select" v-model="selectedFromBudgetHead">
                          <option value="" disabled>--Select--</option>
                          <option :value="999">Other</option>
                          <option v-for="head in budgetHeads" :key="head.id" :value="head.id">{{ head.budget }}</option>
                        </select>
                        <!-- Debug info (temporary) -->
                        <small class="text-muted">Selected: {{ selectedFromBudgetHead }} (Type: {{ typeof selectedFromBudgetHead }})</small>
                      </div>
                    </div>

                    <!-- Budget Head Other (shown when "Other" is selected) -->
                    <div class="col-md-6 col-lg-4" v-if="showBudgetHeadOther">
                      <div class="form-group">
                        <label>Budget Head Other</label>
                        <input type="text" class="form-control" v-model="fromBudgetHeadRemarks" placeholder="Enter budget head details" />
                      </div>
                    </div>

                    <!-- BE (INR Thousand) for From Budget Head -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>BE  (₹ In Lakhs)</label>
                        <input
                          type="text"
                          class="form-control"
                          :value="fromBudgetAmount"
                          placeholder="BE Amount"
                          disabled
                        />
                      </div>
                    </div>

                    <!-- Re-Appropriation Amt -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>Re-Appropriation Amt (₹ In Lakhs)</label>
                        <input
                          type="text"
                          class="form-control"
                         
                          v-model="reappropriationAmount"
                        />
                      </div>
                    </div>

                    <!-- Other Details -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>Other Details</label>
                        <input type="text" class="form-control" v-model="otherDetails"  />
                      </div>
                    </div>
                     <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>From Rule</label>
                        <input type="text" class="form-control" v-model="fromRule"  />
                      </div>
                    </div>

                    <!-- To Budget Head -->
                    <div class="bg-body-secondary mt-4 mb-4 rebdr">
                      <div class="row">
                        <div class="col-md-6 col-lg-4">
                          <div class="form-group">
                            <label>To Budget Head</label>
                            <select class="form-select" v-model="selectedToBudgetHead">
                              <option value="" disabled>--Select--</option>
                              <option v-for="head in budgetHeads" :key="head.id" :value="head.id">{{ head.budget }}</option>
                            </select>
                          </div>
                        </div>

                        <!-- BE (in INR Thousand) for To Budget Head -->
                        <div class="col-md-6 col-lg-4">
                          <div class="form-group">
                            <label>To BE (₹ In Lakhs)</label>
                            <input
                              type="text"
                              class="form-control"
                              :value="toBudgetAmount"
                              placeholder="BE Amount"
                              disabled
                            />
                          </div>
                        </div>

                        <!-- Re-appropriation Amount (disabled placeholder) -->
                        <div class="col-md-6 col-lg-4">
                          <div class="form-group">
                            <label>Re-appropriation Amount  (₹ In Lakhs)</label>
                            <input
                              type="text"
                              class="form-control"
                              :value="formattedReappropriationAmount"
                              disabled
                            />
                          </div>
                        </div>

                        <!-- Select Entity Type -->
                        <div class="col-md-6 col-lg-4">
                          <div class="form-group">
                            <label>Select Entity Type</label>
                            <div class="d-flex">
                              <div class="form-check me-3">
                                <input
                                  class="form-check-input"
                                  type="radio"
                                  id="entityState"
                                  value="State/UT"
                                  v-model="entityType"
                                />
                                <label class="form-check-label" for="entityState">State/UT</label>
                              </div>
                              <div class="form-check me-3">
                                <input
                                  class="form-check-input"
                                  type="radio"
                                  id="entityAgency"
                                  value="Agency"
                                  v-model="entityType"
                                />
                                <label class="form-check-label" for="entityAgency">Agency</label>
                              </div>
                              <div class="form-check">
                                <input
                                  class="form-check-input"
                                  type="radio"
                                  id="entityAdmin"
                                  value="Admin"
                                  v-model="entityType"
                                />
                                <label class="form-check-label" for="entityAdmin">Admin</label>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Select Entity -->
                        <div class="col-md-6 col-lg-4" v-if="entityType !== 'Admin'">
                          <div class="form-group">
                            <label @click="showEntityList = !showEntityList" style="cursor: pointer;">
                              Select Entity <span v-if="showEntityList">▲</span><span v-else>▼</span>
                            </label>

                            <div
                              v-if="showEntityList"
                              class="checkbox-list"
                              style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 8px;"
                            >
                              <!-- Show States when entityType is State/UT -->
                              <div v-if="entityType === 'State/UT'" v-for="state in states" :key="state.id" class="form-check">
                                <input
                                  class="form-check-input"
                                  type="checkbox"
                                  :id="'entity_' + state.id"
                                  :value="state.id"
                                  v-model="selectedEntities"
                                />
                                <label class="form-check-label" :for="'entity_' + state.id">{{ state.name }}</label>
                              </div>
                              
                              <!-- Show PDs when entityType is Agency -->
                              <div v-if="entityType === 'Agency'">
                                <div v-for="pd in programDivisions" :key="pd.division_id" class="form-check">
                                  <input
                                    class="form-check-input"
                                    type="checkbox"
                                    :id="'entity_' + pd.division_id"
                                    :value="pd.division_id"
                                    v-model="selectedEntities"
                                  />
                                  <label class="form-check-label" :for="'entity_' + pd.division_id">
                                    {{ pd.division_name }}
                                  </label>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                         <div class="col-md-6 col-lg-4">
                          <div class="form-group">
                            <label>Remarks</label>
                            <input type="text" class="form-control" v-model="remarks"  />
                          </div>
                        </div>


                        <div class="col-md-6 col-lg-4">
                          <div class="form-group">
                            <label>To Rule</label>
                            <input type="text" class="form-control" v-model="toRule"  />
                          </div>
                        </div>

                      
                      </div>
                    </div>

                    <!-- Reason for Additionality -->
                    <div class="row">
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label>Reason for Additionality</label>
                          <input
                            type="text"
                            class="form-control"
                            v-model="reasonForAdditionality"
                            placeholder="xyz"
                          />
                        </div>
                      </div>

                      <!-- Proposal Attract NS/NIS -->
                      <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                          <label>Proposal Attract NS/NIS</label>
                          <select class="form-select" v-model="proposalAttractNsNis">
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                          </select>
                        </div>
                      </div>
                    </div>

                    <!-- Table of existing reappropriations -->
                    <div class="table-responsive mt-3">
                      <table class="table table-bordered table-head-bg-primary">
                        <thead>
                          <tr>
                            <th>From HOA</th>
                            <th>From Rule</th>
                            <th>To HOA</th>
                            <th>Amount <small class="text-capitalize">(₹ In Lakhs)</small></th>
                            <th>To Entity Type</th>
                            <th>To Entity Name</th>
                            <th>To Rule</th>
                          </tr>
                        </thead>
                        <tbody>
                            <tr>
                              <td>{{ currentEntry.fromHOA }}</td>
                              <td>{{ currentEntry.fromRule }}</td>
                              <td>{{ currentEntry.toHOA }}</td>
                              <td>{{ currentEntry.reappropriationAmount }}</td>
                              <td>{{ currentEntry.toEntityType }}</td>
                              <td>{{ currentEntry.toEntityNames }}</td>
                              <td>{{ currentEntry.toRule }}</td>
                            </tr>
                          </tbody>

                      </table>
                    </div>
                  </div>
                </div>

                <div class="card-footer">
                  <div class="form">
                    <div class="col-12 d-flex justify-content-center">
                      <button class="btn btn-success me-1" @click.prevent="submitForm">Submit</button>
                      <button class="btn btn-danger me-1" @click.prevent="resetForm">Reset</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Reappropriation List Section -->
      <div class="container mt-4">
        <div class="row">
          <div class="col-md-12">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Reappropriation List</h5>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-bordered table-striped">
                    <thead class="table-primary">
                      <tr>
                        <th>Date</th>
                        <th>From HOA</th>
                        <th>To HOA</th>
                        <th>Amount <small class="text-capitalize">(₹ In Lakhs)</small></th>
                        <th>Entity Type</th>
                        <th>Entity Name</th>
                        <th>Remarks</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="reapp in paginatedReappropriations" :key="reapp.id">
                        <td>{{ formatDate(reapp.ro_date) }}</td>
                        <td>{{ getBudgetHeadNameWithDots(reapp.from_budget_head_id) }}</td>
                        <td>{{ getBudgetHeadNameWithDots(reapp.to_budget_head_id) }}</td>
                        <td>{{ reapp.reappropriation_amount }}</td>
                        <td>{{ reapp.entity_type }}</td>
                        <td>{{ getEntityNamesForReapp(reapp.selected_entity_ids, reapp.entity_type) }}</td>
                        <td>{{ reapp.reason_for_additionality || '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                  
                  <!-- Pagination Controls -->
                  <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="d-flex align-items-center">
                      <label class="me-2">Show:</label>
                      <select v-model="itemsPerPage" class="form-select form-select-sm" style="width: 70px;">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                      </select>
                      <span class="ms-2">entries</span>
                    </div>
                    
                    <div class="d-flex align-items-center">
                      <span class="me-3">
                        Showing {{ startIndex + 1 }} to {{ endIndex }} of {{ reappropriationList.length }} entries
                      </span>
                      
                      <nav>
                        <ul class="pagination pagination-sm mb-0">
                          <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <button class="page-link" @click="goToPage(1)" :disabled="currentPage === 1">
                              <i class="fas fa-angle-double-left"></i>
                            </button>
                          </li>
                          <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <button class="page-link" @click="goToPage(currentPage - 1)" :disabled="currentPage === 1">
                              <i class="fas fa-angle-left"></i>
                            </button>
                          </li>
                          
                          <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === currentPage }">
                            <button class="page-link" @click="goToPage(page)">{{ page }}</button>
                          </li>
                          
                          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <button class="page-link" @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages">
                              <i class="fas fa-angle-right"></i>
                            </button>
                          </li>
                          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <button class="page-link" @click="goToPage(totalPages)" :disabled="currentPage === totalPages">
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

      <Footer />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';

import Header from '../Common/Header.vue';
import Sidebar from '../Common/Sidebar.vue';
import Footer from '../Common/Footer.vue';

// Dropdown data
const financialYears = ref(['2025-2026','2024-2025', '2023-2024', '2022-2023', '2021-2022']);
const budgetPhases = ref(['BE', 'FE', 'RE']);
const programDivisions = ref([]);
const budgetHeads = ref([]);
const states = ref([]);
const reappropriations = ref([]);
const selectedEntities = ref([]);
const fromRule = ref('');
const toRule = ref('');
const remarks = ref('');
const showEntityList = ref(false);

// Form refs
const selectedYear = ref('');
const selectedPhase = ref('');
const roDate = ref('');
const type = ref('');
const section = ref('');
const selectedDivision = ref('');
const selectedFromBudgetHead = ref('');
const selectedToBudgetHead = ref('');
const reappropriationAmount = ref('');
const otherDetails = ref('');
const entityType = ref('State/UT'); // default selection
const selectedState = ref('');
const reasonForAdditionality = ref('');
const proposalAttractNsNis = ref('Yes');
const fromBudgetHeadRemarks = ref(''); // New ref for budget head remarks

const fromBudgetAmount = ref(''); // BE amount for From Budget Head
const toBudgetAmount = ref('');   // BE amount for To Budget Head
const reappropriationList = ref([]); // Reappropriation list data

// Pagination variables
const currentPage = ref(1);
const itemsPerPage = ref(10);

const showBudgetHeadOther = computed(() => {
  console.log('Computing showBudgetHeadOther:', selectedFromBudgetHead.value, 'Type:', typeof selectedFromBudgetHead.value);
  return selectedFromBudgetHead.value == 999;
});

const formattedReappropriationAmount = computed(() => {
  if (!reappropriationAmount.value) return '';
  return Number(reappropriationAmount.value).toLocaleString();
});

const getBudgetHeadName = (id) => {
  const head = budgetHeads.value.find((h) => h.id === id);
  return head ? head.budget : '';
};

const currentEntry = computed(() => ({
  fromHOA: getBudgetHeadName(selectedFromBudgetHead.value),
  fromRule: fromRule.value,
  toHOA: getBudgetHeadName(selectedToBudgetHead.value),
  reappropriationAmount: formattedReappropriationAmount.value,
  toEntityType: entityType.value,
  toEntityNames: getEntityNames(selectedEntities.value).join(', '),
  toRule: toRule.value,
}));

// Pagination computed properties
const totalPages = computed(() => {
  return Math.ceil(reappropriationList.value.length / itemsPerPage.value);
});

const startIndex = computed(() => {
  return (currentPage.value - 1) * itemsPerPage.value;
});

const endIndex = computed(() => {
  return Math.min(startIndex.value + itemsPerPage.value, reappropriationList.value.length);
});

const paginatedReappropriations = computed(() => {
  const start = startIndex.value;
  const end = start + itemsPerPage.value;
  return reappropriationList.value.slice(start, end);
});

const visiblePages = computed(() => {
  const pages = [];
  const total = totalPages.value;
  const current = currentPage.value;
  
  // Always show first page
  pages.push(1);
  
  // Show pages around current page
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  
  if (start > 2) {
    pages.push('...');
  }
  
  for (let i = start; i <= end; i++) {
    if (i > 1 && i < total) {
      pages.push(i);
    }
  }
  
  if (end < total - 1) {
    pages.push('...');
  }
  
  // Always show last page if there are more than 1 page
  if (total > 1) {
    pages.push(total);
  }
  
  return pages;
});

const getEntityNames = (ids) => {
  if (!Array.isArray(ids)) return [];
  
  if (entityType.value === 'State/UT') {
    return ids
      .map((id) => {
        const entity = states.value.find((s) => s.id === id);
        return entity ? entity.name : '';
      })
      .filter((name) => name);
  } else if (entityType.value === 'Agency') {
    return ids
      .map((id) => {
        const entity = programDivisions.value.find((pd) => pd.division_id === id);
        return entity ? entity.division_name : '';
      })
      .filter((name) => name);
  }
  
  return [];
};

// Functions for reappropriation list
const getBudgetHeadNameWithDots = (budgetId) => {
  const head = budgetHeads.value.find((h) => h.id === budgetId);
  if (!head) return '-';
  
  // Format budget head with dots (15 digits)
  const budgetCode = head.budget || '';
  if (budgetCode.length >= 15) {
    return budgetCode.replace(/(\d{3})(\d{3})(\d{3})(\d{3})(\d{3})/, '$1.$2.$3.$4.$5');
  }
  return budgetCode;
};

const getEntityNamesForReapp = (selectedEntityIds, entityType) => {
  if (!selectedEntityIds || !Array.isArray(selectedEntityIds)) return '-';
  
  if (entityType === 'State/UT') {
    return selectedEntityIds
      .map((id) => {
        const state = states.value.find((s) => s.id === id);
        return state ? state.name : '';
      })
      .filter((name) => name)
      .join(', ');
  } else if (entityType === 'Agency') {
    return selectedEntityIds
      .map((id) => {
        const entity = programDivisions.value.find((pd) => pd.division_id === id);
        return entity ? entity.division_name : '';
      })
      .filter((name) => name)
      .join(', ');
  } else if (entityType === 'Admin') {
    return 'Admin';
  }
  
  return '-';
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-IN');
};

// Watch entityType changes to reset selected entities and show/hide entity list
watch(entityType, (newType) => {
  selectedEntities.value = [];
  showEntityList.value = false;
});

// Watch itemsPerPage changes to reset to first page
watch(itemsPerPage, () => {
  currentPage.value = 1;
});

const loadReappropriations = async () => {
  try {
    const response = await axios.get('/api/reappropriations');
    reappropriationList.value = response.data;
    // Reset to first page when data is loaded
    currentPage.value = 1;
  } catch (error) {
    console.error('Error loading reappropriations:', error);
    reappropriationList.value = [];
  }
};

// Pagination functions
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const goToFirstPage = () => {
  currentPage.value = 1;
};

const goToLastPage = () => {
  currentPage.value = totalPages.value;
};

const goToPreviousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const goToNextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};
onMounted(async () => {
  try {
    const resDivisions = await axios.get('/md-program-divisions');
    programDivisions.value = resDivisions.data;
  } catch (error) {
    console.error('Error loading program divisions:', error);
    programDivisions.value = [];
  }

  try {
    const resHeads = await axios.get('/api/budget-heads');
    budgetHeads.value = resHeads.data;
  } catch {
    budgetHeads.value = [];
  }

  try {
    const resStates = await axios.get('/api/states');
    states.value = resStates.data;
  } catch {
    states.value = [];
  }

  await loadReappropriations();
});

// Watch selectedFromBudgetHead and fetch BE amount
watch(selectedFromBudgetHead, async (newVal) => {
  console.log('selectedFromBudgetHead changed:', newVal, 'Type:', typeof newVal);
  
  if (!newVal) {
    fromBudgetAmount.value = '';
    return;
  }
  
  // If "Other" is selected (ID 999), don't fetch budget amount
  if (newVal == 999) {
    console.log('Other option selected, clearing budget amount');
    fromBudgetAmount.value = '';
    return;
  }
  
  try {
    const response = await axios.get('/api/budget-phase/amount', {
      params: { budget_head_id: newVal }
    });
    const amount = response.data.budget_amount || 0;
    fromBudgetAmount.value = Number(amount).toLocaleString();
  } catch (error) {
    console.error('Failed to fetch budget amount:', error);
    fromBudgetAmount.value = '';
  }
});

// Watch selectedToBudgetHead and fetch BE amount
watch(selectedToBudgetHead, async (newVal) => {
  if (!newVal) {
    toBudgetAmount.value = '';
    return;
  }
  try {
    const response = await axios.get('/api/budget-phase/amount', {
      params: { budget_head_id: newVal }
    });
    const amount = response.data.budget_amount || 0;
    toBudgetAmount.value = Number(amount).toLocaleString();
  } catch (error) {
    console.error('Failed to fetch To Budget Head amount:', error);
    toBudgetAmount.value = '';
  }
});

const submitForm = async () => {
  try {
    // Prepare selected entity IDs based on entity type
    let entityIds = [];
    if (entityType.value === 'State/UT') {
      entityIds = selectedEntities.value; // State IDs
    } else if (entityType.value === 'Agency') {
      entityIds = selectedEntities.value; // PD IDs
    } else if (entityType.value === 'Admin') {
      entityIds = []; // Empty array for Admin
    }

    // Handle from_be value - if "Other" is selected, set to 0
    let fromBeValue = 0;
    if (selectedFromBudgetHead.value != 999) {
      fromBeValue = Number(fromBudgetAmount.value.replace(/,/g, ''));
    }

    const payload = {
      financial_year: selectedYear.value,
      budget_phase: selectedPhase.value,
      ro_date: roDate.value,
      type: type.value,
      section: section.value,
      program_division_id: selectedDivision.value,
      from_budget_head_id: selectedFromBudgetHead.value,
      to_budget_head_id: selectedToBudgetHead.value,
      reappropriation_amount: parseFloat(reappropriationAmount.value),
      other_details: otherDetails.value,
      entity_type: entityType.value,
      selected_entity_ids: entityIds, // Save the appropriate IDs based on entity type
      from_rule: fromRule.value,
      to_rule: toRule.value,
      remarks: remarks.value,
      reason_for_additionality: reasonForAdditionality.value,
      proposal_attract_ns_nis: proposalAttractNsNis.value,
      from_be: fromBeValue,
      to_be: Number(toBudgetAmount.value.replace(/,/g, '')),
      from_budget_head_remarks: fromBudgetHeadRemarks.value, // Add remarks to payload
    };

    await axios.post('/api/reappropriations', payload);

    alert('Data inserted successfully!');
    await loadReappropriations();
    resetForm();
  } catch (error) {
    console.error(error);
    alert('Error inserting data');
  }
};

const resetForm = () => {
  selectedYear.value = '';
  selectedPhase.value = '';
  roDate.value = '';
  type.value = '';
  section.value = '';
  selectedDivision.value = '';
  selectedEntities.value = [];
  showEntityList.value = false;

  selectedFromBudgetHead.value = '';
  selectedToBudgetHead.value = '';
  reappropriationAmount.value = '';
  otherDetails.value = '';
  entityType.value = 'State/UT';
  selectedState.value = '';
  reasonForAdditionality.value = '';
  proposalAttractNsNis.value = 'Yes';
  fromBudgetAmount.value = '';
  toBudgetAmount.value = '';
  fromRule.value = '';
  toRule.value = '';
  remarks.value = '';
  fromBudgetHeadRemarks.value = ''; // Reset remarks
};



const addMoreReappropriation = () => {
  alert('Add More functionality not implemented yet.');
};
</script>

<style scoped>
/* Add any scoped CSS if needed */
</style>
