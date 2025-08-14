<template>
	<div class="wrapper">
	  <Sidebar />
	  <div class="main-panel">
		<Header />
		  <div class="container">
			<div class="page-inner allinsideform">
			  <div class="page-header">
				<h3 class="fw-bold mb-3">Annual Action Plan Module</h3>
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
						  						<div class="card-title">PD wise Budget Allocation (AAP) - Budget Heads for FY 2025-26 (₹ In Lakhs)</div>
					  </div>
  
					  <div class="card-body">
						  <div v-if="loading" class="text-center">
							  <div class="spinner-border" role="status">
								  <span class="visually-hidden">Loading...</span>
							  </div>
						  </div>
  
						  <div v-else-if="error" class="alert alert-danger">
							  {{ error }}
						  </div>
  
						  <div v-else>
							  <div class="table-responsive">
								  <table class="table table-bordered table-hover align-middle text-center">
									  <thead class="table-dark">
										  <tr>
											<!-- <th rowspan="2" class="align-middle"></th> -->
											<th class="align-middle"></th>
											  <th v-for="pd in programDivisions" :key="pd.division_id" colspan="1">
												  {{ pd.division_name }}<br/>(Proposed by KY)<br/>by as per BE
											  </th>
											  <th class="align-middle">Final Allocation</th>
											  <!-- <th rowspan="2" class="align-middle">Remarks</th> -->
										  </tr>
										  <tr>
											  <th class="align-middle">Unified HoA-KY</th>
											  <th v-for="pd in programDivisions" :key="pd.division_id">
												 ₹ In Lakhs
											  </th>
											  <th class="align-middle">₹ In Lakhs</th>
										  </tr>
										  <tr>
											  <th class="align-middle">Major Head-2435</th>
											  <th v-for="pd in programDivisions" :key="pd.division_id">
												 <!-- ₹ In Lakhs -->NA
											  </th>
											  <th class="align-middle">NA</th>
										  </tr>
										  <tr>
											  <th class="align-middle">General Component</th>
											  <th v-for="pd in programDivisions" :key="pd.division_id">
												 <!-- ₹ In Lakhs -->NA
											  </th>
											  <th>NA</th>
										  </tr>
									  </thead>
									  										<tbody>
										  <tr v-for="bh in budgetHeads" :key="bh.bh_id">
											  <td class="fw-bold">{{ bh.budget_code }} - {{ bh.budget_name }}</td>
											  <td v-for="pd in programDivisions" :key="pd.division_id">
												  <input 
													  type="number" 
													  class="form-control tableform-control-withoutbg" 
													  v-model="allocationData[bh.bh_id][pd.division_id]"
													  placeholder="0.00"
													  step="0.01"
													  min="0"
												  >
											  </td>
											  <!-- <td class="fw-bold text-center row-total"> -->
											  <td class="fw-bold text-center bg-success-subtle">
												  {{ calculateRowTotal(bh.bh_id) }}
											  </td>
											  <!-- <td>
												  <input 
													  type="text" 
													  class="form-control tableform-control-withoutbg" 
													  v-model="remarksData[bh.bh_id]"
													  placeholder="Remark"
												  >
											  </td> -->
										  </tr>
										  
										  <!-- Total Row -->
										  <tr class="table-warning fw-bold">
											  <td>Total</td>
											  <!-- <td v-for="pd in programDivisions" :key="pd.division_id" class="total-cell"> -->
											  <td v-for="pd in programDivisions" :key="pd.division_id" >
												  {{ calculateColumnTotal(pd.division_id) }}
											  </td>
											  <td class="fw-bold text-center grand-total">
												  {{ calculateGrandTotal() }}
											  </td>
											  <td>-</td>
										  </tr>
									  </tbody>
								  </table>
							  </div>
  
							  <!-- Submit Button -->
							  <div class="row mt-4">
								  <div class="col-12 text-center">
									  <button 
										  @click="submitAllocation" 
										  class="btn btn-primary btn-lg"
										  :disabled="submitting"
									  >
										  <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
										  {{ submitting ? 'Saving...' : 'Submit Allocation' }}
									  </button>
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
  
  // Fetch budget heads from API
  const fetchBudgetHeads = async () => {
	try {
	  const response = await fetch('/api/aap-budget-heads')
	  if (!response.ok) throw new Error('Failed to fetch budget heads')
	  const data = await response.json()
	  budgetHeads.value = data
	  console.log('Budget heads fetched successfully:', data)
	} catch (err) {
	  console.error('Error fetching budget heads:', err)
	  error.value = 'Failed to load budget heads'
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
			  // Use exact amount as stored
			  const amount = parseFloat(allocation.amount)
			  allocationData.value[bhId][pdId] = amount.toFixed(3)
			  console.log(`Set amount for budget head ${bhId}, PD ${pdId}: ${amount.toFixed(3)}`)
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
  
  // Initialize allocation data structure
  const initializeAllocationData = () => {
	console.log('Initializing allocation data structure...')
	console.log('Budget Heads:', budgetHeads.value)
	console.log('Program Divisions:', programDivisions.value)
	
	budgetHeads.value.forEach(bh => {
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
	budgetHeads.value.forEach(bh => {
	  const value = parseFloat(allocationData.value[bh.bh_id][pdId]) || 0
	  total += value
	})
	return total.toFixed(3)
  }
  
  // Calculate row total for a specific budget head
  const calculateRowTotal = (bhId) => {
	let total = 0
	programDivisions.value.forEach(pd => {
	  const value = parseFloat(allocationData.value[bhId][pd.division_id]) || 0
	  total += value
	})
	return total.toFixed(3)
  }
  
  // Calculate grand total (sum of all allocations)
  const calculateGrandTotal = () => {
	let total = 0
	budgetHeads.value.forEach(bh => {
	  programDivisions.value.forEach(pd => {
		const value = parseFloat(allocationData.value[bh.bh_id][pd.division_id]) || 0
		total += value
	  })
	})
	return total.toFixed(3)
  }
  
  // Watch for changes in allocation data to trigger reactive updates
  watch(allocationData, () => {
	// This will trigger reactive updates when allocation data changes
  }, { deep: true })
  
  // Submit allocation data
  const submitAllocation = async () => {
	submitting.value = true
	
	try {
	  // Prepare data for submission
	  const submissionData = []
	  
	  budgetHeads.value.forEach(bh => {
		programDivisions.value.forEach(pd => {
		  const amount = allocationData.value[bh.bh_id][pd.division_id]
		  if (amount && amount > 0) {
			submissionData.push({
			  financial_year: '2025-26',
			  bh_id: bh.bh_id,
			  pd_id: pd.division_id,
			  amount: parseFloat(amount), // Save exact amount as entered
			  status: 1
			})
		  }
		})
	  })
  
	  // console.log("========================submissionData=======================");
	  // console.log(submissionData);
	  // return false;
  
	  if (submissionData.length === 0) {
		alert('Please enter at least one allocation amount')
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
  </style>