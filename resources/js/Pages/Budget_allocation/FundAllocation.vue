<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Budget Allocation Module</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="login.html"><i class="icon-home"></i></a>
              </li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item">
                <a href="Fund-Allocation.html">Fund Allocation</a>
              </li>
            </ul>
          </div>

          <!-- Form Fields -->
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Fund Allocation</div>
                </div>

                <div class="card-body">
                  <div class="row">
                    <!-- Radio Buttons -->
                    <div class="col-md-4 col-lg-4">
                      <div class="form-group">
                        <label>Fund Allocation For</label><br />
                        <div class="d-flex">
                          <div class="form-check me-3">
                            <input
                              class="form-check-input"
                              type="radio"
                              id="radio2435"
                              value="2435"
                              v-model="fundAllocationFor"
                              name="fundAlloc"
                            />
                            <label class="form-check-label" for="radio2435">2435</label>
                          </div>

                          <div class="form-check me-3">
                            <input
                              class="form-check-input"
                              type="radio"
                              id="radio3601"
                              value="3601"
                              v-model="fundAllocationFor"
                              name="fundAlloc"
                            />
                            <label class="form-check-label" for="radio3601">3601 & 3602</label>
                          </div>

                          <div class="form-check">
                            <input
                              class="form-check-input"
                              type="radio"
                              id="radio2552"
                              value="2552"
                              v-model="fundAllocationFor"
                              name="fundAlloc"
                            />
                            <label class="form-check-label" for="radio2552">2552</label>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Financial Year -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label for="financialYearSelect">F.Y</label>
                        <select class="form-select" id="financialYearSelect" v-model="financialYear">
                          <option disabled value="">Select Year</option>
                          <option value="2024-2025">2024–2025</option>
                        </select>
                      </div>
                    </div>

                    <!-- Budget Phase -->
                    <div class="col-md-6 col-lg-4">
                      <div class="form-group">
                        <label for="budgetPhaseSelect">Budget Phase</label>
                        <select class="form-select" id="budgetPhaseSelect" v-model="budgetPhase">
                          <option disabled value="">Select Budget Phase</option>
                          <option value="BE">BE</option>
                          <option value="FE">FE</option>
                          <option value="RE">RE</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <!-- Tables shown only when conditions are met -->
                  <div v-if="canShowTable">
                    <!-- Table 1 -->
                    <div class="table-scroll-container mt-5">
                      <table class="table table-bordered table-head-bg-primary min-wide-table">
                        <tbody>
                          <tr>
                            <td></td>
                            <td width="20%">
                              <label class="highlight_textbox" style="width: 113px;">Amount Avail in {{ budgetPhase }}</label>
                            </td>
                            <td v-for="n in 9" :key="'pd-label-' + n">
                              <label class="highlight_textbox"  style="width: 113px;">PD{{ n }}</label>
                            </td>
                          </tr>

                          <tr>
                            <td></td>
                            <td>
                              <input
                                type="text"
                                class="form-control"
                                :value="totalAvailableAmount.toLocaleString()"
                                disabled
                              />
                            </td>
                             <td v-for="n in 9" :key="'total-pd-' + n">
                                <input
                                  type="text"
                                  class="form-control"
                                  :value="getPdTotal(n - 1)"
                                  disabled
                                />
                              </td>
                          </tr>
                         <template v-for="(category, cIdx) in budgetData" :key="'category-' + cIdx">
                          <tr>
                            <td colspan="6" class="catgerybg">{{ category.category }}</td>
                          </tr>

                          <tr v-for="(budget, bIdx) in category.budgetArray" :key="'budget-row-' + cIdx + '-' + bIdx">
                            <td>
                              <input
                                style="width: 120px;"
                                type="text"
                                class="form-control tableform-control-withoutbg"
                                :placeholder="budget.budget"
                                disabled
                              />
                            </td>
                            <td>
                              <input
                                type="text"
                                class="form-control tableform-control-withoutbg"
                                :value="budget.amount"
                                disabled
                              />
                            </td>
                            <td v-for="n in 9" :key="'pd-input-' + cIdx + '-' + bIdx + '-' + n">
                              <input
                                type="number"
                                class="form-control tableform-control-withoutbg"
                                v-model.number="pdInputs[cIdx][bIdx][n - 1]"
                                @input="validateBudgetRow(cIdx, bIdx, budget.amount)"
                              />
                            </td>
                          </tr>
                         </template>
                        </tbody>
                      </table>
                    </div>

                  
                  </div>
                </div>

                <!-- Footer Buttons -->
                <div class="card-footer" v-if="canShowTable">
                  <div class="form">
                    <div class="col-12 d-flex justify-content-center">
                      <button id="displayNotif" class="btn btn-primary me-1">Save as Draft</button>
                      <button class="btn btn-success me-1">Submit</button>
                      <button class="btn btn-danger me-1">Reset</button>
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
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'

import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const fundAllocationFor = ref('')
const financialYear = ref('')
const budgetPhase = ref('')
const budgetData = ref([])
const pdInputs = ref([])

watch(budgetData, (newData) => {
  pdInputs.value = newData.map(category =>
    category.budgetArray.map(() =>
      Array(9).fill(0) // 9 PD columns
    )
  )
})

function getPdTotal(pdIndex) {
  return pdInputs.value.reduce((categorySum, category) => {
    return categorySum + category.reduce((budgetSum, valueArray) => {
      return budgetSum + Number(valueArray[pdIndex] || 0)
    }, 0)
  }, 0)
}

function validateBudgetRow(cIdx, bIdx, allowedAmount) {
  const row = pdInputs.value[cIdx][bIdx]
  const total = row.reduce((sum, val) => sum + Number(val || 0), 0)

  if (total > allowedAmount) {
    alert(`Total amount for this budget row cannot exceed ₹${allowedAmount.toLocaleString()}`)
    
    // Reset the last input to 0 (or adjust as needed)
    const lastIndex = row.findLastIndex(v => v !== 0)
    if (lastIndex !== -1) {
      row[lastIndex] = 0
    }
  }
}


// Only show table when all 3 values are selected
const canShowTable = computed(() => {
  return fundAllocationFor.value !== '' &&
         financialYear.value !== '' &&
         budgetPhase.value !== ''
})

const totalAvailableAmount = computed(() => {
  return budgetData.value.reduce((total, category) => {
    return total + category.budgetArray.reduce((sum, item) => sum + item.amount, 0)
  }, 0)
})
watch([fundAllocationFor, financialYear, budgetPhase], ([newFundAllocationFor, newFinancialYear, newBudgetPhase]) => {
  if (newFundAllocationFor && newFinancialYear && newBudgetPhase) {
    fetchBudgetData(newFundAllocationFor, newFinancialYear, newBudgetPhase);
  }
});

const fetchBudgetData = async (fundAllocationFor, financialYear, budgetPhase) => {
  try {
    const response = await fetch(`/api/budget-allocation?fund_allocation_for=${fundAllocationFor}&financial_year=${financialYear}&budget_phase=${budgetPhase}`);
    const data = await response.json();
     budgetData.value = data;
    
  } catch (error) {
    console.error('Error fetching budget data:', error);
  }
};
</script>

