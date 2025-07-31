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
                          <option v-for="head in budgetHeads" :key="head.id" :value="head.id">{{ head.budget }}</option>
                        </select>
                      </div>
                    </div>

                    <!-- BE (INR Thousand) for From Budget Head -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label>BE (IN INR THOUSAND)</label>
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
                        <label>Re-Appropriation Amt</label>
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
                            <label>To BE (in INR Thousand)</label>
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
                            <label>Re-appropriation Amount (INR)</label>
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
                         <!-- Select Entity -->
<div class="col-md-6 col-lg-4">
  <div class="form-group">
    <label @click="showEntityList = !showEntityList" style="cursor: pointer;">
      Select Entity <span v-if="showEntityList">▲</span><span v-else>▼</span>
    </label>

    <div
      v-if="showEntityList"
      class="checkbox-list"
      style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 8px;"
    >
      <div v-for="state in states" :key="state.id" class="form-check">
        <input
          class="form-check-input"
          type="checkbox"
          :id="'entity_' + state.id"
          :value="state.id"
          v-model="selectedEntities"
        />
        <label class="form-check-label" :for="'entity_' + state.id">{{ state.name }}</label>
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
const financialYears = ref(['2024-2025', '2023-2024', '2022-2023', '2021-2022']);
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

const fromBudgetAmount = ref(''); // BE amount for From Budget Head
const toBudgetAmount = ref('');   // BE amount for To Budget Head

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
  toEntityType: entityType.value,
  toEntityNames: getEntityNames(selectedEntities.value).join(', '),
  toRule: toRule.value,
}));
const getEntityNames = (ids) => {
  if (!Array.isArray(ids)) return [];
  return ids
    .map((id) => {
      const entity = states.value.find((s) => s.id === id);
      return entity ? entity.name : '';
    })
    .filter((name) => name);
};
watch(entityType, (newType) => {
  selectedEntities.value = [];
});
onMounted(async () => {
  try {
    const resDivisions = await axios.get('/md-program-divisions');
    programDivisions.value = resDivisions.data;
  } catch {
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
  if (!newVal) {
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
  selected_entity_ids: selectedEntities.value, // selected states/entities
  from_rule: fromRule.value,
  to_rule: toRule.value,
  remarks:remarks.value,
  reason_for_additionality: reasonForAdditionality.value,
  proposal_attract_ns_nis: proposalAttractNsNis.value,
   from_be: Number(fromBudgetAmount.value.replace(/,/g, '')),
  to_be: Number(toBudgetAmount.value.replace(/,/g, '')),
};


    await axios.post('/api/reappropriations', payload);

    alert('Data inserted successfully!');
    await loadReappropriations();
    resetForm();
  } catch (error) {
    console.error(error);
    //alert('Error inserting data');
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
};



const addMoreReappropriation = () => {
  alert('Add More functionality not implemented yet.');
};
</script>

<style scoped>
/* Add any scoped CSS if needed */
</style>
