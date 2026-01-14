<template>
	<div class="wrapper">
	  <Sidebar />
	  <div class="main-panel">
		<Header />
		  <div class="container">
			<div class="page-inner allinsideform">
			  <div class="page-header">
				<!-- <h3 class="fw-bold mb-3">Annual Action Plan Module</h3> -->
				<h3 class="fw-bold mb-3">Report</h3>
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
					<a href="#">PD wise Budget Allocation (AAP) with release - Budget Heads</a>
				  </li>
				</ul>
			  </div>
			  
			  <div class="row">
				<div class="col-md-12">
				  <div class="card">
					  <div class="card-header">
						  <div class="card-title d-flex justify-content-between align-items-center">
							  <span>PD wise Budget Allocation (AAP) with release - Budget Heads for FY 2025-26 (₹ In Lakhs)</span>
							  <!-- <button 
								  class="btn btn-outline-info btn-sm d-flex align-items-center" 
								  @click="viewHistory"
								  title="View Allocation History"
							  >
								  <i class="fas fa-history"></i> &nbsp;History
							  </button> -->
						  </div>
					  </div>
  
					  <div class="card-body">
						  <div v-if="loading" class="text-center">
							  <div class="spinner-border" role="status">
								  <span class="visually-hidden">Loading...</span>
							  </div>
							  <p class="mt-2">Loading budget heads and program divisions...</p>
						  </div>

						  <div v-else-if="categorizing" class="text-center">
							  <div class="spinner-border text-info" role="status">
								  <span class="visually-hidden">Categorizing...</span>
							  </div>
							  <p class="mt-2">Categorizing budget heads...</p>
						  </div>

						  <div v-else-if="error" class="alert alert-danger">
							  {{ error }}
						  </div>
  
						  <div v-else>
							  <!-- Filters Section -->
							  <div class="row mb-4">
								  <div class="col-12">
									  <div class="card border-primary">
										  <div class="card-header bg-primary text-white">
											  <h6 class="mb-0">
												  <i class="fas fa-filter me-2"></i>Filters
											  </h6>
										  </div>
										  <div class="card-body">
											  <div class="row g-3">
												  <!-- Financial Year Filter -->
												  <div class="col-md-3">
													  <label for="financialYear" class="form-label fw-bold">Financial Year</label>
													  <select 
														  id="financialYear" 
														  class="form-select" 
														  v-model="selectedFinancialYear"
														  @change="onFinancialYearChange"
													  >
														  <option value="2025-26">2025-26</option>
														  <option value="2024-25">2024-25</option>
														  <option value="2023-24">2023-24</option>
														  <option value="2022-23">2022-23</option>
													  </select>
												  </div>

												  <!-- Program Division Filter -->
												  <div class="col-md-3">
													  <label class="form-label fw-bold">Program Division <span class="text-danger">*</span></label>
													  <div class="custom-multiselect-container" @click.stop>
														  <div 
															  class="custom-multiselect-input form-control" 
															  :class="{ 'is-open': showPdDropdown }"
															  @click="togglePdDropdown"
														  >
															  <div class="selected-tags-wrapper">
																  <span 
																	  v-for="pdId in selectedProgramDivisions" 
																	  :key="pdId"
																	  class="custom-tag"
																  >
																	  {{ getProgramDivisionName(pdId) }}
																	  <span 
																		  class="tag-remove" 
																		  @click.stop="removeProgramDivision(pdId)"
																	  >×</span>
																  </span>
																  <input
																	  type="text"
																	  class="tag-input"
																	  v-model="pdSearchTerm"
																	  :placeholder="selectedProgramDivisions.length === 0 ? 'Select program divisions...' : ''"
																	  @input="filterProgramDivisions"
																	  @focus="showPdDropdown = true"
																	  @click.stop="showPdDropdown = true"
																  />
															  </div>
															  <div class="dropdown-arrows">
																  <i class="fas fa-chevron-up" v-if="showPdDropdown"></i>
																  <i class="fas fa-chevron-down" v-else></i>
															  </div>
														  </div>
														  <div 
															  class="custom-dropdown-menu" 
															  v-show="showPdDropdown"
															  @click.stop
														  >
															  <div 
																  v-for="pd in filteredAvailableProgramDivisions" 
																  :key="pd.division_id"
																  class="dropdown-item"
																  :class="{ 'highlighted': highlightedPdIndex === filteredAvailableProgramDivisions.indexOf(pd) }"
																  @click="selectProgramDivision(pd.division_id)"
																  @mouseenter="highlightedPdIndex = filteredAvailableProgramDivisions.indexOf(pd)"
															  >
																  {{ pd.division_name }}
															  </div>
															  <div v-if="filteredAvailableProgramDivisions.length === 0" class="dropdown-item text-muted">
																  No program divisions available
															  </div>
														  </div>
													  </div>
												  </div>

												  <!-- Major Head Filter -->
												  <div class="col-md-3">
													  <label class="form-label fw-bold">Major Head <span class="text-danger">*</span></label>
													  <div class="custom-multiselect-container" @click.stop>
														  <div 
															  class="custom-multiselect-input form-control" 
															  :class="{ 'is-open': showMajorHeadDropdown }"
															  @click="toggleMajorHeadDropdown"
														  >
															  <div class="selected-tags-wrapper">
																  <span 
																	  v-for="majorHeadCode in selectedMajorHeads" 
																	  :key="majorHeadCode"
																	  class="custom-tag"
																  >
																	  {{ getMajorHeadLabel(majorHeadCode) }}
																	  <span 
																		  class="tag-remove" 
																		  @click.stop="removeMajorHead(majorHeadCode)"
																	  >×</span>
																  </span>
																  <input
																	  type="text"
																	  class="tag-input"
																	  v-model="majorHeadSearchTerm"
																	  :placeholder="selectedMajorHeads.length === 0 ? 'Select major heads...' : ''"
																	  @input="filterMajorHeads"
																	  @focus="showMajorHeadDropdown = true"
																	  @click.stop="showMajorHeadDropdown = true"
																  />
															  </div>
															  <div class="dropdown-arrows">
																  <i class="fas fa-chevron-up" v-if="showMajorHeadDropdown"></i>
																  <i class="fas fa-chevron-down" v-else></i>
															  </div>
														  </div>
														  <div 
															  class="custom-dropdown-menu" 
															  v-show="showMajorHeadDropdown"
															  @click.stop
														  >
															  <div 
																  v-for="majorHead in filteredAvailableMajorHeads" 
																  :key="majorHead.code"
																  class="dropdown-item"
																  :class="{ 'highlighted': highlightedMajorHeadIndex === filteredAvailableMajorHeads.indexOf(majorHead) }"
																  @click="selectMajorHead(majorHead.code)"
																  @mouseenter="highlightedMajorHeadIndex = filteredAvailableMajorHeads.indexOf(majorHead)"
															  >
																  {{ majorHead.label }}
															  </div>
															  <div v-if="filteredAvailableMajorHeads.length === 0" class="dropdown-item text-muted">
																  No major heads available
															  </div>
														  </div>
													  </div>
												  </div>

												  <!-- Budget Head Filter -->
												  <div class="col-md-3">
													  <label class="form-label fw-bold">Budget Head <span class="text-danger">*</span></label>
													  <div class="custom-multiselect-container" @click.stop>
														  <div 
															  class="custom-multiselect-input form-control" 
															  :class="{ 'is-open': showBudgetHeadDropdown }"
															  @click="toggleBudgetHeadDropdown"
														  >
															  <div class="selected-tags-wrapper">
																  <span 
																	  v-for="bhId in selectedBudgetHeads" 
																	  :key="bhId"
																	  class="custom-tag"
																  >
																	  {{ getBudgetHeadDisplay(bhId) }}
																	  <span 
																		  class="tag-remove" 
																		  @click.stop="removeBudgetHead(bhId)"
																	  >×</span>
																  </span>
																  <input
																	  type="text"
																	  class="tag-input"
																	  v-model="budgetHeadSearchTerm"
																	  :placeholder="selectedBudgetHeads.length === 0 ? 'Select budget heads...' : ''"
																	  @input="filterBudgetHeads"
																	  @focus="showBudgetHeadDropdown = true"
																	  @click.stop="showBudgetHeadDropdown = true"
																  />
															  </div>
															  <div class="dropdown-arrows">
																  <i class="fas fa-chevron-up" v-if="showBudgetHeadDropdown"></i>
																  <i class="fas fa-chevron-down" v-else></i>
															  </div>
														  </div>
														  <div 
															  class="custom-dropdown-menu" 
															  v-show="showBudgetHeadDropdown"
															  @click.stop
														  >
															  <div 
																  v-for="bh in filteredAvailableBudgetHeads" 
																  :key="bh.bh_id"
																  class="dropdown-item"
																  :class="{ 'highlighted': highlightedBudgetHeadIndex === filteredAvailableBudgetHeads.indexOf(bh) }"
																  @click="selectBudgetHead(bh.bh_id)"
																  @mouseenter="highlightedBudgetHeadIndex = filteredAvailableBudgetHeads.indexOf(bh)"
															  >
																  {{ bh.budget_code }} - {{ bh.budget_name }}
															  </div>
															  <div v-if="filteredAvailableBudgetHeads.length === 0" class="dropdown-item text-muted">
																  No budget heads available
															  </div>
														  </div>
													  </div>
												  </div>
											  </div>

											  <!-- Column Visibility Filters -->
											  <div class="row mt-3">
												  <div class="col-12">
													  <label class="form-label fw-bold mb-2">Show/Hide Columns:</label>
													  <div class="d-flex gap-3 flex-wrap">
														  <div class="form-check">
															  <input 
																  class="form-check-input" 
																  type="checkbox" 
																  id="showAllocation"
																  v-model="showAllocation"
																  checked
															  >
															  <label class="form-check-label" for="showAllocation">
																  Allocation
															  </label>
														  </div>
														  <div class="form-check">
															  <input 
																  class="form-check-input" 
																  type="checkbox" 
																  id="showRelease"
																  v-model="showRelease"
																  checked
															  >
															  <label class="form-check-label" for="showRelease">
																  Release
															  </label>
														  </div>
														  <div class="form-check">
															  <input 
																  class="form-check-input" 
																  type="checkbox" 
																  id="showExpenditure"
																  v-model="showExpenditure"
																  checked
															  >
															  <label class="form-check-label" for="showExpenditure">
																  Expenditure
															  </label>
														  </div>
													  </div>
												  </div>
											  </div>

											  <!-- Filter Actions -->
											  <div class="row mt-3">
												  <div class="col-12">
													  <button 
														  class="btn btn-secondary btn-sm me-2" 
														  @click="clearFilters"
													  >
														  <i class="fas fa-times me-1"></i>Clear Filters
													  </button>
													  <span class="text-muted ms-2">
														  Showing {{ filteredCategorizedBudgetHeads.length }} categories
													  </span>
												  </div>
											  </div>
										  </div>
									  </div>
								  </div>
							  </div>

							  <!-- Summary Section -->
							  <!-- <div class="row mb-3">
								  <div class="col-12">
									  <div class="card">
										  <div class="card-header">
											  <h6 class="mb-0">
												  <i class="fas fa-calculator me-2"></i>
												  Budget Allocation Summary
											  </h6>
										  </div>
										  <div class="card-body">
											  <div class="row">
												  <div class="col-md-6">
													  <h6>Major Head Totals:</h6>
													  <ul class="list-unstyled">
														  <li v-for="category in categorizedBudgetHeads.filter(c => c.type === 'major_head')" :key="category.id">
															  <span class="badge bg-primary me-2">{{ category.label }}</span>
															  <span class="fw-bold">{{ calculateMajorHeadTotal(category.label) }}</span>
														  </li>
													  </ul>
												  </div>
												  <div class="col-md-6">
													  <h6>Program Division Totals:</h6>
													  <ul class="list-unstyled">
														  <li v-for="pd in programDivisions" :key="pd.division_id">
															  <span class="badge bg-success me-2">{{ pd.division_name }}</span>
															  <span class="fw-bold">{{ calculateColumnTotal(pd.division_id) }}</span>
														  </li>
													  </ul>
												  </div>
											  </div>
										  </div>
									  </div>
								  </div>
							  </div> -->

							  <!-- Export Buttons -->
							  <div class="row mb-3">
								  <div class="col-12 d-flex justify-content-end align-items-center">
									  <div class="export-buttons">
										  <button 
											  class="btn btn-success btn-sm me-2" 
											  @click="exportToExcel"
											  title="Export to Excel"
										  >
											  <i class="fas fa-file-excel me-1"></i>EXCEL
										  </button>
										  <button 
											  class="btn btn-info btn-sm me-2" 
											  @click="exportToCSV"
											  title="Export to CSV"
										  >
											  <i class="fas fa-file-csv me-1"></i>CSV
										  </button>
										  <button 
											  class="btn btn-danger btn-sm" 
											  @click="exportToPDF"
											  title="Export to PDF"
										  >
											  <i class="fas fa-file-pdf me-1"></i>PDF
										  </button>
									  </div>
								  </div>
							  </div>

							  <div class="table-responsive" id="reportTable">
								  <table class="table table-bordered table-hover align-middle text-center">
									  <thead class="table-dark">
										  <tr>
											<th class="align-middle fw-sticky" rowspan="3">Unified HoA-KY</th>
											  <th v-for="pd in filteredProgramDivisions" :key="pd.division_id" :colspan="getColumnSpan()">
												  {{ pd.division_name }}<br/>(Proposed by KY)<br/>by as per BE
											  </th>
											  <th class="align-middle" rowspan="3">Final Allocation</th>
											  <th class="align-middle bg-success" rowspan="3">Total Release</th>
											  <th class="align-middle bg-success" rowspan="3">Total Expenditure</th>
										  </tr>
										  <tr>
											  <template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												  <th v-if="showAllocation" class="sub-column-header">Allocation</th>
												  <th v-if="showRelease" class="sub-column-header">Release</th>
												  <th v-if="showExpenditure" class="sub-column-header">Expenditure</th>
											  </template>
										  </tr>
										  <tr>
											  <template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												  <th v-if="showAllocation">₹ In Lakhs</th>
												  <th v-if="showRelease">₹ In Lakhs</th>
												  <th v-if="showExpenditure">₹ In Lakhs</th>
											  </template>
										  </tr>
									  </thead>
										<tbody>
										  <!-- Categorized Budget Heads -->
										  <template v-for="category in filteredCategorizedBudgetHeads" :key="category.id">
											<!-- Major Head Row -->
											<tr v-if="category.type === 'major_head'" class="table-primary fw-bold">
											  <td class="text-start fw-sticky" :style="{ paddingLeft: '20px' }">
												{{ category.label }}
											  </td>
											  <template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												  <td v-if="showAllocation" class="text-center fw-bold total-cell" 
													   :title="`Allocation Total for ${pd.division_name} under ${category.label}`">
													{{ calculateMajorHeadTotalForPD(category.label, pd.division_id, 'allocation') }}
												  </td>
												  <td v-if="showRelease" class="text-center fw-bold total-cell" 
													   :title="`Release Total for ${pd.division_name} under ${category.label}`">
													{{ calculateMajorHeadTotalForPD(category.label, pd.division_id, 'release') }}
												  </td>
												  <td v-if="showExpenditure" class="text-center fw-bold total-cell" 
													   :title="`Expenditure Total for ${pd.division_name} under ${category.label}`">
													{{ calculateMajorHeadTotalForPD(category.label, pd.division_id, 'expenditure') }}
												  </td>
											  </template>
											  <td class="text-center fw-bold grand-total-cell" title="Grand total for all program divisions">
												{{ calculateMajorHeadTotal(category.label) }}
											  </td>
											  <td class="text-center fw-bold bg-success-subtle" title="Total Release for all program divisions">
												{{ calculateMajorHeadTotalRelease(category.label) }}
											  </td>
											  <td class="text-center fw-bold bg-success-subtle" title="Total Expenditure for all program divisions">
												{{ calculateMajorHeadTotalExpenditure(category.label) }}
											  </td>
											</tr>
											
											<!-- Subcategory Row -->
											<tr v-if="category.type === 'subcategory'" class="table-secondary">
											  <td class="text-start fw-sticky" :style="{ paddingLeft: '40px' }">
												{{ category.label }}
												<!-- <span v-if="category.budgetHeads.length === 1" class="badge bg-warning ms-2" title="Single record subcategory">
													<i class="fas fa-info-circle"></i> Single
												</span> -->
											  </td>
											  <template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												  <td v-if="showAllocation" class="text-center fw-bold total-cell"
													   :title="`Allocation Total for ${pd.division_name} under ${category.label}${category.budgetHeads.length === 1 ? ' (Single record)' : ''}`">
													{{ calculateSubcategoryTotalForPD(category.label, pd.division_id, category.parentMajorHead, 'allocation') }}
												  </td>
												  <td v-if="showRelease" class="text-center fw-bold total-cell"
													   :title="`Release Total for ${pd.division_name} under ${category.label}${category.budgetHeads.length === 1 ? ' (Single record)' : ''}`">
													{{ calculateSubcategoryTotalForPD(category.label, pd.division_id, category.parentMajorHead, 'release') }}
												  </td>
												  <td v-if="showExpenditure" class="text-center fw-bold total-cell"
													   :title="`Expenditure Total for ${pd.division_name} under ${category.label}${category.budgetHeads.length === 1 ? ' (Single record)' : ''}`">
													{{ calculateSubcategoryTotalForPD(category.label, pd.division_id, category.parentMajorHead, 'expenditure') }}
												  </td>
											  </template>
											  <td class="text-center fw-bold grand-total-cell" title="Grand total for all program divisions">
												{{ calculateSubcategoryTotal(category.label, category.parentMajorHead) }}
											  </td>
											  <td class="text-center fw-bold bg-success-subtle" title="Total Release for all program divisions">
												{{ calculateSubcategoryTotalRelease(category.label, category.parentMajorHead) }}
											  </td>
											  <td class="text-center fw-bold bg-success-subtle" title="Total Expenditure for all program divisions">
												{{ calculateSubcategoryTotalExpenditure(category.label, category.parentMajorHead) }}
											  </td>
											</tr>
											
											<!-- Individual Budget Head Rows -->
											<tr v-for="bh in category.budgetHeads" :key="`bh_${bh.bh_id}`" 
												 v-if="category.type === 'subcategory'"
												 class="budget-head-row">
											  <td class="text-start fw-sticky" :style="{ paddingLeft: '60px' }">
												{{ bh.budget_code }} - {{ bh.budget_name }}
											  </td>
											  <template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												  <td v-if="showAllocation" class="text-center">
													{{ formatToFiveDecimals(allocationData[bh.bh_id]?.[pd.division_id] || 0) }}
												  </td>
												  <td v-if="showRelease" class="text-center">
													{{ getReleaseAmount(bh.bh_id, pd.division_id) }}
												  </td>
												  <td v-if="showExpenditure" class="text-center">
													{{ getExpenditureAmount(bh.bh_id, pd.division_id) }}
												  </td>
											  </template>
											  <td class="text-center fw-bold bg-success-subtle">
												{{ calculateRowTotal(bh.bh_id) }}
											  </td>
											  <td class="text-center fw-bold bg-success-subtle">
												{{ calculateRowTotalRelease(bh.bh_id) }}
											  </td>
											  <td class="text-center fw-bold bg-success-subtle">
												{{ calculateRowTotalExpenditure(bh.bh_id) }}
											  </td>
											</tr>
										  </template>
										  
										  <!-- Total Row -->
										  <tr class="table-warning fw-bold">
											  <td class="fw-sticky">Total</td>
											  <template v-for="pd in filteredProgramDivisions" :key="pd.division_id">
												  <td v-if="showAllocation">
													  {{ calculateColumnTotal(pd.division_id, 'allocation') }}
												  </td>
												  <td v-if="showRelease">
													  {{ calculateColumnTotal(pd.division_id, 'release') }}
												  </td>
												  <td v-if="showExpenditure">
													  {{ calculateColumnTotal(pd.division_id, 'expenditure') }}
												  </td>
											  </template>
											  <td class="text-center grand-total">
												  {{ calculateGrandTotal() }}
											  </td>
											  <td class="text-center fw-bold bg-success-subtle">
												  {{ calculateGrandTotalRelease() }}
											  </td>
											  <td class="text-center fw-bold bg-success-subtle">
												  {{ calculateGrandTotalExpenditure() }}
											  </td>
										  </tr>
										</tbody>
								  </table>
							  </div>
  
							  <!-- Submit Button -->
							  <div class="row mt-4">
								  <div class="col-12 text-center">
									  <!-- <button 
										  @click="submitAllocation" 
										  class="btn btn-primary btn-lg me-3"
										  :disabled="submitting || categorizing"
									  >
										  <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
										  {{ submitting ? 'Saving...' : 'Submit Allocation' }}
									  </button> -->
									  
									  <!-- <button 
										  @click="testDifferentMajorHeads" 
										  class="btn btn-info btn-lg me-3"
										  :disabled="categorizedBudgetHeads.length === 0"
									  >
										  <i class="fas fa-calculator me-2"></i>
										  Test Major Heads
									  </button> -->
									  
									  <!-- <button 
										  @click="validateAllMajorHeads" 
										  class="btn btn-warning btn-lg me-3"
										  :disabled="categorizedBudgetHeads.length === 0"
									  >
										  <i class="fas fa-check-circle me-2"></i>
										  Validate All Major Heads
									  </button>
									   -->
									  <!-- <button 
										  @click="showMajorHeadSummary" 
										  class="btn btn-secondary btn-lg me-3"
										  :disabled="categorizedBudgetHeads.length === 0"
									  >
										  <i class="fas fa-list me-2"></i>
										  Major Head Summary
									  </button> -->
									  
									  <!-- <button 
										  @click="showUniqueSubcategories" 
										  class="btn btn-primary btn-lg me-3"
										  :disabled="categorizedBudgetHeads.length === 0"
									  >
										  <i class="fas fa-layer-group me-2"></i>
										  Unique Subcategories
									  </button> -->
								  </div>
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
  import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue'
  import { Link } from '@inertiajs/vue3'
  import axios from 'axios';
  import Header from '../Common/Header.vue'
  import Sidebar from '../Common/Sidebar.vue'
  import Footer from '../Common/Footer.vue'
  
  // Reactive data
  const budgetHeads = ref([])
  const programDivisions = ref([])
  const allocationData = ref({})
  const releaseData = ref({}) // Mother sanction release data
  const expenditureData = ref({}) // Daily sanction expenditure data
  const remarksData = ref({})
  const loading = ref(true)
  const error = ref(null)
  const submitting = ref(false)
  const categorizedBudgetHeads = ref([])
  const categorizing = ref(false)

  // Filter reactive data
  const selectedFinancialYear = ref('2025-26')
  const selectedProgramDivisions = ref([])
  const selectedMajorHeads = ref([])
  const selectedBudgetHeads = ref([])
  const pdSearchTerm = ref('')
  const majorHeadSearchTerm = ref('')
  const budgetHeadSearchTerm = ref('')
  const showPdDropdown = ref(false)
  const showMajorHeadDropdown = ref(false)
  const showBudgetHeadDropdown = ref(false)
  const highlightedPdIndex = ref(-1)
  const highlightedMajorHeadIndex = ref(-1)
  const highlightedBudgetHeadIndex = ref(-1)

  // Column visibility filters
  const showAllocation = ref(true)
  const showRelease = ref(true)
  const showExpenditure = ref(true)

  // Watch column visibility to ensure at least one column is always visible
  watch([showAllocation, showRelease, showExpenditure], ([allocation, release, expenditure]) => {
	const visibleCount = (allocation ? 1 : 0) + (release ? 1 : 0) + (expenditure ? 1 : 0)
	if (visibleCount === 0) {
	  // If all are unchecked, re-enable the one that was just unchecked
	  if (!allocation && release && expenditure) {
		showAllocation.value = true
	  } else if (allocation && !release && expenditure) {
		showRelease.value = true
	  } else if (allocation && release && !expenditure) {
		showExpenditure.value = true
	  } else {
		// Fallback: enable allocation
		showAllocation.value = true
	  }
	}
  })

  // Function to categorize budget heads based on the logic provided
  const categorizeBudgetHeads = (budgetHeadsList) => {
    if (!budgetHeadsList || budgetHeadsList.length === 0) {
      console.log('No budget heads to categorize')
      return []
    }
    
    const categories = []
    
    // Group budget heads by major head (first 4 digits)
    const groupedByMajorHead = {}
    
    budgetHeadsList.forEach(bh => {
      const budgetCode = bh.budget_code
      if (!budgetCode) {
        console.warn('Budget head missing budget_code:', bh)
        return
      }
      
      const first4Digits = budgetCode.substring(0, 4)
      
      if (!groupedByMajorHead[first4Digits]) {
        groupedByMajorHead[first4Digits] = []
      }
      groupedByMajorHead[first4Digits].push(bh)
    })
    
    console.log('Grouped by major head:', groupedByMajorHead)
    
    // Process each major head group
    Object.keys(groupedByMajorHead).forEach(majorHead => {
      const budgetHeadsInGroup = groupedByMajorHead[majorHead]
      
      // Add Major Head header
      categories.push({
        type: 'major_head',
        id: `major_${majorHead}`,
        label: `Major Head-${majorHead}`,
        budgetHeads: [],
        level: 0
      })
      
      // Create unique subcategories for each major head
      const subCategories = {}
      
      budgetHeadsInGroup.forEach(bh => {
        const budgetCode = bh.budget_code
        const numericCode = budgetCode.replace(/[^0-9]/g, '')
        
        if (numericCode.length >= 13) {
          const first4Digits = numericCode.substring(0, 4)
          const secondLast2Digits = numericCode.substring(11, 13)
          const middle3Digits = numericCode.substring(6, 9)
          const last2Digits = numericCode.substring(numericCode.length - 2)
          
          let subCategory = `General Component (${majorHead})` // Default with major head identifier
          
          // Apply the logic for subcategories with major head specific names
          if (first4Digits === '2435' && secondLast2Digits === '02') {
            subCategory = `EAP for CPP under MIDH (${majorHead})`
          } else if (first4Digits === '2435' && secondLast2Digits === '01') {
            subCategory = `EAP (NBM) (${majorHead})`
          } else if (first4Digits === '2552' && secondLast2Digits === '01') {
            subCategory = `EAP for CPP under MIDH (${majorHead})`
          } else if (middle3Digits === '796') {
            subCategory = `ST Component(796) (${majorHead})`
          } else if (middle3Digits === '789') {
            subCategory = `SC Component(789) (${majorHead})`
          } else if (last2Digits === '31' && secondLast2Digits === '01') {
            subCategory = `DAJUGA (${majorHead})`
          }
          
          if (!subCategories[subCategory]) {
            subCategories[subCategory] = []
          }
          subCategories[subCategory].push(bh)
        } else {
          // Fallback to General Component for codes that don't match the pattern
          const fallbackCategory = `General Component (${majorHead})`
          if (!subCategories[fallbackCategory]) {
            subCategories[fallbackCategory] = []
          }
          subCategories[fallbackCategory].push(bh)
        }
      })
      
      // Add subcategory headers and their budget heads
      Object.keys(subCategories).forEach(subCategoryName => {
        const budgetHeadsInSubCategory = subCategories[subCategoryName]
        
        if (budgetHeadsInSubCategory.length > 0) {
          // Add subcategory header
          categories.push({
            type: 'subcategory',
            id: `sub_${majorHead}_${subCategoryName.replace(/\s+/g, '_').replace(/[()]/g, '')}`,
            label: subCategoryName,
            budgetHeads: budgetHeadsInSubCategory,
            level: 1,
            parentMajorHead: majorHead
          })
        }
      })
    })
    
    console.log('Final categorized structure:', categories)
    return categories
  }
  
  // Fetch budget heads from API
  const fetchBudgetHeads = async () => {
	try {
	  const response = await fetch('/api/aap-budget-heads')
	  if (!response.ok) throw new Error('Failed to fetch budget heads')
	  const data = await response.json()
	  budgetHeads.value = data
	  console.log('Budget heads fetched successfully:', data)
	  
	  // Categorize the budget heads
	  categorizing.value = true
	  try {
		categorizedBudgetHeads.value = categorizeBudgetHeads(data)
		console.log('Categorized budget heads:', categorizedBudgetHeads.value)
	  } catch (categorizeError) {
		console.error('Error categorizing budget heads:', categorizeError)
		error.value = 'Failed to categorize budget heads: ' + categorizeError.message
	  } finally {
		categorizing.value = false
	  }
	} catch (err) {
	  console.error('Error fetching budget heads:', err)
	  error.value = 'Failed to load budget heads: ' + err.message
	}
  }
  
  // Fetch program divisions from API
  const fetchProgramDivisions = async () => {
	try {
	  const response = await fetch('/api/aap-program-divisions')
	  if (!response.ok) throw new Error('Failed to fetch program divisions')
	  const data = await response.json()
	  programDivisions.value = data
	  console.log('Program divisions fetched successfully:', data)
	} catch (err) {
	  console.error('Error fetching program divisions:', err)
	  error.value = 'Failed to load program divisions'
	}
  }
  
  // Fetch existing allocation data
  const fetchExistingAllocations = async () => {
	try {
	  const response = await fetch('/api/pdwise-aap-allocation?financial_year=2025-26')
	  if (!response.ok) throw new Error('Failed to fetch existing allocations')
	  const result = await response.json()
	  
	  console.log('Existing allocations result:', result)
	  
	  if (result.success && result.data) {
		// Populate existing data
		Object.keys(result.data).forEach(bhId => {
		  const bhAllocations = result.data[bhId]
		  console.log(`Processing budget head ${bhId}:`, bhAllocations)
		  
		  Object.keys(bhAllocations).forEach(pdId => {
			const allocation = bhAllocations[pdId]
			console.log(`Processing PD ${pdId} for budget head ${bhId}:`, allocation)
			
			if (allocationData.value[bhId] && allocationData.value[bhId][pdId] !== undefined) {
			  // Use exact amount as stored - preserve the exact value from DB without parsing to float first
			  // This prevents rounding issues (e.g., 4740.97500 should not become 4740.98000)
			  const amount = allocation.amount
			  allocationData.value[bhId][pdId] = formatToFiveDecimals(amount)
			  console.log(`Set amount for budget head ${bhId}, PD ${pdId}: ${formatToFiveDecimals(amount)} (original: ${amount})`)
			} else {
			  console.log(`Data structure not ready for budget head ${bhId}, PD ${pdId}`)
			}
		  })
		})
		
		// Populate remarks if they exist
		if (result.remarks) {
		  console.log('Processing remarks:', result.remarks)
		  Object.keys(result.remarks).forEach(bhId => {
			if (remarksData.value[bhId] !== undefined) {
			  remarksData.value[bhId] = result.remarks[bhId]
			  console.log(`Set remark for budget head ${bhId}: ${result.remarks[bhId]}`)
			}
		  })
		}
	  } else {
		console.log('No existing data found or API returned error')
	  }
	} catch (err) {
	  console.error('Error fetching existing allocations:', err)
	  // Don't show error for existing data, just log it
	}
  }

  // Fetch mother sanction release data
  const fetchReleaseData = async () => {
	try {
	  const response = await fetch('/api/mother-sanction-release-data?financial_year=2025-26')
	  if (!response.ok) {
		const errorText = await response.text()
		console.error('Failed to fetch release data:', response.status, errorText)
		throw new Error(`Failed to fetch release data: ${response.status}`)
	  }
	  const result = await response.json()
	  
	  console.log('Release data API response:', result)
	  
	  if (result.success && result.data) {
		releaseData.value = result.data
		console.log('Release data fetched and stored:', releaseData.value)
		console.log('Release data debug info:', result.debug)
		
		// Log sample data
		const sampleKeys = Object.keys(releaseData.value).slice(0, 3)
		sampleKeys.forEach(bhId => {
		  console.log(`Release data for budget head ${bhId}:`, releaseData.value[bhId])
		})
	  } else {
		console.warn('Release data API returned success=false or no data:', result)
	  }
	} catch (err) {
	  console.error('Error fetching release data:', err)
	}
  }

  // Fetch daily sanction expenditure data
  const fetchExpenditureData = async () => {
	try {
	  const response = await fetch('/api/daily-sanction-expenditure-data?financial_year=2025-26')
	  if (!response.ok) {
		const errorText = await response.text()
		console.error('Failed to fetch expenditure data:', response.status, errorText)
		throw new Error(`Failed to fetch expenditure data: ${response.status}`)
	  }
	  const result = await response.json()
	  
	  console.log('Expenditure data API response:', result)
	  
	  if (result.success && result.data) {
		expenditureData.value = result.data
		console.log('Expenditure data fetched and stored:', expenditureData.value)
		console.log('Expenditure data debug info:', result.debug)
		
		// Log sample data
		const sampleKeys = Object.keys(expenditureData.value).slice(0, 3)
		sampleKeys.forEach(bhId => {
		  console.log(`Expenditure data for budget head ${bhId}:`, expenditureData.value[bhId])
		})
	  } else {
		console.warn('Expenditure data API returned success=false or no data:', result)
	  }
	} catch (err) {
	  console.error('Error fetching expenditure data:', err)
	}
  }

  // Get release amount for a budget head and PD
  const getReleaseAmount = (bhId, pdId) => {
	if (!releaseData.value) {
	  return '0.00000'
	}
	// Convert to string to match API response keys
	const bhIdStr = String(bhId)
	const pdIdStr = String(pdId)
	
	if (!releaseData.value[bhIdStr] || !releaseData.value[bhIdStr][pdIdStr]) {
	  return '0.00000'
	}
	const amount = releaseData.value[bhIdStr][pdIdStr] || 0
	return formatToFiveDecimals(amount)
  }

  // Get expenditure amount for a budget head and PD
  const getExpenditureAmount = (bhId, pdId) => {
	if (!expenditureData.value) {
	  return '0.00000'
	}
	// Convert to string to match API response keys
	const bhIdStr = String(bhId)
	const pdIdStr = String(pdId)
	
	if (!expenditureData.value[bhIdStr] || !expenditureData.value[bhIdStr][pdIdStr]) {
	  return '0.00000'
	}
	const amount = expenditureData.value[bhIdStr][pdIdStr] || 0
	return formatToFiveDecimals(amount)
  }
  
  // Get all budget heads from categorized structure
  const getAllBudgetHeads = () => {
	const allBudgetHeads = []
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'subcategory') {
		allBudgetHeads.push(...category.budgetHeads)
	  }
	})
	return allBudgetHeads
  }

  // Computed property for available major heads
  const availableMajorHeads = computed(() => {
	const majorHeads = []
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'major_head') {
		const majorHeadCode = category.label.replace('Major Head-', '')
		if (!majorHeads.find(mh => mh.code === majorHeadCode)) {
		  majorHeads.push({
			code: majorHeadCode,
			label: category.label
		  })
		}
	  }
	})
	return majorHeads.sort((a, b) => a.code.localeCompare(b.code))
  })

  // Computed property for filtered available major heads
  const filteredAvailableMajorHeads = computed(() => {
	const available = availableMajorHeads.value.filter(mh => !selectedMajorHeads.value.includes(mh.code))
	if (!majorHeadSearchTerm.value) {
	  return available
	}
	const search = majorHeadSearchTerm.value.toLowerCase()
	return available.filter(mh => 
	  mh.label.toLowerCase().includes(search) || mh.code.toLowerCase().includes(search)
	)
  })

  // Computed property for available program divisions
  const availableProgramDivisions = computed(() => {
	return programDivisions.value.filter(pd => !selectedProgramDivisions.value.includes(pd.division_id))
  })

  // Computed property for filtered available program divisions
  const filteredAvailableProgramDivisions = computed(() => {
	if (!pdSearchTerm.value) {
	  return availableProgramDivisions.value
	}
	const search = pdSearchTerm.value.toLowerCase()
	return availableProgramDivisions.value.filter(pd => 
	  pd.division_name.toLowerCase().includes(search)
	)
  })

  // Computed property for filtered program divisions
  const filteredProgramDivisions = computed(() => {
	if (selectedProgramDivisions.value.length === 0) {
	  return programDivisions.value
	}
	return programDivisions.value.filter(pd => selectedProgramDivisions.value.includes(pd.division_id))
  })

  // Computed property for available budget heads
  const availableBudgetHeads = computed(() => {
	return budgetHeads.value.filter(bh => !selectedBudgetHeads.value.includes(bh.bh_id))
  })

  // Computed property for filtered available budget heads
  const filteredAvailableBudgetHeads = computed(() => {
	if (!budgetHeadSearchTerm.value) {
	  return availableBudgetHeads.value
	}
	const search = budgetHeadSearchTerm.value.toLowerCase()
	return availableBudgetHeads.value.filter(bh => 
	  bh.budget_code?.toLowerCase().includes(search) || 
	  bh.budget_name?.toLowerCase().includes(search)
	)
  })

  // Computed property for filtered categorized budget heads
  const filteredCategorizedBudgetHeads = computed(() => {
	let filtered = categorizedBudgetHeads.value

	// Filter by major heads
	if (selectedMajorHeads.value.length > 0) {
	  filtered = filtered.filter(category => {
		if (category.type === 'major_head') {
		  const majorHeadCode = category.label.replace('Major Head-', '')
		  return selectedMajorHeads.value.includes(majorHeadCode)
		} else if (category.type === 'subcategory') {
		  return selectedMajorHeads.value.includes(category.parentMajorHead)
		}
		return false
	  })
	}

	// Filter by selected budget heads
	if (selectedBudgetHeads.value.length > 0) {
	  filtered = filtered.map(category => {
		if (category.type === 'subcategory') {
		  const filteredBudgetHeads = category.budgetHeads.filter(bh => 
			selectedBudgetHeads.value.includes(bh.bh_id)
		  )
		  
		  if (filteredBudgetHeads.length > 0) {
			return {
			  ...category,
			  budgetHeads: filteredBudgetHeads
			}
		  }
		  return null
		}
		return category
	  }).filter(category => category !== null)
	}

	// Ensure major head rows are shown if their subcategories are visible
	const result = []
	const visibleMajorHeads = new Set()
	
	filtered.forEach(category => {
	  if (category.type === 'subcategory') {
		visibleMajorHeads.add(category.parentMajorHead)
	  }
	})

	filtered.forEach(category => {
	  if (category.type === 'major_head') {
		const majorHeadCode = category.label.replace('Major Head-', '')
		if (visibleMajorHeads.has(majorHeadCode) || selectedMajorHeads.value.length === 0) {
		  result.push(category)
		}
	  } else if (category.type === 'subcategory') {
		if (category.budgetHeads && category.budgetHeads.length > 0) {
		  result.push(category)
		}
	  }
	})

	return result
  })

  // Helper functions for filters
  const getProgramDivisionName = (pdId) => {
	const pd = programDivisions.value.find(p => p.division_id == pdId)
	return pd ? pd.division_name : ''
  }

  const getMajorHeadLabel = (majorHeadCode) => {
	const majorHead = availableMajorHeads.value.find(mh => mh.code === majorHeadCode)
	return majorHead ? majorHead.label : `Major Head-${majorHeadCode}`
  }

  const getBudgetHeadDisplay = (bhId) => {
	const bh = budgetHeads.value.find(b => b.bh_id === bhId)
	return bh ? `${bh.budget_code} - ${bh.budget_name}` : ''
  }

  // Filter functions
  const togglePdDropdown = () => {
	showPdDropdown.value = !showPdDropdown.value
	if (showPdDropdown.value) {
	  highlightedPdIndex.value = -1
	}
  }

  const selectProgramDivision = (pdId) => {
	if (!selectedProgramDivisions.value.includes(pdId)) {
	  selectedProgramDivisions.value.push(pdId)
	  pdSearchTerm.value = ''
	}
	showPdDropdown.value = false
  }

  const removeProgramDivision = (pdId) => {
	const index = selectedProgramDivisions.value.indexOf(pdId)
	if (index > -1) {
	  selectedProgramDivisions.value.splice(index, 1)
	}
  }

  const filterProgramDivisions = () => {
	// Handled by computed property
  }

  const toggleMajorHeadDropdown = () => {
	showMajorHeadDropdown.value = !showMajorHeadDropdown.value
	if (showMajorHeadDropdown.value) {
	  highlightedMajorHeadIndex.value = -1
	}
  }

  const selectMajorHead = (majorHeadCode) => {
	if (!selectedMajorHeads.value.includes(majorHeadCode)) {
	  selectedMajorHeads.value.push(majorHeadCode)
	  majorHeadSearchTerm.value = ''
	}
	showMajorHeadDropdown.value = false
  }

  const removeMajorHead = (majorHeadCode) => {
	const index = selectedMajorHeads.value.indexOf(majorHeadCode)
	if (index > -1) {
	  selectedMajorHeads.value.splice(index, 1)
	}
  }

  const filterMajorHeads = () => {
	// Handled by computed property
  }

  const toggleBudgetHeadDropdown = () => {
	showBudgetHeadDropdown.value = !showBudgetHeadDropdown.value
	if (showBudgetHeadDropdown.value) {
	  highlightedBudgetHeadIndex.value = -1
	}
  }

  const selectBudgetHead = (bhId) => {
	if (!selectedBudgetHeads.value.includes(bhId)) {
	  selectedBudgetHeads.value.push(bhId)
	  budgetHeadSearchTerm.value = ''
	}
	showBudgetHeadDropdown.value = false
  }

  const removeBudgetHead = (bhId) => {
	const index = selectedBudgetHeads.value.indexOf(bhId)
	if (index > -1) {
	  selectedBudgetHeads.value.splice(index, 1)
	}
  }

  const filterBudgetHeads = () => {
	// Handled by computed property
  }

  // Function to calculate column span based on visible columns
  const getColumnSpan = () => {
	let count = 0
	if (showAllocation.value) count++
	if (showRelease.value) count++
	if (showExpenditure.value) count++
	return count || 1 // At least 1 column should be visible
  }

  const clearFilters = () => {
	selectedProgramDivisions.value = []
	selectedMajorHeads.value = []
	selectedBudgetHeads.value = []
	pdSearchTerm.value = ''
	majorHeadSearchTerm.value = ''
	budgetHeadSearchTerm.value = ''
	showPdDropdown.value = false
	showMajorHeadDropdown.value = false
	showBudgetHeadDropdown.value = false
	highlightedPdIndex.value = -1
	highlightedMajorHeadIndex.value = -1
	highlightedBudgetHeadIndex.value = -1
	// Reset column visibility to show all
	showAllocation.value = true
	showRelease.value = true
	showExpenditure.value = true
  }

  const onFinancialYearChange = () => {
	// Reload data for the new financial year
	loading.value = true
	Promise.all([
	  fetchBudgetHeads(),
	  fetchProgramDivisions(),
	  fetchExistingAllocations(),
	  fetchReleaseData(),
	  fetchExpenditureData()
	]).finally(() => {
	  loading.value = false
	})
  }

  // Close dropdowns when clicking outside
  const handleClickOutside = (event) => {
	if (!event.target.closest('.custom-multiselect-container')) {
	  showPdDropdown.value = false
	  showMajorHeadDropdown.value = false
	  showBudgetHeadDropdown.value = false
	}
  }

  // Get single record subcategories
  const getSingleRecordSubcategories = () => {
	return categorizedBudgetHeads.value.filter(category => 
	  category.type === 'subcategory' && category.budgetHeads.length === 1
	)
  }

  // Get multiple record subcategories
  const getMultipleRecordSubcategories = () => {
	return categorizedBudgetHeads.value.filter(category => 
	  category.type === 'subcategory' && category.budgetHeads.length > 1
	)
  }

  // Test major head filtering for a specific subcategory
  const testMajorHeadFiltering = (subcategoryLabel) => {
	const subcategory = categorizedBudgetHeads.value.find(category => 
	  category.type === 'subcategory' && category.label === subcategoryLabel
	)
	
	if (subcategory) {
	  console.log(`=== TESTING MAJOR HEAD FILTERING FOR: ${subcategoryLabel} ===`)
	  console.log(`Parent Major Head: ${subcategory.parentMajorHead}`)
	  console.log(`Total budget heads: ${subcategory.budgetHeads.length}`)
	  
	  subcategory.budgetHeads.forEach((bh, index) => {
		const budgetCode = bh.budget_code
		const first4Digits = budgetCode.substring(0, 4)
		const belongsToMajorHead = first4Digits === subcategory.parentMajorHead
		
		console.log(`Budget head ${index + 1}: ${budgetCode} - ${bh.budget_name}`)
		console.log(`  First 4 digits: ${first4Digits}`)
		console.log(`  Belongs to major head ${subcategory.parentMajorHead}: ${belongsToMajorHead}`)
		console.log(`  Amount: ${allocationData.value[bh.bh_id]?.[programDivisions.value[0]?.division_id] || 0}`)
	  })
	  
	  // Test the calculation with parent major head
	  const totalWithParent = calculateSubcategoryTotalForPD(subcategoryLabel, programDivisions.value[0]?.division_id, subcategory.parentMajorHead)
	  console.log(`Calculated total with parent major head ${subcategory.parentMajorHead}: ${totalWithParent}`)
	  
	  // Test the calculation with a different major head (if available)
	  if (programDivisions.value.length > 0) {
		const firstPD = programDivisions.value[0]
		const testMajorHead = '2552' // Example different major head
		const totalWithTestMajorHead = calculateSubcategoryTotalForPD(subcategoryLabel, firstPD.division_id, testMajorHead)
		console.log(`Calculated total with test major head ${testMajorHead}: ${totalWithTestMajorHead}`)
	  }
	  
	  console.log('================================================')
	} else {
	  console.log(`Subcategory "${subcategoryLabel}" not found`)
	}
  }

  // Function to test different major head calculations
  const testDifferentMajorHeads = () => {
	console.log('=== TESTING DIFFERENT MAJOR HEAD CALCULATIONS ===')
	
	// Get all unique major heads from the data
	const majorHeads = new Set()
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'subcategory') {
		majorHeads.add(category.parentMajorHead)
	  }
	})
	
	console.log('Available major heads:', Array.from(majorHeads))
	
	// Test each subcategory with different major heads
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'subcategory') {
		console.log(`\nSubcategory: ${category.label}`)
		console.log(`Default parent major head: ${category.parentMajorHead}`)
		
		// Test with parent major head
		if (programDivisions.value.length > 0) {
		  const firstPD = programDivisions.value[0]
		  const totalWithParent = calculateSubcategoryTotalForPD(category.label, firstPD.division_id, category.parentMajorHead)
		  console.log(`  Total with parent major head ${category.parentMajorHead}: ${totalWithParent}`)
		  
		  // Test with other major heads
		  majorHeads.forEach(majorHead => {
			if (majorHead !== category.parentMajorHead) {
			  const totalWithOther = calculateSubcategoryTotalForPD(category.label, firstPD.division_id, majorHead)
			  console.log(`  Total with major head ${majorHead}: ${totalWithOther}`)
			}
		  })
		}
	  }
	})
	
	console.log('\n=== UNIQUE SUBCATEGORY ANALYSIS ===')
	// Show how each major head has unique subcategories
	majorHeads.forEach(majorHead => {
	  const subcategoriesForMajorHead = categorizedBudgetHeads.value.filter(category => 
		category.type === 'subcategory' && category.parentMajorHead === majorHead
	  )
	  
	  console.log(`\nMajor Head ${majorHead}:`)
	  subcategoriesForMajorHead.forEach(subCategory => {
		console.log(`  - ${subCategory.label}`)
	  })
	})
	
	console.log('================================================')
  }

  // Function to validate all major heads are working correctly
  const validateAllMajorHeads = () => {
	console.log('=== VALIDATING ALL MAJOR HEADS ===')
	
	// Get all major head categories
	const majorHeadCategories = categorizedBudgetHeads.value.filter(category => category.type === 'major_head')
	console.log(`Found ${majorHeadCategories.length} major head categories:`, majorHeadCategories.map(c => c.label))
	
	majorHeadCategories.forEach(majorHead => {
	  const majorHeadCode = majorHead.label.replace('Major Head-', '')
	  console.log(`\nValidating ${majorHead.label} (Code: ${majorHeadCode})`)
	  
	  // Find subcategories under this major head
	  const subcategoriesUnderMajorHead = categorizedBudgetHeads.value.filter(category => 
		category.type === 'subcategory' && category.parentMajorHead === majorHeadCode
	  )
	  
	  console.log(`  Subcategories under ${majorHead.label}: ${subcategoriesUnderMajorHead.length}`)
	  subcategoriesUnderMajorHead.forEach(subCategory => {
		console.log(`    - ${subCategory.label} (${subCategory.budgetHeads.length} budget heads)`)
	  })
	  
	  // Test calculations for each program division
	  if (programDivisions.value.length > 0) {
		programDivisions.value.forEach(pd => {
		  const columnTotal = calculateMajorHeadTotalForPD(majorHead.label, pd.division_id)
		  console.log(`    Column total for ${pd.division_name}: ${columnTotal}`)
		})
		
		// Test grand total
		const grandTotal = calculateMajorHeadTotal(majorHead.label)
		console.log(`    Grand total: ${grandTotal}`)
	  }
	})
	
	console.log('================================================')
  }

  // Function to show summary of all major heads
  const showMajorHeadSummary = () => {
	console.log('=== MAJOR HEAD SUMMARY ===')
	
	const majorHeadCategories = categorizedBudgetHeads.value.filter(category => category.type === 'major_head')
	
	if (majorHeadCategories.length === 0) {
	  console.log('No major head categories found')
	  return
	}
	
	console.log(`Total Major Heads: ${majorHeadCategories.length}`)
	
	majorHeadCategories.forEach(majorHead => {
	  const majorHeadCode = majorHead.label.replace('Major Head-', '')
	  const subcategoriesUnderMajorHead = categorizedBudgetHeads.value.filter(category => 
		category.type === 'subcategory' && category.parentMajorHead === majorHeadCode
	  )
	  
	  console.log(`\n${majorHead.label}:`)
	  console.log(`  Code: ${majorHeadCode}`)
	  console.log(`  Subcategories: ${subcategoriesUnderMajorHead.length}`)
	  console.log(`  Budget Heads: ${subcategoriesUnderMajorHead.reduce((total, sub) => total + sub.budgetHeads.length, 0)}`)
	  
	  // Show subcategory breakdown
	  subcategoriesUnderMajorHead.forEach(subCategory => {
		console.log(`    ${subCategory.label}: ${subCategory.budgetHeads.length} budget heads`)
	  })
	  
	  // Show totals if program divisions are available
	  if (programDivisions.value.length > 0) {
		console.log(`  Totals:`)
		programDivisions.value.forEach(pd => {
		  const columnTotal = calculateMajorHeadTotalForPD(majorHead.label, pd.division_id)
		  console.log(`    ${pd.division_name}: ${columnTotal}`)
		})
		const grandTotal = calculateMajorHeadTotal(majorHead.label)
		console.log(`    Grand Total: ${grandTotal}`)
	  }
	})
	
	console.log('========================')
  }

  // Function to show unique subcategories for each major head
  const showUniqueSubcategories = () => {
	console.log('=== UNIQUE SUBCATEGORIES FOR EACH MAJOR HEAD ===')
	
	const majorHeadCategories = categorizedBudgetHeads.value.filter(category => category.type === 'major_head')
	
	if (majorHeadCategories.length === 0) {
	  console.log('No major head categories found')
	  return
	}
	
	console.log(`Total Major Heads: ${majorHeadCategories.length}`)
	
	majorHeadCategories.forEach(majorHead => {
	  const majorHeadCode = majorHead.label.replace('Major Head-', '')
	  const subcategoriesUnderMajorHead = categorizedBudgetHeads.value.filter(category => 
		category.type === 'subcategory' && category.parentMajorHead === majorHeadCode
	  )
	  
	  console.log(`\n${majorHead.label} (Code: ${majorHeadCode}):`)
	  console.log(`  Unique Subcategories: ${subcategoriesUnderMajorHead.length}`)
	  
	  // Show unique subcategories for this major head
	  subcategoriesUnderMajorHead.forEach(subCategory => {
		console.log(`    - ${subCategory.label}`)
		console.log(`      Budget Heads: ${subCategory.budgetHeads.length}`)
		
		// Show sample budget heads
		subCategory.budgetHeads.slice(0, 3).forEach((bh, index) => {
		  console.log(`        ${index + 1}. ${bh.budget_code} - ${bh.budget_name}`)
		})
		
		if (subCategory.budgetHeads.length > 3) {
		  console.log(`        ... and ${subCategory.budgetHeads.length - 3} more`)
		}
	  })
	  
	  // Show totals if program divisions are available
	  if (programDivisions.value.length > 0) {
		console.log(`  Totals:`)
		programDivisions.value.forEach(pd => {
		  const columnTotal = calculateMajorHeadTotalForPD(majorHead.label, pd.division_id)
		  console.log(`    ${pd.division_name}: ${columnTotal}`)
		})
		const grandTotal = calculateMajorHeadTotal(majorHead.label)
		console.log(`    Grand Total: ${grandTotal}`)
	  }
	})
	
	console.log('==============================================')
  }

  // Helper function to format number to exactly 5 decimal places without rounding
  // This preserves the exact value from the database
  const formatToFiveDecimals = (value) => {
	if (value === null || value === undefined || value === '') {
	  return '0.00000'
	}
	
	// Convert to string first to preserve precision
	const valueStr = String(value)
	
	// If it's already a string with decimal, preserve it
	if (valueStr.includes('.')) {
	  const parts = valueStr.split('.')
	  const integerPart = parts[0]
	  let decimalPart = parts[1] || ''
	  
	  // Pad or truncate to exactly 5 decimal places
	  if (decimalPart.length > 5) {
		// Truncate, don't round
		decimalPart = decimalPart.substring(0, 5)
	  } else {
		// Pad with zeros
		decimalPart = decimalPart.padEnd(5, '0')
	  }
	  
	  return `${integerPart}.${decimalPart}`
	} else {
	  // No decimal point, add .00000
	  return `${valueStr}.00000`
	}
  }

  // Format input value to 5 decimal places when field loses focus
  const formatInputValue = (bhId, pdId) => {
	const currentValue = allocationData.value[bhId][pdId]
	if (currentValue !== null && currentValue !== undefined && currentValue !== '') {
	  const numValue = parseFloat(currentValue)
	  if (!isNaN(numValue)) {
		allocationData.value[bhId][pdId] = formatToFiveDecimals(numValue)
	  }
	}
  }

  // Helper function to add two numbers with 5 decimal precision
  const addWithPrecision = (a, b) => {
	const numA = parseFloat(a) || 0
	const numB = parseFloat(b) || 0
	// Multiply by 100000, add, then divide to maintain precision
	return Math.round((numA * 100000) + (numB * 100000)) / 100000
  }

  // Initialize allocation data structure
  const initializeAllocationData = () => {
	console.log('Initializing allocation data structure...')
	
	// Get all budget heads from categorized structure
	const allBudgetHeads = getAllBudgetHeads()
	console.log('All budget heads from categories:', allBudgetHeads)
	
	allBudgetHeads.forEach(bh => {
	  allocationData.value[bh.bh_id] = {}
	  remarksData.value[bh.bh_id] = ''
	  
	  programDivisions.value.forEach(pd => {
		allocationData.value[bh.bh_id][pd.division_id] = ''
	  })
	  
	  console.log(`Initialized data structure for budget head ${bh.bh_id}:`, allocationData.value[bh.bh_id])
	})
	
	console.log('Final allocation data structure:', allocationData.value)
  }
  
  // Calculate column total
  const calculateColumnTotal = (pdId, columnType = 'allocation') => {
	let total = 0
	const allBudgetHeads = getAllBudgetHeads()
	allBudgetHeads.forEach(bh => {
	  let value = 0
	  if (columnType === 'allocation') {
		value = parseFloat(allocationData.value[bh.bh_id]?.[pdId]) || 0
	  } else if (columnType === 'release') {
		value = parseFloat(releaseData.value[bh.bh_id]?.[pdId]) || 0
	  } else if (columnType === 'expenditure') {
		value = parseFloat(expenditureData.value[bh.bh_id]?.[pdId]) || 0
	  }
	  total = addWithPrecision(total, value)
	})
	return formatToFiveDecimals(total)
  }

  // Calculate row total for a specific budget head
  const calculateRowTotal = (bhId) => {
	let total = 0
	filteredProgramDivisions.value.forEach(pd => {
	  const value = parseFloat(allocationData.value[bhId][pd.division_id]) || 0
	  total = addWithPrecision(total, value)
	})
	return formatToFiveDecimals(total)
  }

  // Calculate row total release for a specific budget head
  const calculateRowTotalRelease = (bhId) => {
	let total = 0
	filteredProgramDivisions.value.forEach(pd => {
	  const value = parseFloat(getReleaseAmount(bhId, pd.division_id)) || 0
	  total = addWithPrecision(total, value)
	})
	return formatToFiveDecimals(total)
  }

  // Calculate row total expenditure for a specific budget head
  const calculateRowTotalExpenditure = (bhId) => {
	let total = 0
	filteredProgramDivisions.value.forEach(pd => {
	  const value = parseFloat(getExpenditureAmount(bhId, pd.division_id)) || 0
	  total = addWithPrecision(total, value)
	})
	return formatToFiveDecimals(total)
  }

  // Calculate grand total (sum of all allocations)
  const calculateGrandTotal = () => {
	let total = 0
	const allBudgetHeads = getAllBudgetHeads()
	allBudgetHeads.forEach(bh => {
	  filteredProgramDivisions.value.forEach(pd => {
		const value = parseFloat(allocationData.value[bh.bh_id][pd.division_id]) || 0
		total = addWithPrecision(total, value)
	  })
	})
	return formatToFiveDecimals(total)
  }

  // Calculate grand total release (sum of all releases)
  const calculateGrandTotalRelease = () => {
	let total = 0
	const allBudgetHeads = getAllBudgetHeads()
	allBudgetHeads.forEach(bh => {
	  filteredProgramDivisions.value.forEach(pd => {
		const value = parseFloat(getReleaseAmount(bh.bh_id, pd.division_id)) || 0
		total = addWithPrecision(total, value)
	  })
	})
	return formatToFiveDecimals(total)
  }

  // Calculate grand total expenditure (sum of all expenditures)
  const calculateGrandTotalExpenditure = () => {
	let total = 0
	const allBudgetHeads = getAllBudgetHeads()
	allBudgetHeads.forEach(bh => {
	  filteredProgramDivisions.value.forEach(pd => {
		const value = parseFloat(getExpenditureAmount(bh.bh_id, pd.division_id)) || 0
		total = addWithPrecision(total, value)
	  })
	})
	return formatToFiveDecimals(total)
  }

  // Calculate total for a specific major head
  const calculateMajorHeadTotal = (majorHeadLabel) => {
	let total = 0
	const majorHeadCode = majorHeadLabel.replace('Major Head-', '')
	
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'major_head' && category.label === majorHeadLabel) {
		// Find all subcategories under this major head
		categorizedBudgetHeads.value.forEach(subCategory => {
		  if (subCategory.type === 'subcategory' && subCategory.parentMajorHead === majorHeadCode) {
			// Calculate total for this subcategory across all program divisions
			const subcategoryTotal = calculateSubcategoryTotal(subCategory.label, majorHeadCode)
			const value = parseFloat(subcategoryTotal) || 0
			total = addWithPrecision(total, value)
		  }
		})
	  }
	})
	return formatToFiveDecimals(total)
  }

  // Calculate total release for a specific major head
  const calculateMajorHeadTotalRelease = (majorHeadLabel) => {
	let total = 0
	const majorHeadCode = majorHeadLabel.replace('Major Head-', '')
	
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'major_head' && category.label === majorHeadLabel) {
		categorizedBudgetHeads.value.forEach(subCategory => {
		  if (subCategory.type === 'subcategory' && subCategory.parentMajorHead === majorHeadCode) {
			const subcategoryTotal = calculateSubcategoryTotalRelease(subCategory.label, majorHeadCode)
			const value = parseFloat(subcategoryTotal) || 0
			total = addWithPrecision(total, value)
		  }
		})
	  }
	})
	return formatToFiveDecimals(total)
  }

  // Calculate total expenditure for a specific major head
  const calculateMajorHeadTotalExpenditure = (majorHeadLabel) => {
	let total = 0
	const majorHeadCode = majorHeadLabel.replace('Major Head-', '')
	
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'major_head' && category.label === majorHeadLabel) {
		categorizedBudgetHeads.value.forEach(subCategory => {
		  if (subCategory.type === 'subcategory' && subCategory.parentMajorHead === majorHeadCode) {
			const subcategoryTotal = calculateSubcategoryTotalExpenditure(subCategory.label, majorHeadCode)
			const value = parseFloat(subcategoryTotal) || 0
			total = addWithPrecision(total, value)
		  }
		})
	  }
	})
	return formatToFiveDecimals(total)
  }

  // Calculate total for a specific subcategory for a specific program division
  const calculateSubcategoryTotalForPD = (subcategoryLabel, pdId, majorHeadLabel = null, columnType = 'allocation') => {
	let total = 0
	let budgetHeadsInSubcategory = []
	let parentMajorHead = ''
	
	// First, find the subcategory and get its parent major head
	const subcategory = categorizedBudgetHeads.value.find(category => 
	  category.type === 'subcategory' && category.label === subcategoryLabel
	)
	
	if (subcategory) {
	  budgetHeadsInSubcategory = subcategory.budgetHeads
	  parentMajorHead = subcategory.parentMajorHead
	  
	  // If majorHeadLabel is provided, use it; otherwise use the parent major head
	  const targetMajorHead = majorHeadLabel || parentMajorHead
	  
	  // Calculate total only for budget heads that belong to the target major head
	  budgetHeadsInSubcategory.forEach(bh => {
		const budgetCode = bh.budget_code
		if (budgetCode && budgetCode.substring(0, 4) === targetMajorHead) {
		  let value = 0
		  if (columnType === 'allocation') {
			value = parseFloat(allocationData.value[bh.bh_id]?.[pdId]) || 0
		  } else if (columnType === 'release') {
			value = parseFloat(releaseData.value[bh.bh_id]?.[pdId]) || 0
		  } else if (columnType === 'expenditure') {
			value = parseFloat(expenditureData.value[bh.bh_id]?.[pdId]) || 0
		  }
		  total = addWithPrecision(total, value)
		}
	  })
	  
	  // If there's only one budget head under this subcategory, 
	  // show the same value as the individual budget head row
	  if (budgetHeadsInSubcategory.length === 1) {
		const singleBh = budgetHeadsInSubcategory[0]
		let singleBhTotal = 0
		if (columnType === 'allocation') {
		  singleBhTotal = parseFloat(allocationData.value[singleBh.bh_id]?.[pdId]) || 0
		} else if (columnType === 'release') {
		  singleBhTotal = parseFloat(releaseData.value[singleBh.bh_id]?.[pdId]) || 0
		} else if (columnType === 'expenditure') {
		  singleBhTotal = parseFloat(expenditureData.value[singleBh.bh_id]?.[pdId]) || 0
		}
		return formatToFiveDecimals(singleBhTotal)
	  }
	}
	
	return formatTotal(total)
  }

  // Calculate total for a specific subcategory
  const calculateSubcategoryTotal = (subcategoryLabel, majorHeadLabel = null) => {
	let total = 0
	let budgetHeadsInSubcategory = []
	let parentMajorHead = ''
	
	// First, find the subcategory and get its parent major head
	const subcategory = categorizedBudgetHeads.value.find(category => 
	  category.type === 'subcategory' && category.label === subcategoryLabel
	)
	
	if (subcategory) {
	  budgetHeadsInSubcategory = subcategory.budgetHeads
	  parentMajorHead = subcategory.parentMajorHead
	  
	  // If majorHeadLabel is provided, use it; otherwise use the parent major head
	  const targetMajorHead = majorHeadLabel || parentMajorHead
	  
	  // Calculate total only for budget heads that belong to the target major head
	  budgetHeadsInSubcategory.forEach(bh => {
		const budgetCode = bh.budget_code
		if (budgetCode && budgetCode.substring(0, 4) === targetMajorHead) {
		  filteredProgramDivisions.value.forEach(pd => {
			const value = parseFloat(allocationData.value[bh.bh_id]?.[pd.division_id]) || 0
			total = addWithPrecision(total, value)
		  })
		}
	  })
	  
	  // If there's only one budget head under this subcategory,
	  // the total should equal the single budget head's row total
	  if (budgetHeadsInSubcategory.length === 1) {
		const singleBh = budgetHeadsInSubcategory[0]
		let singleBhRowTotal = 0
		filteredProgramDivisions.value.forEach(pd => {
		  const value = parseFloat(allocationData.value[singleBh.bh_id]?.[pd.division_id]) || 0
		  singleBhRowTotal = addWithPrecision(singleBhRowTotal, value)
		})
		return formatToFiveDecimals(singleBhRowTotal)
	  }
	}
	
	return formatTotal(total)
  }

  // Calculate total release for a specific subcategory
  const calculateSubcategoryTotalRelease = (subcategoryLabel, majorHeadLabel = null) => {
	let total = 0
	let budgetHeadsInSubcategory = []
	let parentMajorHead = ''
	
	const subcategory = categorizedBudgetHeads.value.find(category => 
	  category.type === 'subcategory' && category.label === subcategoryLabel
	)
	
	if (subcategory) {
	  budgetHeadsInSubcategory = subcategory.budgetHeads
	  parentMajorHead = subcategory.parentMajorHead
	  
	  const targetMajorHead = majorHeadLabel || parentMajorHead
	  
	  budgetHeadsInSubcategory.forEach(bh => {
		const budgetCode = bh.budget_code
		if (budgetCode && budgetCode.substring(0, 4) === targetMajorHead) {
		  filteredProgramDivisions.value.forEach(pd => {
			const value = parseFloat(getReleaseAmount(bh.bh_id, pd.division_id)) || 0
			total = addWithPrecision(total, value)
		  })
		}
	  })
	  
	  if (budgetHeadsInSubcategory.length === 1) {
		const singleBh = budgetHeadsInSubcategory[0]
		let singleBhRowTotal = 0
		filteredProgramDivisions.value.forEach(pd => {
		  const value = parseFloat(getReleaseAmount(singleBh.bh_id, pd.division_id)) || 0
		  singleBhRowTotal = addWithPrecision(singleBhRowTotal, value)
		})
		return formatToFiveDecimals(singleBhRowTotal)
	  }
	}
	
	return formatToFiveDecimals(total)
  }

  // Calculate total expenditure for a specific subcategory
  const calculateSubcategoryTotalExpenditure = (subcategoryLabel, majorHeadLabel = null) => {
	let total = 0
	let budgetHeadsInSubcategory = []
	let parentMajorHead = ''
	
	const subcategory = categorizedBudgetHeads.value.find(category => 
	  category.type === 'subcategory' && category.label === subcategoryLabel
	)
	
	if (subcategory) {
	  budgetHeadsInSubcategory = subcategory.budgetHeads
	  parentMajorHead = subcategory.parentMajorHead
	  
	  const targetMajorHead = majorHeadLabel || parentMajorHead
	  
	  budgetHeadsInSubcategory.forEach(bh => {
		const budgetCode = bh.budget_code
		if (budgetCode && budgetCode.substring(0, 4) === targetMajorHead) {
		  filteredProgramDivisions.value.forEach(pd => {
			const value = parseFloat(getExpenditureAmount(bh.bh_id, pd.division_id)) || 0
			total = addWithPrecision(total, value)
		  })
		}
	  })
	  
	  if (budgetHeadsInSubcategory.length === 1) {
		const singleBh = budgetHeadsInSubcategory[0]
		let singleBhRowTotal = 0
		filteredProgramDivisions.value.forEach(pd => {
		  const value = parseFloat(getExpenditureAmount(singleBh.bh_id, pd.division_id)) || 0
		  singleBhRowTotal = addWithPrecision(singleBhRowTotal, value)
		})
		return formatToFiveDecimals(singleBhRowTotal)
	  }
	}
	
	return formatToFiveDecimals(total)
  }

  // Calculate total for a specific major head for a specific program division
  const calculateMajorHeadTotalForPD = (majorHeadLabel, pdId, columnType = 'allocation') => {
	let total = 0
	const majorHeadCode = majorHeadLabel.replace('Major Head-', '')
	
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'major_head' && category.label === majorHeadLabel) {
		// Find all subcategories under this major head
		categorizedBudgetHeads.value.forEach(subCategory => {
		  if (subCategory.type === 'subcategory' && subCategory.parentMajorHead === majorHeadCode) {
			// Calculate total for this subcategory in this program division
			const subcategoryTotal = calculateSubcategoryTotalForPD(subCategory.label, pdId, majorHeadCode, columnType)
			const value = parseFloat(subcategoryTotal) || 0
			total = addWithPrecision(total, value)
		  }
		})
	  }
	})
	return formatToFiveDecimals(total)
  }

  // Function to format totals nicely (now using 5 decimal places)
  const formatTotal = (value) => {
	return formatToFiveDecimals(value)
  }
  
  // Watch for changes in allocation data to trigger reactive updates
  watch(allocationData, () => {
	// This will trigger reactive updates when allocation data changes
	console.log('Allocation data changed, recalculating totals...')
  }, { deep: true })

  // Watch for changes in categorized budget heads to recalculate totals
  watch(categorizedBudgetHeads, () => {
	console.log('Categorized budget heads changed, recalculating totals...')
  }, { deep: true })
  
  // Submit allocation data
  const submitAllocation = async () => {
	submitting.value = true
	
	try {
	  // Prepare data for submission
	  const submissionData = []
	  
	  const allBudgetHeads = getAllBudgetHeads()
	  allBudgetHeads.forEach(bh => {
		programDivisions.value.forEach(pd => {
		  const amount = allocationData.value[bh.bh_id][pd.division_id]
		  // Allow zero values to be saved - check if amount is not null/undefined/empty string
		  // but allow 0 as a valid value
		  if (amount !== null && amount !== undefined && amount !== '') {
			// Parse and format to 5 decimals before submission to ensure exact precision
			const exactAmount = parseFloat(amount)
			// Check if it's a valid number (including 0)
			// This will save 0 when user explicitly enters 0
			if (!isNaN(exactAmount) && exactAmount >= 0) {
			  submissionData.push({
				financial_year: '2025-26',
				bh_id: bh.bh_id,
				pd_id: pd.division_id,
				amount: exactAmount, // Save exact amount as entered (including 0, will be stored with 5 decimal precision in DB)
				status: 1
			  })
			}
		  }
		})
	  })
  
	  // console.log("========================submissionData=======================");
	  // console.log(submissionData);
	  // return false;
  
	  if (submissionData.length === 0) {
		alert('Please enter at least one allocation amount (including 0)')
		submitting.value = false
		return
	  }
  
	  // Submit to backend
	  const response = await fetch('/api/pdwise-aap-allocation', {
		method: 'POST',
		headers: {
		  'Content-Type': 'application/json',
		  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
		},
		body: JSON.stringify({
		  allocations: submissionData,
		  remarks: remarksData.value
		})
	  })
  
	  if (!response.ok) {
		throw new Error('Failed to save allocation data')
	  }
  
	  const result = await response.json()
	  
	  // Show success message without interrupting the form
	  const successMessage = document.createElement('div')
	  successMessage.className = 'alert alert-success alert-dismissible fade show position-fixed'
	  successMessage.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;'
	  successMessage.innerHTML = `
		<strong>Success!</strong> Allocation data saved successfully.
		<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
	  `
	  document.body.appendChild(successMessage)
	  
	  // Auto-remove success message after 5 seconds
	  setTimeout(() => {
		if (successMessage.parentNode) {
		  successMessage.remove()
		}
	  }, 5000)
	  
	  // Don't reset form - keep data intact for user to see
	  // initializeAllocationData()
	  
	  // Refresh existing data from database to show the most current data
	  await fetchExistingAllocations()
	  
	} catch (err) {
	  console.error('Error submitting allocation:', err)
	  alert('Failed to save allocation data: ' + err.message)
	} finally {
	  submitting.value = false
	}
  }

  // Debug categorization function
  const debugCategorization = () => {
	console.log('Current budget heads:', budgetHeads.value)
	console.log('Categorized budget heads:', categorizedBudgetHeads.value)
	console.log('Program divisions:', programDivisions.value)
	console.log('Allocation data:', allocationData.value)
	console.log('Remarks data:', remarksData.value)
	
	// Debug major head structure
	console.log('=== MAJOR HEAD STRUCTURE DEBUG ===')
	const majorHeadCategories = categorizedBudgetHeads.value.filter(category => category.type === 'major_head')
	majorHeadCategories.forEach(majorHead => {
	  const majorHeadCode = majorHead.label.replace('Major Head-', '')
	  console.log(`Major Head: ${majorHead.label} (Code: ${majorHeadCode})`)
	  
	  // Find subcategories under this major head
	  const subcategoriesUnderMajorHead = categorizedBudgetHeads.value.filter(category => 
		category.type === 'subcategory' && category.parentMajorHead === majorHeadCode
	  )
	  
	  console.log(`  Subcategories: ${subcategoriesUnderMajorHead.length}`)
	  subcategoriesUnderMajorHead.forEach(subCategory => {
		console.log(`    - ${subCategory.label}: ${subCategory.budgetHeads.length} budget heads`)
		subCategory.budgetHeads.forEach(bh => {
		  console.log(`      * ${bh.budget_code} - ${bh.budget_name}`)
		})
	  })
	})
	console.log('=========================================')
	
	// Debug single record subcategories
	console.log('=== SINGLE RECORD SUBCATEGORIES DEBUG ===')
	const singleRecordSubcategories = getSingleRecordSubcategories()
	const multipleRecordSubcategories = getMultipleRecordSubcategories()
	
	console.log(`Total subcategories: ${categorizedBudgetHeads.value.filter(c => c.type === 'subcategory').length}`)
	console.log(`Single record subcategories: ${singleRecordSubcategories.length}`)
	console.log(`Multiple record subcategories: ${multipleRecordSubcategories.length}`)
	
	singleRecordSubcategories.forEach(category => {
	  console.log(`Single Record: ${category.label}`)
	  const singleBh = category.budgetHeads[0]
	  console.log(`  Budget head: ${singleBh.budget_code} - ${singleBh.budget_name}`)
	  console.log(`  Parent Major Head: ${category.parentMajorHead}`)
	  console.log(`  First 4 digits: ${singleBh.budget_code.substring(0, 4)}`)
	})
	
	multipleRecordSubcategories.forEach(category => {
	  console.log(`Multiple Records: ${category.label} (${category.budgetHeads.length} budget heads)`)
	  console.log(`  Parent Major Head: ${category.parentMajorHead}`)
	  category.budgetHeads.forEach((bh, index) => {
		console.log(`    Budget head ${index + 1}: ${bh.budget_code} - ${bh.budget_name}`)
		console.log(`    First 4 digits: ${bh.budget_code.substring(0, 4)}`)
	  })
	})
	console.log('=========================================')
  }
  
  // Refresh totals function
  const refreshTotals = () => {
	console.log('Refreshing totals...')
	// Re-calculate all totals and update the table
	const allBudgetHeads = getAllBudgetHeads()
	allBudgetHeads.forEach(bh => {
	  programDivisions.value.forEach(pd => {
		const currentValue = parseFloat(allocationData.value[bh.bh_id][pd.division_id]) || 0
		const newTotal = calculateRowTotal(bh.bh_id)
		if (currentValue !== parseFloat(newTotal)) {
		  allocationData.value[bh.bh_id][pd.division_id] = newTotal
		  console.log(`Updated allocation for ${bh.budget_code} - ${bh.budget_name}, PD ${pd.division_name}: ${newTotal}`)
		}
	  })
	})
	// Re-calculate major head totals
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'major_head') {
		const majorHeadLabel = category.label.replace('Major Head-', '')
		const newTotal = calculateMajorHeadTotal(majorHeadLabel)
		console.log(`Updated Major Head Total for ${majorHeadLabel}: ${newTotal}`)
	  }
	})
	// Re-calculate subcategory totals
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'subcategory') {
		const subcategoryLabel = category.label
		const newTotal = calculateSubcategoryTotal(subcategoryLabel)
		console.log(`Updated Subcategory Total for ${subcategoryLabel}: ${newTotal}`)
	  }
	})
	// Re-calculate grand total
	const newGrandTotal = calculateGrandTotal()
	console.log(`Updated Grand Total: ${newGrandTotal}`)
  }

  // Navigate to history page in new tab
  const viewHistory = () => {
    window.open('/pdwise-budget-allocation-aap-central-history', '_blank')
  }

  // Function to prepare table data for export
  const prepareTableData = () => {
	const data = []
	
	// First header row - Program Division names
	const headerRow1 = ['Unified HoA-KY']
	filteredProgramDivisions.value.forEach(pd => {
	  const pdColumns = []
	  if (showAllocation.value) pdColumns.push('Allocation')
	  if (showRelease.value) pdColumns.push('Release')
	  if (showExpenditure.value) pdColumns.push('Expenditure')
	  headerRow1.push(...pdColumns)
	})
	headerRow1.push('Final Allocation', 'Total Release', 'Total Expenditure')
	data.push(headerRow1)
	
	// Second header row - Column types
	const headerRow2 = ['']
	filteredProgramDivisions.value.forEach(() => {
	  if (showAllocation.value) headerRow2.push('₹ In Lakhs')
	  if (showRelease.value) headerRow2.push('₹ In Lakhs')
	  if (showExpenditure.value) headerRow2.push('₹ In Lakhs')
	})
	headerRow2.push('₹ In Lakhs', '₹ In Lakhs', '₹ In Lakhs')
	data.push(headerRow2)
	
	// Data rows from filtered categories
	filteredCategorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'major_head') {
		const row = [category.label]
		filteredProgramDivisions.value.forEach(pd => {
		  if (showAllocation.value) {
			row.push(calculateMajorHeadTotalForPD(category.label, pd.division_id, 'allocation'))
		  }
		  if (showRelease.value) {
			row.push(calculateMajorHeadTotalForPD(category.label, pd.division_id, 'release'))
		  }
		  if (showExpenditure.value) {
			row.push(calculateMajorHeadTotalForPD(category.label, pd.division_id, 'expenditure'))
		  }
		})
		row.push(
		  calculateMajorHeadTotal(category.label),
		  calculateMajorHeadTotalRelease(category.label),
		  calculateMajorHeadTotalExpenditure(category.label)
		)
		data.push(row)
	  } else if (category.type === 'subcategory') {
		// Subcategory row
		const subcategoryRow = [category.label]
		filteredProgramDivisions.value.forEach(pd => {
		  if (showAllocation.value) {
			subcategoryRow.push(calculateSubcategoryTotalForPD(category.label, pd.division_id, category.parentMajorHead, 'allocation'))
		  }
		  if (showRelease.value) {
			subcategoryRow.push(calculateSubcategoryTotalForPD(category.label, pd.division_id, category.parentMajorHead, 'release'))
		  }
		  if (showExpenditure.value) {
			subcategoryRow.push(calculateSubcategoryTotalForPD(category.label, pd.division_id, category.parentMajorHead, 'expenditure'))
		  }
		})
		subcategoryRow.push(
		  calculateSubcategoryTotal(category.label, category.parentMajorHead),
		  calculateSubcategoryTotalRelease(category.label, category.parentMajorHead),
		  calculateSubcategoryTotalExpenditure(category.label, category.parentMajorHead)
		)
		data.push(subcategoryRow)
		
		// Individual budget head rows
		category.budgetHeads.forEach(bh => {
		  const bhRow = [`${bh.budget_code} - ${bh.budget_name}`]
		  filteredProgramDivisions.value.forEach(pd => {
			if (showAllocation.value) {
			  bhRow.push(formatToFiveDecimals(allocationData.value[bh.bh_id]?.[pd.division_id] || 0))
			}
			if (showRelease.value) {
			  bhRow.push(getReleaseAmount(bh.bh_id, pd.division_id))
			}
			if (showExpenditure.value) {
			  bhRow.push(getExpenditureAmount(bh.bh_id, pd.division_id))
			}
		  })
		  bhRow.push(
			calculateRowTotal(bh.bh_id),
			calculateRowTotalRelease(bh.bh_id),
			calculateRowTotalExpenditure(bh.bh_id)
		  )
		  data.push(bhRow)
		})
	  }
	})
	
	// Total row
	const totalRow = ['Total']
	filteredProgramDivisions.value.forEach(pd => {
	  if (showAllocation.value) {
		totalRow.push(calculateColumnTotal(pd.division_id, 'allocation'))
	  }
	  if (showRelease.value) {
		totalRow.push(calculateColumnTotal(pd.division_id, 'release'))
	  }
	  if (showExpenditure.value) {
		totalRow.push(calculateColumnTotal(pd.division_id, 'expenditure'))
	  }
	})
	totalRow.push(
	  calculateGrandTotal(),
	  calculateGrandTotalRelease(),
	  calculateGrandTotalExpenditure()
	)
	data.push(totalRow)
	
	return data
  }

  // Function to export to Excel (CSV format that opens in Excel)
  const exportToExcel = () => {
	const data = prepareTableData()
	let csvContent = ''
	
	data.forEach(row => {
	  const csvRow = row.map(cell => {
		const cellValue = String(cell || '')
		if (cellValue.includes(',') || cellValue.includes('"') || cellValue.includes('\n')) {
		  return `"${cellValue.replace(/"/g, '""')}"`
		}
		return cellValue
	  })
	  csvContent += csvRow.join(',') + '\n'
	})
	
	// Create BOM for UTF-8 to support special characters
	const BOM = '\uFEFF'
	const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
	const link = document.createElement('a')
	const url = URL.createObjectURL(blob)
	
	link.setAttribute('href', url)
	link.setAttribute('download', `PDwise_Budget_Allocation_Release_${selectedFinancialYear.value}_${new Date().getTime()}.xlsx`)
	link.style.visibility = 'hidden'
	document.body.appendChild(link)
	link.click()
	document.body.removeChild(link)
  }

  // Function to export to CSV
  const exportToCSV = () => {
	const data = prepareTableData()
	let csvContent = ''
	
	data.forEach(row => {
	  const csvRow = row.map(cell => {
		const cellValue = String(cell || '')
		if (cellValue.includes(',') || cellValue.includes('"') || cellValue.includes('\n')) {
		  return `"${cellValue.replace(/"/g, '""')}"`
		}
		return cellValue
	  })
	  csvContent += csvRow.join(',') + '\n'
	})
	
	const BOM = '\uFEFF'
	const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
	const link = document.createElement('a')
	const url = URL.createObjectURL(blob)
	
	link.setAttribute('href', url)
	link.setAttribute('download', `PDwise_Budget_Allocation_Release_${selectedFinancialYear.value}_${new Date().getTime()}.csv`)
	link.style.visibility = 'hidden'
	document.body.appendChild(link)
	link.click()
	document.body.removeChild(link)
  }

  // Function to export to PDF using print
  const exportToPDF = () => {
	const printWindow = window.open('', '_blank')
	const tableElement = document.getElementById('reportTable')
	
	if (!tableElement) {
	  alert('Table not found')
	  return
	}
	
	const tableHTML = tableElement.outerHTML
	
	const headStart = '<head>'
	const titleTag = '<title>PD wise Budget Allocation (AAP) with release - Budget Heads - ' + selectedFinancialYear.value + '</title>'
	const styleStart = '<style>'
	const styles = 'body { font-family: Arial, sans-serif; margin: 20px; }' +
	  'h2 { text-align: center; color: #333; }' +
	  '.meta-info { text-align: center; margin-bottom: 20px; color: #666; }' +
	  'table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 9px; }' +
	  'table th, table td { border: 1px solid #ddd; padding: 4px; text-align: center; }' +
	  'table th { background-color: #343a40; color: white; font-weight: bold; }' +
	  '.bg-success, .bg-success-subtle { background-color: #d4edda !important; }' +
	  '.table-primary { background-color: #cce7ff !important; }' +
	  '.table-secondary { background-color: #e2e3e5 !important; }' +
	  '.table-warning { background-color: #fff3cd !important; }' +
	  '.fw-bold { font-weight: bold; }' +
	  '.fw-sticky { position: sticky; left: 0; background-color: #fff; z-index: 1; }' +
	  '@media print { @page { size: landscape; margin: 1cm; } body { margin: 0; } }'
	const styleEnd = '</style>'
	const headEnd = '</head>'
	
	const bodyStart = '<body>'
	const h2Tag = '<h2>PD wise Budget Allocation (AAP) with release - Budget Heads</h2>'
	const metaInfoStart = '<div class="meta-info">'
	const financialYearP = '<p><strong>Financial Year:</strong> ' + selectedFinancialYear.value + '</p>'
	const generatedP = '<p><strong>Generated on:</strong> ' + new Date().toLocaleString() + '</p>'
	const programDivisionsP = selectedProgramDivisions.value.length > 0 
	  ? '<p><strong>Program Divisions:</strong> ' + selectedProgramDivisions.value.map(id => getProgramDivisionName(id)).join(', ') + '</p>' 
	  : '<p><strong>Program Divisions:</strong> All Program Divisions</p>'
	const majorHeadP = selectedMajorHeads.value.length > 0 
	  ? '<p><strong>Major Heads:</strong> ' + selectedMajorHeads.value.map(code => getMajorHeadLabel(code)).join(', ') + '</p>' 
	  : '<p><strong>Major Heads:</strong> All Major Heads</p>'
	const budgetHeadP = selectedBudgetHeads.value.length > 0 
	  ? '<p><strong>Budget Heads:</strong> ' + selectedBudgetHeads.value.length + ' selected</p>' 
	  : '<p><strong>Budget Heads:</strong> All Budget Heads</p>'
	const columnsP = '<p><strong>Visible Columns:</strong> ' + 
	  (showAllocation.value ? 'Allocation ' : '') +
	  (showRelease.value ? 'Release ' : '') +
	  (showExpenditure.value ? 'Expenditure ' : '') +
	  'Final Allocation, Total Release, Total Expenditure</p>'
	const metaInfoEnd = '</div>'
	const scriptStart = '<' + 'script' + '>'
	const scriptContent = 'window.onload = function() { window.print(); }'
	const scriptEnd = '<' + '/' + 'script' + '>'
	const scriptTag = scriptStart + scriptContent + scriptEnd
	const bodyEnd = '<' + '/' + 'body' + '>'
	const htmlEnd = '<' + '/' + 'html' + '>'
	
	const htmlContent = '<!DOCTYPE html><html>' +
	  headStart + titleTag + styleStart + styles + styleEnd + headEnd +
	  bodyStart + h2Tag + metaInfoStart + financialYearP + generatedP + programDivisionsP + majorHeadP + budgetHeadP + columnsP + metaInfoEnd +
	  tableHTML + scriptTag + bodyEnd + htmlEnd
	
	printWindow.document.write(htmlContent)
	printWindow.document.close()
  }
  
  // Load data on component mount
  onMounted(async () => {
	try {
	  console.log('Component mounted, starting to load data...')
	  
	  // Add click outside listener
	  document.addEventListener('click', handleClickOutside)
	  
	  console.log('Fetching budget heads and program divisions...')
	  await Promise.all([fetchBudgetHeads(), fetchProgramDivisions()])
	  
	  console.log('Data fetched, initializing allocation data...')
	  console.log('Budget Heads:', budgetHeads.value)
	  console.log('Program divisions:', programDivisions.value)
	  
	  initializeAllocationData()
	  
	  console.log('Allocation data initialized, fetching existing allocations, release and expenditure data...')
	  await Promise.all([
		fetchExistingAllocations(),
		fetchReleaseData(),
		fetchExpenditureData()
	  ])
	  
	  console.log('Component initialization completed')
	} catch (err) {
	  console.error('Error initializing component:', err)
	  error.value = 'Failed to initialize component'
	} finally {
	  loading.value = false
	  console.log('Component loading completed')
	}
  })

  // Cleanup on unmount
  onBeforeUnmount(() => {
	document.removeEventListener('click', handleClickOutside)
  })
  </script>
  
  <style scoped>
  .tableform-control-withoutbg {
	background: transparent;
	border: 1px solid #dee2e6;
	text-align: center;
  }
  
  .tableform-control-withoutbg:focus {
	background: white;
	border-color: #80bdff;
	box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  }
  
  .table th {
	vertical-align: middle;
	white-space: nowrap;
  }
  
  .table td {
	vertical-align: middle;
  }
  
  .btn-lg {
	padding: 12px 30px;
	font-size: 18px;
  }
  
  /* Style for total cells */
  .total-cell {
	background-color: #f8f9fa;
	font-weight: bold;
	color: #495057;
  }
  
  /* Style for row totals */
  .row-total {
	background-color: #e9ecef;
	font-weight: bold;
	color: #495057;
  }
  
  /* Style for grand total */
  .grand-total {
	background-color: #fff3cd;
	font-weight: bold;
	color: #856404;
  }

  /* Style for categorized structure */
  .table-primary {
	background-color: #cce7ff !important;
	color: #004085;
  }

  .table-secondary {
	background-color: #e2e3e5 !important;
	color: #383d41;
  }

  .budget-head-row {
	background-color: #ffffff;
  }

  .budget-head-row:hover {
	background-color: #f8f9fa;
  }

  /* Indentation for hierarchy */
  .table td[style*="paddingLeft"] {
	border-left: 3px solid #007bff;
  }

  /* Major head styling */
  .table-primary td:first-child {
	font-weight: bold;
	font-size: 1.1em;
  }

  /* Subcategory styling */
  .table-secondary td:first-child {
	font-weight: 600;
	font-style: italic;
  }

  /* Total cell styling */
  .total-cell {
	background-color: #f8f9fa;
	font-weight: bold;
	color: #495057;
	border: 2px solid #dee2e6;
	transition: all 0.3s ease;
	cursor: help;
  }

  /* Major head total cells */
  .table-primary .total-cell {
	background-color: #e3f2fd;
	color: #0d47a1;
  }

  .table-primary .total-cell:hover {
	background-color: #bbdefb;
	transform: scale(1.02);
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  /* Subcategory total cells */
  .table-secondary .total-cell {
	background-color: #f3e5f5;
	color: #4a148c;
  }

  .table-secondary .total-cell:hover {
	background-color: #e1bee7;
	transform: scale(1.02);
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  /* Grand total cell styling */
  .grand-total-cell {
	background-color: #fff3cd;
	color: #856404;
	border: 2px solid #ffc107;
	font-weight: bold;
  }

  /* Input field styling for budget head rows */
  .budget-head-row input {
	background-color: #ffffff;
	border: 1px solid #ced4da;
  }

  .budget-head-row input:focus {
	background-color: #ffffff;
	border-color: #80bdff;
	box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  }

  /* Summary section styling */
  .card-header h6 {
	color: #495057;
	font-weight: 600;
  }

  .badge {
	font-size: 0.8em;
	padding: 0.4em 0.6em;
  }

  .badge.bg-primary {
	background-color: #007bff !important;
  }

  .badge.bg-success {
	background-color: #28a745 !important;
  }

  .badge.bg-warning {
	background-color: #ffc107 !important;
	color: #212529 !important;
  }

  /* Table improvements */
  .table th {
	background-color: #343a40;
	color: white;
	font-weight: 600;
  }

  .table td {
	vertical-align: middle;
  }

  /* Responsive improvements */
  @media (max-width: 768px) {
	.table-responsive {
	  font-size: 0.9em;
	}
	
	.btn-lg {
	  padding: 8px 16px;
	  font-size: 14px;
	}
  }
  /* to make first column sticky of the table */
  .fw-sticky {
		position: sticky;
		left: 0;
		background-color: #f2f2f2;
		z-index: 1;
  }

  /* Sub-column header styling */
  .sub-column-header {
		font-size: 0.85em;
		padding: 8px 4px;
		background-color: #495057;
		border: 1px solid #343a40;
  }

  /* Ensure proper spacing for 3 columns per PD */
  .table th.sub-column-header {
		white-space: nowrap;
		font-weight: 600;
  }

  /* Custom Multiselect Styles */
  .custom-multiselect-container {
	position: relative;
  }

  .custom-multiselect-input {
	min-height: 38px;
	padding: 4px 8px;
	display: flex;
	align-items: center;
	cursor: text;
	position: relative;
  }

  .custom-multiselect-input.is-open {
	border-color: #80bdff;
	box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  }

  .selected-tags-wrapper {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 4px;
	flex: 1;
	min-width: 0;
  }

  .custom-tag {
	display: inline-flex;
	align-items: center;
	background-color: #b3d9ff;
	color: #0056b3;
	padding: 2px 6px;
	border-radius: 3px;
	font-size: 0.875rem;
	white-space: nowrap;
	margin: 2px 0;
  }

  .tag-remove {
	margin-left: 6px;
	cursor: pointer;
	font-weight: bold;
	font-size: 1rem;
	line-height: 1;
	color: #0056b3;
	padding: 0 2px;
  }

  .tag-remove:hover {
	color: #003d82;
  }

  .tag-input {
	border: none;
	outline: none;
	background: transparent;
	flex: 1;
	min-width: 100px;
	padding: 2px 4px;
	font-size: 0.875rem;
  }

  .tag-input::placeholder {
	color: #6c757d;
	opacity: 1;
  }

  .dropdown-arrows {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 0 8px;
	color: #6c757d;
	font-size: 0.75rem;
	cursor: pointer;
  }

  .custom-dropdown-menu {
	position: absolute;
	top: 100%;
	left: 0;
	right: 0;
	background: white;
	border: 1px solid #ced4da;
	border-top: none;
	border-radius: 0 0 0.25rem 0.25rem;
	max-height: 200px;
	overflow-y: auto;
	overflow-x: hidden;
	z-index: 1000;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
	margin-top: -1px;
  }

  /* Custom Scrollbar Styling */
  .custom-dropdown-menu::-webkit-scrollbar {
	width: 8px;
  }

  .custom-dropdown-menu::-webkit-scrollbar-track {
	background: #f1f1f1;
	border-radius: 4px;
  }

  .custom-dropdown-menu::-webkit-scrollbar-thumb {
	background: #888;
	border-radius: 4px;
  }

  .custom-dropdown-menu::-webkit-scrollbar-thumb:hover {
	background: #555;
  }

  /* Firefox Scrollbar */
  .custom-dropdown-menu {
	scrollbar-width: thin;
	scrollbar-color: #888 #f1f1f1;
  }

  .dropdown-item {
	padding: 8px 12px;
	cursor: pointer;
	font-size: 0.875rem;
	border-bottom: 1px solid #f0f0f0;
  }

  .dropdown-item:last-child {
	border-bottom: none;
  }

  .dropdown-item:hover,
  .dropdown-item.highlighted {
	background-color: #f8f9fa;
  }

  .dropdown-item:active {
	background-color: #e9ecef;
  }

  /* Export buttons styling */
  .export-buttons {
	display: flex;
	gap: 8px;
  }
  </style>