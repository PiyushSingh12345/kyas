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
                  <a href="#">Annual Action Plan State</a>
                </li>
              </ul>

            </div>
            
            <div class="row">
              <div class="col-md-12">
                <div class="card">
					<div class="card-header">
						<div class="card-title">Annual Action Plan State</div>
						<!-- <h5 class="card-title">Krishonnati Yojana - State Release Data FY 2025-26 (₹ in Lakhs)</h5> -->
					</div>

					<div class="card-body">

						<div class="row mb-3 mt-3 me-2">
							<div class="col-md-4 col-lg-4">
								<div class="form-group">
									<label for="email2">Select State</label>
									<select class="form-select" id="financialYear">
										<option selected="">--Select--</option>
									</select>
								</div>
							</div>
						</div>

						<div class="table-responsive">
							<table class="table table-bordered  table-hover align-middle text-center">
								<thead class="table-dark">
									<tr>

										<th>Scheme Component</th>
										<th>SLS Details</th>
										<th>Total Allocation <br>for FY 2025-26</th>
										<th>Allocation <br> for FY 2025-26 (State Share)</th>
										<th>Allocation <br>for FY 2025-26 (Center Share)</th>
										<th>Annual Allocation <br> (Center Share) General Component</th>
										<th>Annual Allocation <br> (Center Share) SCSP</th>
										<th>Annual Allocation <br> (Center Share) TSP</th>
										<th>Annual Allocation <br> (Center Share) Capital Assets - SCSP</th>
										<th>Annual Allocation <br> (Center Share) Capital Assets - TSP</th>
										<th>Annual Allocation <br> (Center Share) DAJUGA</th>
									</tr>
								</thead>
								<tbody>

									<tr>


										<td>Agricultural Extension</td>
										<td>ATMA - SAM MISSION</td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
									</tr>
									<tr>
										<td>Agricultural Extension</td>
										<td>Soil Health and Fertility</td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>


									</tr>
									<tr>
										<td>Agricultural Extension</td>
										<td>Seeds</td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>


									</tr>

									<tr class="table-warning fw-bold">
										<td>Total</td>
										<td></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
										<td><input type="text"
												class="form-control tableform-control-withoutbg"></td>
									</tr>


									<!-- Add more rows as needed -->
								</tbody>
							</table>
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
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'

import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'


onMounted(async () => {
  
});
</script>