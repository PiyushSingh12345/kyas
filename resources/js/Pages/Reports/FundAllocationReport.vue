<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
					<div class="page-inner allinsideform">
						<div class="page-header">
							<h3 class="fw-bold mb-3">MIS Reports & Dashboards
							</h3>
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
									<a href="Fund-Allocation.html">Fund Allocation Report
									</a>
								</li>


							</ul>
						</div>
						<div class="row">
							<div class="col-md-12">
								<div class="card">
									<div class="card-header">
										<div class="card-title">Fund Allocation Report</div>
									</div>
									<div class="card-body">
										<div class="row">
											

											<div class="col-md-4 col-lg-4">
												<div class="form-group">
													<label for="financialYearSelect">F.Y</label>
													<select class="form-select" v-model="financialYear" id="financialYearSelect">
														<option value="" disabled>Select Financial Year</option>
														<option v-for="year in financialYears" :key="year" :value="year">{{ year }}</option>
													</select>
												</div>
											</div>
											<div class="col-md-4 col-lg-4">
												<div class="form-group">
													<label for="budgetPhaseSelect">Budget Phase</label>
													<select class="form-select" v-model="budgetPhase" id="budgetPhaseSelect">
														<option value="" disabled>Budget Phase</option>
														<option v-for="phase in budgetPhases" :key="phase" :value="phase">{{ phase }}</option>
													</select>
												</div>
											</div>


<div class="col-md-4 col-lg-4">
												<div class="form-group">
													<label>Fund Allocation For</label><br>
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


										  

										<div v-if="canShowTable">
											<div class="table-responsive mt-5">
												<table class="table table-bordered table-head-bg-primary">
													
													<tbody>

														<tr>
															<td></td>
															<td width="20%">
																<label class="highlight_textbox">Amount Avail in {{ budgetPhase }}</label>
															</td>
															<td v-for="(component, index) in availableComponents" :key="'pd-label-' + index">
																<label class="highlight_textbox">{{ component.name }}</label>
															</td>
														</tr>
														<tr>
															<td></td>
															<td>
																<input type="text" class="form-control" :value="budgetData.reduce((total, category) => total + category.budgetArray.reduce((sum, item) => sum + item.amount, 0), 0).toLocaleString()" disabled />
															</td>
															<td v-for="(component, pdIndex) in availableComponents" :key="'total-pd-' + pdIndex">
																<input type="text" class="form-control" :value="budgetData.reduce((categorySum, category) => categorySum + category.budgetArray.reduce((budgetSum, item) => budgetSum + (item.sls_pd && item.sls_pd[component.name] ? item.sls_pd[component.name] : 0), 0), 0)" disabled />
															</td>
														</tr>
														<template v-for="(category, cIdx) in budgetData" :key="'category-' + cIdx">
															<tr>
																<td colspan="6" class="catgerybg">{{ category.category }}</td>
															</tr>
															<tr v-for="(budget, bIdx) in category.budgetArray" :key="'budget-row-' + cIdx + '-' + bIdx">
																<td>
																	<input style="width: 135px;" type="text" class="form-control tableform-control-withoutbg" :placeholder="budget.budget" disabled />
																</td>
																<td>
																	<input type="text" class="form-control tableform-control-withoutbg" :value="budget.amount" disabled />
																</td>
																<td v-for="(component, pdIndex) in availableComponents" :key="'pd-input-' + cIdx + '-' + bIdx + '-' + pdIndex">
																	<input type="text" class="form-control tableform-control-withoutbg" :value="budget.sls_pd && budget.sls_pd[component.name] ? budget.sls_pd[component.name] : 0" disabled />
																</td>
															</tr>
														</template>
													</tbody>
												</table>
											</div>
										</div>



									</div>

									<div class="card-footer">
										<div class="form">
											<div class="col-12 d-flex justify-content-center">
										
											
											

											  <button class="btn btn-success me-1">Close</button>
										
											
										  </div>
										</div>
									  </div>


								</div>
							</div>
						</div>
					</div>



<!-- 2nd radio button -->


					


				</div>
      <Footer />
    </div>
  </div>
