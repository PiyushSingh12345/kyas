<style>
.table-scroll-container {
  overflow-x: auto;
  width: 100%;
  margin-bottom: 1rem;
}

.min-wide-table {
  min-width: 1300px; /* Enough width for all PD columns */
  border-collapse: collapse;
}
</style>
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
                    <div class="form-group mb-3">
                      <label class="fw-bold">Fund Allocation For</label><br />
                      <div class="d-flex">
                        <div class="form-check me-3">
                          <input class="form-check-input" type="radio" id="f2435" value="2435" v-model="fundAllocationFor" />
                          <label class="form-check-label" for="f2435">2435</label>
                        </div>
                        <div class="form-check me-3">
                          <input class="form-check-input" type="radio" id="f3601" value="3601" v-model="fundAllocationFor" />
                          <label class="form-check-label" for="f3601">3601 & 3602</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" id="f2552" value="2552" v-model="fundAllocationFor" />
                          <label class="form-check-label" for="f2552">2552</label>
                        </div>
                      </div>
                    </div>

                    <!-- Financial Year -->
                    <div class="col-md-4 col-lg-4">
                      <div class="form-group">
                        <label for="financialYearSelect">F.Y</label>
                        <select class="form-select" id="financialYearSelect" v-model="financialYear">
                          <option disabled value="">Select Year</option>
                          <option value="2025-2026">2025-2026</option>
                          <option value="2024-2025">2024–2025</option>
                        </select>
                      </div>
                    </div>

                    <!-- Budget Phase -->
                    <div class="col-md-4 col-lg-4">
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
                    <div class="col-md-4 col-lg-4" v-if="['3601', '3602', '2552'].includes(fundAllocationFor)">
                    <div class="form-group">
                        <label for="stateSelect">Select State</label>
                        <select v-model="selectedState" class="form-select" id="stateSelect">
                          <option value="">--- Select State ---</option>
                          <option v-for="state in states" :key="state.id" :value="state.id">
                            {{ state.name }}
                          </option>
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
                              <label class="highlight_textbox" >Amount Avail in {{ budgetPhase }}</label>
                            </td>
                            
                            <td v-for="(component, index) in availableComponents" :key="'pd-label-' + index">
                              <label class="highlight_textbox" >
                                {{ component.name }}
                              </label>
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
                             <td v-for="(component, pdIndex) in availableComponents" :key="'total-pd-' + pdIndex">
                                <input
                                  type="text"
                                  class="form-control"
                                  :value="getPdTotal(pdIndex)"
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
                                style="width: 135px;"
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
                           <td v-for="(component, pdIndex) in availableComponents" :key="'pd-input-' + cIdx + '-' + bIdx + '-' + pdIndex">
                              <input
                                type="number"
                                class="form-control tableform-control-withoutbg"
                                v-model.number="pdInputs[cIdx][bIdx][pdIndex]"
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
                      <button class="btn btn-primary me-1" @click="submitForm(0)">Save as Draft</button>
                      <button class="btn btn-success me-1" @click="submitForm(1)">Submit</button>
                      <button class="btn btn-danger me-1" @click="resetForm">Reset</button>
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
import { router } from '@inertiajs/vue3' 


const fundAllocationFor = ref('')
const financialYear = ref('')
const budgetPhase = ref('')
const budgetData = ref([])
const pdInputs = ref([])

const availableComponents = ref([])
const selectedComponent = ref('')
const states = ref([])
const selectedState = ref('')

const resetForm = () => {
  fundAllocationFor.value = ''
  financialYear.value = ''
  budgetPhase.value = ''
  budgetData.value = []
  pdInputs.value = []
  availableComponents.value = []
}



watch(budgetData, (newData) => {
  pdInputs.value = newData.map(category =>
    category.budgetArray.map(() =>
      Array(9).fill(0) // 9 PD columns
    )
  )
})

watch(fundAllocationFor, async (fund) => {
  selectedComponent.value = fund === '2435' ? 'PD' : 'SL'

  if (['3601', '3602', '2552'].includes(fund)) {
    try {
      const response = await fetch('/api/states')
      states.value = response.ok ? await response.json() : []
    } catch (error) {
      console.error('Error fetching states:', error)
    }
  } else {
    states.value = []
    selectedState.value = ''
  }
})
watch([fundAllocationFor, financialYear, budgetPhase, selectedState], async ([fund, year, phase, state]) => {
  if (!fund || !year || !phase) return

  // For 2435, fetch immediately after year & phase
  if (fund === '2435') {
    await fetchComponents(fund)
  }

  // For 3601/3602/2552, wait for state selection
  if (['3601', '3602', '2552'].includes(fund) && state) {
    await fetchComponents(fund, state)
  }
})


const fetchComponents = async (fund, state = null) => {
  try {
    let url = `/api/get-components-by-fund?fund=${fund}`
    if (state) url += `&state_id=${state}`

    const res = await fetch(url)
    availableComponents.value = res.ok ? await res.json() : []
  } catch (err) {
    console.error('Error fetching component data:', err)
    availableComponents.value = []
  }
}





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

const submitForm = (status) => {
  const fund = fundAllocationFor.value

  const payload = {
    fund_allocation_for: fund,
    financial_year: financialYear.value,
    budget_phase: budgetPhase.value,
    state_id: ['3601', '3602', '2552'].includes(fund) ? selectedState.value : 0,
    status: status, // 0 for draft, 1 for submit
    budget_array: []
  }

  budgetData.value.forEach((category, cIdx) => {
    category.budgetArray.forEach((budget, bIdx) => {
      availableComponents.value.forEach((component, pdIdx) => {
        const entry = {
          category: category.category,
          budget: budget.budget,
          budget_amount: budget.amount,
          sls_pd_name: component.name,
          amount: pdInputs.value[cIdx][bIdx][pdIdx] || 0
        }
        payload.budget_array.push(entry)
      })
    })
  })

  console.log('Payload:', payload)

  router.post(route('fund-allocation.store'), payload, {
    onSuccess: () => {
      alert(status === 1 ? 'Submitted successfully!' : 'Saved as draft!')
      resetForm()
    },
    onError: (errors) => {
      console.error('Validation failed:', errors)
      alert('Validation failed. Check input and try again.')
    }
  })
}


</script>

