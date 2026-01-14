<template>
	<div class="wrapper">
	  <Sidebar />
	  <div class="main-panel">
		<Header />
		  <div class="container">
			<div class="page-inner allinsideform">
			  <div class="page-header">
				<!-- <h3 class="fw-bold mb-3">Annual Action Plan Module</h3> -->
				<h3 class="fw-bold mb-3">Budget Allocation Module</h3>
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
					<a href="#">PD wise Budget Allocation (AAP) - Budget Heads</a>
				  </li>
				</ul>
			  </div>
			  
			  <div class="row">
				<div class="col-md-12">
				  <div class="card">
					  <div class="card-header">
						  <div class="card-title d-flex justify-content-between align-items-center">
							  <span>PD wise Budget Allocation (AAP) - Budget Heads for FY {{ selectedFinancialYear }} (₹ In Lakhs)</span>
							  <button 
								  class="btn btn-outline-info btn-sm d-flex align-items-center" 
								  @click="viewHistory"
								  title="View Allocation History"
							  >
								  <i class="fas fa-history"></i> &nbsp;History
							  </button>
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
												  <div class="col-md-4">
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

												  <!-- Budget Phase Filter -->
												  <div class="col-md-4">
													  <label for="budgetPhase" class="form-label fw-bold">Budget Phase</label>
													  <select 
														  class="form-select" 
														  id="budgetPhase" 
														  v-model="selectedPhase" 
														  @change="onBudgetPhaseChange"
													  >
														  <option disabled value="0">Select Budget Phase</option>
														  <option value="BE">BE</option>
														  <option value="RE">RE</option>
														  <option value="FE">FE</option>
													  </select>
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

							  <div class="table-responsive">
								  <table class="table table-bordered table-hover align-middle text-center">
									  <thead class="table-dark">
										  <tr>
											<!-- <th rowspan="2" class="align-middle"></th> -->
											<th class="align-middle fw-sticky"></th>
											  <th v-for="pd in programDivisions" :key="pd.division_id" colspan="1">
												  {{ pd.division_name }}<br/>(Proposed by KY)<br/>by as per BE
											  </th>
											  <th class="align-middle">Final Allocation</th>
											  <!-- <th rowspan="2" class="align-middle">Remarks</th> -->
										  </tr>
										  <tr>
											  <th class="align-middle fw-sticky">Unified HoA-KY</th>
											  <th v-for="pd in programDivisions" :key="pd.division_id">
												 ₹ In Lakhs
											  </th>
											  <th class="align-middle">₹ In Lakhs</th>
										  </tr>
									  </thead>
									  <tbody>
										  <!-- Categorized Budget Heads -->
										  <template v-for="category in categorizedBudgetHeads" :key="category.id">
											<!-- Major Head Row -->
											<tr v-if="category.type === 'major_head'" class="table-primary fw-bold">
											  <td class="text-start fw-sticky" :style="{ paddingLeft: '20px' }">
												{{ category.label }}
											  </td>
											  <td v-for="pd in programDivisions" :key="pd.division_id" class="text-center fw-bold total-cell" 
												   :title="`Total for ${pd.division_name} under ${category.label}`">
												{{ calculateMajorHeadTotalForPD(category.label, pd.division_id) }}
											  </td>
											  <td class="text-center fw-bold grand-total-cell" title="Grand total for all program divisions">
												{{ calculateMajorHeadTotal(category.label) }}
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
											  <td v-for="pd in programDivisions" :key="pd.division_id" class="text-center fw-bold total-cell"
												   :title="`Total for ${pd.division_name} under ${category.label}${category.budgetHeads.length === 1 ? ' (Single record)' : ''}`">
												{{ calculateSubcategoryTotalForPD(category.label, pd.division_id, category.parentMajorHead) }}
											  </td>
											  <td class="text-center fw-bold grand-total-cell" title="Grand total for all program divisions">
												{{ calculateSubcategoryTotal(category.label, category.parentMajorHead) }}
											  </td>
											</tr>
											
											<!-- Individual Budget Head Rows -->
											<tr v-for="bh in category.budgetHeads" :key="`bh_${bh.bh_id}`" 
												 v-if="category.type === 'subcategory'"
												 class="budget-head-row">
											  <td class="text-start fw-sticky" :style="{ paddingLeft: '60px' }">
												{{ bh.budget_code }} - {{ bh.budget_name }}
											  </td>
											  <td v-for="pd in programDivisions" :key="pd.division_id">
												<input 
													type="number" 
													class="form-control tableform-control-withoutbg" 
													v-model="allocationData[bh.bh_id][pd.division_id]"
													@blur="formatInputValue(bh.bh_id, pd.division_id)"
													placeholder="0.00000"
													step="0.00001"
													min="0"
												>
											  </td>
											  <td class="text-center fw-bold bg-success-subtle">
												{{ calculateRowTotal(bh.bh_id) }}
											  </td>
											</tr>
										  </template>
										  
										  <!-- Total Row -->
										  <tr class="table-warning fw-bold">
											  <td class="fw-sticky">Total</td>
											  <td v-for="pd in programDivisions" :key="pd.division_id">
												  {{ calculateColumnTotal(pd.division_id) }}
											  </td>
											  <td class="text-center grand-total">
												  {{ calculateGrandTotal() }}
											  </td>
										  </tr>
										</tbody>
								  </table>
							  </div>
  
							  <!-- Submit Button -->
							  <div class="row mt-4">
								  <div class="col-12 text-center">
									  <button 
										  @click="submitAllocation" 
										  class="btn btn-primary btn-lg me-3"
										  :disabled="submitting || categorizing"
									  >
										  <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
										  {{ submitting ? 'Saving...' : 'Submit Allocation' }}
									  </button>
									  
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
  import { ref, onMounted, computed, watch } from 'vue'
  import { Link } from '@inertiajs/vue3'
  import axios from 'axios';
  import Header from '../Common/Header.vue'
  import Sidebar from '../Common/Sidebar.vue'
  import Footer from '../Common/Footer.vue'
  
  // Reactive data
  const budgetHeads = ref([])
  const programDivisions = ref([])
  const allocationData = ref({})
  const remarksData = ref({})
  const loading = ref(true)
  const error = ref(null)
  const submitting = ref(false)
  const categorizedBudgetHeads = ref([])
  const categorizing = ref(false)

  // Filter reactive data
  const selectedFinancialYear = ref('2025-26')
  const selectedPhase = ref('0')

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
	  // Build API URL with optional phase parameter
	  let apiUrl = '/api/aap-budget-heads'
	  if (selectedPhase.value && selectedPhase.value !== '0') {
		apiUrl += `?phase=${selectedPhase.value}&year=${selectedFinancialYear.value}`
	  } else {
		apiUrl += `?year=${selectedFinancialYear.value}`
	  }
	  
	  const response = await fetch(apiUrl)
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

  // Function to handle budget phase change
  const onBudgetPhaseChange = async () => {
	await fetchBudgetHeads()
	// Clear existing allocations when phase changes
	allocationData.value = {}
	await fetchExistingAllocations()
  }

  // Function to handle financial year change
  const onFinancialYearChange = async () => {
	await fetchBudgetHeads()
	// Clear existing allocations when financial year changes
	allocationData.value = {}
	await fetchExistingAllocations()
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
	  const response = await fetch(`/api/pdwise-aap-allocation?financial_year=${selectedFinancialYear.value}`)
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
  const calculateColumnTotal = (pdId) => {
	let total = 0
	const allBudgetHeads = getAllBudgetHeads()
	allBudgetHeads.forEach(bh => {
	  const value = parseFloat(allocationData.value[bh.bh_id][pdId]) || 0
	  total = addWithPrecision(total, value)
	})
	return formatToFiveDecimals(total)
  }

  // Calculate row total for a specific budget head
  const calculateRowTotal = (bhId) => {
	let total = 0
	programDivisions.value.forEach(pd => {
	  const value = parseFloat(allocationData.value[bhId][pd.division_id]) || 0
	  total = addWithPrecision(total, value)
	})
	return formatToFiveDecimals(total)
  }

  // Calculate grand total (sum of all allocations)
  const calculateGrandTotal = () => {
	let total = 0
	const allBudgetHeads = getAllBudgetHeads()
	allBudgetHeads.forEach(bh => {
	  programDivisions.value.forEach(pd => {
		const value = parseFloat(allocationData.value[bh.bh_id][pd.division_id]) || 0
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

  // Calculate total for a specific subcategory for a specific program division
  const calculateSubcategoryTotalForPD = (subcategoryLabel, pdId, majorHeadLabel = null) => {
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
		  const value = parseFloat(allocationData.value[bh.bh_id]?.[pdId]) || 0
		  total = addWithPrecision(total, value)
		}
	  })
	  
	  // If there's only one budget head under this subcategory, 
	  // show the same value as the individual budget head row
	  if (budgetHeadsInSubcategory.length === 1) {
		const singleBh = budgetHeadsInSubcategory[0]
		const singleBhTotal = parseFloat(allocationData.value[singleBh.bh_id]?.[pdId]) || 0
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
		  programDivisions.value.forEach(pd => {
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
		programDivisions.value.forEach(pd => {
		  const value = parseFloat(allocationData.value[singleBh.bh_id]?.[pd.division_id]) || 0
		  singleBhRowTotal = addWithPrecision(singleBhRowTotal, value)
		})
		return formatToFiveDecimals(singleBhRowTotal)
	  }
	}
	
	return formatTotal(total)
  }

  // Calculate total for a specific major head for a specific program division
  const calculateMajorHeadTotalForPD = (majorHeadLabel, pdId) => {
	let total = 0
	const majorHeadCode = majorHeadLabel.replace('Major Head-', '')
	
	categorizedBudgetHeads.value.forEach(category => {
	  if (category.type === 'major_head' && category.label === majorHeadLabel) {
		// Find all subcategories under this major head
		categorizedBudgetHeads.value.forEach(subCategory => {
		  if (subCategory.type === 'subcategory' && subCategory.parentMajorHead === majorHeadCode) {
			// Calculate total for this subcategory in this program division
			const subcategoryTotal = calculateSubcategoryTotalForPD(subCategory.label, pdId, majorHeadCode)
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
  
  // Load data on component mount
  onMounted(async () => {
	try {
	  console.log('Component mounted, starting to load data...')
	  
	  console.log('Fetching budget heads and program divisions...')
	  await Promise.all([fetchBudgetHeads(), fetchProgramDivisions()])
	  
	  console.log('Data fetched, initializing allocation data...')
	  console.log('Budget Heads:', budgetHeads.value)
	  console.log('Program divisions:', programDivisions.value)
	  
	  initializeAllocationData()
	  
	  console.log('Allocation data initialized, fetching existing allocations...')
	  await fetchExistingAllocations()
	  
	  console.log('Component initialization completed')
	} catch (err) {
	  console.error('Error initializing component:', err)
	  error.value = 'Failed to initialize component'
	} finally {
	  loading.value = false
	  console.log('Component loading completed')
	}
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
  </style>