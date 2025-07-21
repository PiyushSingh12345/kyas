<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
					<div class="page-inner allinsideform">
						<div class="page-header">
							<h3 class="fw-bold mb-3">Agencies and Admin  
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
									<a href="RoG-Report.html">RoG Report
									</a>
								</li>


							</ul>
						</div>
						<div class="row">
							<div class="col-md-12">
								<div class="card">
									<div class="card-header">
										<div class="card-title">RoG Report
										</div>
									</div>
									<div class="card-body">
										<div class="row">
											

											<div class="col-md-6 col-lg-3">
												<div class="form-group">
													<label for="email2">F.Y</label>
													<select class="form-select" id="financialYear">
														<option value="2024-2025">2024–2025</option>
														
														<!-- Add more years as needed -->
													</select>
												</div>
											</div>
											<div class="col-md-6 col-lg-3">
												<div class="form-group">
													<label for="email2">Category</label>
													<select class="form-select" id="financialYear">
														<option selected="">--Select--</option>
													</select>
												</div>
											</div>







<div class="col-md-6 col-lg-3">
  <div class="form-group">
    <label for="email2">PD/Component</label>
    <select class="form-select" id="financialYear">
      <option selected="">-- Select --</option>
    </select>
  </div>
</div>



<div class="col-md-6 col-lg-3">
<div class="form-group">
<label for="email2">Budget Phase</label>
<select class="form-select" id="financialYear">
<option selected="">-- Select --</option>
</select>
</div>
</div>


										</div>


										
										  

										<div class="table-responsive mt-5">
											<table class="table table-bordered table-head-bg-primary">
												
												<tbody>

													<tr>
														<td ></td>
														<td ><label for="html" class="highlight_textbox">PD/Component - Allocation</label></td>
														<td><label for="html" class="highlight_textbox">PD/Component Release</label></td>
														<td><label for="html" class="highlight_textbox">PD/Component Release %</label></td>
														<td><label for="html" class="highlight_textbox">PD/Component Allocation</label></td>
														<td><label for="html" class="highlight_textbox">PD/Component Release</label></td>
														<td><label for="html" class="highlight_textbox">PD/Component Release %</label></td>
														<td><label for="html" class="highlight_textbox">Total Allocation</label></td>
                                                        <td><label for="html" class="highlight_textbox">Total Release</label></td>
														<td><label for="html" class="highlight_textbox">Total Release %</label></td>
												
													</tr>

												


													
													<tr >
													<td colspan="10" class="catgerybg">	Catergory-1</td>
														
													</tr>


													
													<tr>
														<td ><input type="text" class="form-control tableform-control-withoutbg" placeholder="Budget Head 1" style="width: 100px;"></td>
														<td ><input type="text" class="form-control tableform-control-withoutbg" placeholder="Fund Allocation under PD/Component" style="width: 230px;"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg" placeholder="Sum of Sanctions release under AA module" style="width: 270px;"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg" placeholder="Release/Allocation" style="width: 170px;"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg" style="width: 170px;"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg"  style="width: 170px;"></td>

														<td><input type="text" class="form-control tableform-control-withoutbg" style="width: 170px;"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg" style="width: 150px;"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg" style="width: 150px;"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg" style="width: 150px;"></td>
														
													</tr>

													<tr>
													<td ><input type="text" class="form-control tableform-control-withoutbg" placeholder="Budget Head 2" style="width: 100px;"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg" placeholder="Fund Allocation under PD/Component"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg" placeholder="Sum of Sanctions release under AA module"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg" placeholder="Release/Allocation"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg"></td>

														<td><input type="text" class="form-control tableform-control-withoutbg"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg"></td>
														<td><input type="text" class="form-control tableform-control-withoutbg"></td>
														
													</tr>
													
												</tbody>
											</table>
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

<script>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { ref, onMounted, watch } from 'vue'

export default {
  name: 'BudgetPhaseReport',
  components: {
    Header,
    Sidebar,
    Footer
  },
  setup() {
    const financialYear = ref('2024-2025')
    const filteredBudgetHeads = ref([])
    const isLoading = ref(false)

    const fetchBudgetSummary = async () => {
      if (!financialYear.value) return
      isLoading.value = true

      try {
        const res = await fetch(`/api/budget-phase-summary?year=${financialYear.value}`)
        const data = await res.json()
        filteredBudgetHeads.value = data
      } catch (error) {
        console.error('Error fetching budget summary:', error)
      } finally {
        isLoading.value = false
      }
    }

    onMounted(fetchBudgetSummary)
    watch(financialYear, fetchBudgetSummary)

    return {
      financialYear,
      filteredBudgetHeads,
      isLoading
    }
  }
}
</script>