</template>
<style scoped>
.wrapper {
  background-color: #f9fafb;
  min-height: 100vh;
}
.page-header h3 {
  letter-spacing: 0.05em;
}
.table thead.table-primary {
  background-color: #cfe2ff;
  border-color: #b6d4fe;
}
.table-hover tbody tr:hover {
  background-color: #f1f5ff;
}
.form-control-plaintext {
  background-color: transparent;
  border: none;
  padding-left: 0;
  font-size: 1rem;
}
.form-select {
  border-radius: 0.375rem;
  border: 1px solid #ced4da;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.25rem rgb(13 110 253 / 0.25);
  outline: none;
}
.editable-input {
  transition: background-color 0.3s ease, border-color 0.3s ease;
}
.editable-input:focus {
  border-color: #0d6efd;
  background-color: #e7f1ff;
  outline: none;
}
.sticky-header {
  position: sticky;
  top: 0;
  z-index: 2;
}

/* Buttons */
.btn {
  min-width: 110px;
  padding: 0.5rem 1.25rem;
  font-weight: 600;
  border-radius: 0.375rem;
  transition: background-color 0.25s ease, box-shadow 0.25s ease;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #0d6efd;
  border: none;
  color: white;
}
.btn-primary:hover:not(:disabled) {
  background-color: #0b5ed7;
  box-shadow: 0 0 10px rgba(13, 110, 253, 0.5);
}

.btn-outline-primary {
  border: 2px solid #0d6efd;
  color: #0d6efd;
  background: none;
}
.btn-outline-primary:hover:not(:disabled) {
  background-color: #0d6efd;
  color: white;
  box-shadow: 0 0 10px rgba(13, 110, 253, 0.5);
}

.btn-secondary {
  background-color: #6c757d;
  border: none;
  color: white;
}
.btn-secondary:hover:not(:disabled) {
  background-color: #5c636a;
  box-shadow: 0 0 8px rgba(108, 117, 125, 0.5);
}

/* Responsive tweaks */
@media (max-width: 575px) {
  .d-flex.justify-content-end.flex-wrap {
    justify-content: center !important;
  }
}
</style>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const fundAllocationFor = ref('2435')
const financialYear = ref('')
const budgetPhase = ref('')
const selectedState = ref('')
const states = ref([])
const availableComponents = ref([])
const budgetData = ref([])
const isLoading = ref(false)

const financialYears = ref([
  '2024-2025',
  '2023-2024',
  '2022-2023',
  '2021-2022',
])
const budgetPhases = ref(['BE', 'FE', 'RE'])

const fetchStates = async () => {
  try {
    const response = await fetch('/api/states')
    states.value = response.ok ? await response.json() : []
  } catch (error) {
    states.value = []
  }
}

const fetchComponents = async (fund, state = null) => {
  try {
    let url = `/api/get-components-by-fund?fund=${fund}`
    if (state) url += `&state_id=${state}`
    const res = await fetch(url)
    availableComponents.value = res.ok ? await res.json() : []
  } catch (err) {
    availableComponents.value = []
  }
}

const fetchBudgetData = async () => {
  if (!fundAllocationFor.value || !financialYear.value || !budgetPhase.value) return
  isLoading.value = true
  try {
    let url = `/api/budget-allocation?fund_allocation_for=${fundAllocationFor.value}&financial_year=${financialYear.value}&budget_phase=${budgetPhase.value}`
    if (['3601', '3602', '2552'].includes(fundAllocationFor.value) && selectedState.value) {
      url += `&state_id=${selectedState.value}`
    }
    const response = await fetch(url)
    budgetData.value = response.ok ? await response.json() : []
  } catch (error) {
    budgetData.value = []
  } finally {
    isLoading.value = false
  }
}

watch(fundAllocationFor, async (fund) => {
  if (['3601', '3602', '2552'].includes(fund)) {
    await fetchStates()
  } else {
    states.value = []
    selectedState.value = ''
  }
  await fetchComponents(fund, selectedState.value)
})

watch([fundAllocationFor, financialYear, budgetPhase, selectedState], async ([fund, year, phase, state]) => {
  if (!fund || !year || !phase) return
  if (['3601', '3602', '2552'].includes(fund) && !state) return
  await fetchComponents(fund, state)
  await fetchBudgetData()
})

onMounted(() => {
  fetchComponents(fundAllocationFor.value)
})

const canShowTable = computed(() => {
  return fundAllocationFor.value && financialYear.value && budgetPhase.value && (['2435'].includes(fundAllocationFor.value) || selectedState.value)
})
</script>
