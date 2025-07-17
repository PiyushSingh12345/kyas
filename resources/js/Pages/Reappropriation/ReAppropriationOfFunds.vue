<template id="reappropriation">
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
											<div class="col-md-6 col-lg-4">
												<div class="form-group">
													<label for="email2">F.Y</label>
													<select class="form-select" v-model="selectedYear">
														<option value="" disabled>Select Financial Year</option>
														<option v-for="year in financialYears" :key="year" :value="year">{{ year }}</option>
													</select>
												</div>
											</div>
											<div class="col-md-6 col-lg-4">
												<div class="form-group">
													<label for="email2">Budget Phase</label>
													<select class="form-select" v-model="selectedPhase">
														<option value="" disabled>Budget Phase</option>
                            <option v-for="phase in budgetPhases" :key="phase" :value="phase">{{ phase }}</option>
                            <!-- <option  value = "RE">RE</option> -->
														
														<!-- Add more years as needed -->
													</select>
												</div>
											</div>
											<div class="col-md-6 col-lg-4">
												<div class="form-group">
													<label for="email2">RO Date</label>
													<input type="date" class="form-control" id="email2">
												</div>
											</div>
											<div class="col-md-6 col-lg-4">
												<div class="form-group">
													<label for="email2">Type</label>
													<input type="text" class="form-control">
												</div>
											</div>
											<div class="col-md-6 col-lg-4">
												<div class="form-group">
													<label for="email2">Section</label>
													<input type="text" class="form-control">
												</div>
											</div>
											<div class="col-md-6 col-lg-4">
												<div class="form-group">
													<label for="email2">Program Division</label>
													<select name="user" id="usertype" class="form-select" v-model="selectedDivision">
														<option value="" disabled>Program Division</option>
                            <option v-for="division in programDivisions" :key="division.division_id" :value="division.division_id">{{ division.division_name }}</option>
                            <!-- <option  value = "RE">RE</option> -->
														
														<!-- Add more years as needed -->
													</select>
												</div>
											</div>
											<div class="col-md-6 col-lg-4">
												<div class="form-group">
													<label for="email2">From Budget Head</label>
													<select name="user" id="usertype" class="form-select" v-model="selectedFromBudgetHead">
														<option value="" disabled>--Select--</option>
                            <option v-for="head in budgetHeads" :key="head.id" :value="head.id">{{ head.budget }}</option>
                            <!-- <option  value = "RE">RE</option> -->
														
														<!-- Add more years as needed -->
													</select>
												</div>
											</div>
											<div class="col-md-6 col-lg-4">
												<div class="form-group">
													<label for="email2">BE (IN INR THOUSAND)</label>
													<input type="text" class="form-control" placeholder="1,8906" disabled="">
												</div>
											</div>
											<div class="col-md-6 col-lg-4">
												<div class="form-group">
													<label for="email2">Re-Appropriation Amt</label>
													<input type="text" class="form-control" placeholder="78906">
												</div>
											</div>
											<div class="col-md-6 col-lg-4">
												<div class="form-group">
													<label for="email2">Other Details</label>
													<input type="text" class="form-control" placeholder="xyz">
												</div>
											</div>
											<div class="bg-body-secondary mt-4 mb-4 rebdr">
												<div class="row">
													<div class="col-md-6 col-lg-4">
														<div class="form-group">
															<label for="email2">To Budget Head</label>
															<select name="user" id="usertype" class="form-select" v-model="selectedToBudgetHead">
																<option value="" disabled>--Select--</option>
                            <option v-for="head in budgetHeads" :key="head.id" :value="head.id">{{ head.budget }}</option>
                            <!-- <option  value = "RE">RE</option> -->
														
														<!-- Add more years as needed -->
															</select>
														</div>
													</div>
													<div class="col-md-6 col-lg-4">
														<div class="form-group">
															<label for="email2">BE (in INR Thousand)</label>
															<input type="text" class="form-control" placeholder="12,7890" disabled="">
														</div>
													</div>
													<div class="col-md-6 col-lg-4">
														<div class="form-group">
															<label for="email2">Re-appropriation Amount(INR)</label>
															<input type="text" class="form-control" placeholder="12,7890" disabled="">
														</div>
													</div>
													<div class="col-md-6 col-lg-4">
														<div class="form-group">
															<label for="email2">Select Entity Type</label>
															<div class="d-flex">
																<div class="form-check">
																	<input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1" checked="">
																	<label class="form-check-label" for="flexRadioDefault1">
																		State/UT
																	</label>
																</div>
																<div class="form-check">
																	<input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault2">
																	<label class="form-check-label" for="flexRadioDefault2">
																		Agency
																	</label>
																</div>
																<div class="form-check">
																	<input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault3">
																	<label class="form-check-label" for="flexRadioDefault3">
																		Admin
																	</label>
																</div>
															</div>
														</div>
													</div>
													<div class="col-md-6 col-lg-4">
														<div class="form-group">
															<label for="email2">Select Entity</label>
                              <select name="user" id="usertype" class="form-select">
																<option selected="">--Select--</option>
															</select>
															<!-- <select name="user" id="usertype" class="form-select" v-model="selectedState">
																<option value="" disabled>--Select State--</option>
																<option v-for="state in states" :key="state.id" :value="state.id">{{ state.name }}</option>
															</select> -->
														</div>
													</div>
													<div class="col-md-12 col-lg-12">
														<button class="btn btn-primary me-1 mb-4">+Add More Re-Appropriation</button>
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-md-6 col-lg-4">
													<div class="form-group">
														<label for="email2">Reason for Additionality</label>
														<input type="text" class="form-control" placeholder="xyz">
													</div>
												</div>
												<div class="col-md-6 col-lg-4">
													<div class="form-group">
														<label for="email2">Proposal Attract NS/NIS</label>
														<select name="user" id="usertype" class="form-select">
															<option selected="">--Yes--</option>
															<option>--No--</option>
														</select>
													</div>
												</div>
											</div>
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
														<tr v-for="item in reappropriations" :key="item.id">
    <td>{{ item.budget_head }}</td>
    <td>{{ item.category }}</td>
    <td>{{ item.budget_head }}</td> <!-- To HOA: adjust if you have a separate field -->
    <td>{{ item.state ? item.state.state_type || '' : '' }}</td>
    <td>{{ item.state ? item.state.name : '' }}</td>
    <td>{{ item.category }}</td> <!-- To Rule: adjust if you have a separate field -->
  </tr>
  <tr v-if="reappropriations.length === 0">
    <td colspan="6" class="text-center">No records found</td>
  </tr>
													</tbody>
												</table>
											</div>
										</div>
									</div>
									<div class="card-footer">
										<div class="form">
											<div class="col-12 d-flex justify-content-center">
												<button class="btn btn-success me-1">Submit</button>
												<button class="btn btn-danger me-1">Reset</button>
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
    import { ref, onMounted } from 'vue';
    import axios from 'axios';

    // Correct relative paths (from createUser.vue to Common/)
    import Header from '../Common/Header.vue'
    import Sidebar from '../Common/Sidebar.vue'
    import Footer from '../Common/Footer.vue'

    // State for dropdowns
    const financialYears = ref([
      '2024-2025',
      '2023-2024',
      '2022-2023',
      '2021-2022',
    ]);
    const budgetPhases = ref(['BE', 'FE', 'RE']);
    const programDivisions = ref([]);
    const budgetHeads = ref([]);
    const states = ref([]);
    const reappropriations = ref([]);

    // Selected values
    const selectedYear = ref('');
    const selectedPhase = ref('');
    const selectedDivision = ref('');
    const selectedFromBudgetHead = ref('');
    const selectedToBudgetHead = ref('');
    const selectedState = ref('');

    onMounted(async () => {
      // Fetch program divisions
      try {
        const res = await axios.get('/md-program-divisions');
        programDivisions.value = res.data;
      } catch (e) {
        programDivisions.value = [];
      }
      // Fetch budget heads
      try {
        const res = await axios.get('/api/budget-heads');
        budgetHeads.value = res.data;
      } catch (e) {
        budgetHeads.value = [];
      }
      // Fetch states
      try {
        const res = await axios.get('/api/states');
        states.value = res.data;
      } catch (e) {
        states.value = [];
      }
      // Fetch re-appropriation records
      try {
        const res = await axios.get('/api/mother-sanctions-list');
        reappropriations.value = res.data;
      } catch (e) {
        reappropriations.value = [];
      }
    });
  </script>
